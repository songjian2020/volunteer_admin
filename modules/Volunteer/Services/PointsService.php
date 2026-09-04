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
        int $relatedId = 0
    ): void {
        DB::transaction(function () use ($volunteerId, $points, $reason, $type, $relatedType, $relatedId) {
            $volunteer = VolVolunteerModel::lockForUpdate()->find($volunteerId);
            if (!$volunteer) {
                throw new \RuntimeException('志愿者不存在');
            }
            $volunteer->total_points = max(0, $volunteer->total_points + $points);
            $volunteer->star_level = VolVolunteerModel::calcStarLevel($volunteer->total_points);
            $volunteer->save();

            VolPointsLogModel::create([
                'volunteer_id' => $volunteerId,
                'type' => $type,
                'reason' => $reason,
                'points' => $points,
                'related_type' => $relatedType,
                'related_id' => $relatedId,
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
}
