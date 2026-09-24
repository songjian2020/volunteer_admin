<?php

namespace Modules\Volunteer\Services;

use Illuminate\Support\Facades\DB;
use Modules\Volunteer\Models\VolPointsLogModel;
use Modules\Volunteer\Models\VolVolunteerModel;

class PointsService
{
    public static function addPoints(
        int $volunteerId,
        int $points,
        string $reason,
        string $type = 'manual',
        string $relatedType = '',
        int $relatedId = 0,
        int $operatorId = 0
    ): void {
        if ($points === 0) {
            throw new \RuntimeException('积分不能为0');
        }

        DB::transaction(function () use ($volunteerId, $points, $reason, $type, $relatedType, $relatedId, $operatorId) {
            $volunteer = VolVolunteerModel::lockForUpdate()->find($volunteerId);
            if (!$volunteer) {
                throw new \RuntimeException('志愿者不存在');
            }
            $nextPoints = $volunteer->total_points + $points;
            if ($nextPoints < 0) {
                throw new \RuntimeException('积分不足，当前余额 '.$volunteer->total_points);
            }
            $volunteer->total_points = $nextPoints;
            $volunteer->star_level = VolVolunteerModel::calcStarLevel($volunteer->total_points);
            $volunteer->save();

            VolPointsLogModel::create([
                'volunteer_id' => $volunteerId,
                'type' => $type,
                'reason' => $reason,
                'points' => $points,
                'related_type' => $relatedType,
                'related_id' => $relatedId,
                'operator_id' => $operatorId,
            ]);
        });
    }

    public static function deductPoints(
        int $volunteerId,
        int $points,
        string $reason,
        string $type = 'exchange',
        string $relatedType = '',
        int $relatedId = 0
    ): void {
        $volunteer = VolVolunteerModel::find($volunteerId);
        if (!$volunteer || $volunteer->total_points < $points) {
            throw new \RuntimeException('积分不足');
        }
        self::addPoints($volunteerId, -$points, $reason, $type, $relatedType, $relatedId);
    }

    public static function generateExchangeCode(): string
    {
        do {
            $code = 'WS' . strtoupper(substr(md5(uniqid((string) mt_rand(), true)), 0, 8));
        } while (\Modules\Volunteer\Models\VolOrderModel::where('exchange_code', $code)->exists());
        return $code;
    }

    public static function generateOrderNo(): string
    {
        return 'EX' . date('YmdHis') . mt_rand(1000, 9999);
    }

    public static function generateCheckinCode(): string
    {
        return strtoupper(substr(md5(uniqid((string) mt_rand(), true)), 0, 10));
    }

    /** 从扫码/识图结果中提取兑换码（兼容 WSS-EX: 前缀与纯码） */
    public static function normalizeExchangeCode(string $raw): string
    {
        $text = trim($raw);
        if ($text === '') {
            return '';
        }
        if (preg_match('/WSS-EX[:：=\/\s]*([A-Za-z0-9]+)/i', $text, $m)) {
            return strtoupper($m[1]);
        }
        if (preg_match('/[?&](?:code|c)=([A-Za-z0-9]+)/i', $text, $m)) {
            return strtoupper($m[1]);
        }
        $text = preg_replace('/\s+/', '', $text) ?: '';

        return strtoupper($text);
    }
}
