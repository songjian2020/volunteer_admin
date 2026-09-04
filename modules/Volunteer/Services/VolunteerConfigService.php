<?php

namespace Modules\Volunteer\Services;

/**
 * 志愿服务网站配置
 */
class VolunteerConfigService
{
    public static function needAudit(): bool
    {
        $value = site_config('volunteer.need_audit', '1');
        if (is_bool($value)) {
            return $value;
        }
        return in_array(strtolower((string) $value), ['1', 'true', 'on', 'yes'], true);
    }

    /** 积分结算方式：fixed=按活动固定积分，hourly=按时长比例结算 */
    public static function pointsSettleMode(): string
    {
        $mode = (string) site_config('volunteer.points_settle_mode', 'hourly');
        return in_array($mode, ['fixed', 'hourly'], true) ? $mode : 'hourly';
    }

    public static function agreementText(): string
    {
        $text = (string) site_config('volunteer.agreement_text', '');
        return $text !== '' ? $text : "我愿意成为一名光荣的志愿者。\n我承诺：尽己所能，不计报酬，帮助他人，服务社会，践行志愿精神，传播先进文化，为社会进步贡献力量！";
    }

    /**
     * 默认注册字段
     */
    public static function defaultRegisterFields(): array
    {
        return [
            ['key' => 'name', 'label' => '姓名', 'required' => true, 'type' => 'text'],
            ['key' => 'phone', 'label' => '电话', 'required' => true, 'type' => 'number'],
            ['key' => 'gender', 'label' => '性别', 'required' => true, 'type' => 'gender'],
            ['key' => 'age', 'label' => '年龄', 'required' => false, 'type' => 'number'],
            ['key' => 'address', 'label' => '住址', 'required' => false, 'type' => 'text'],
            ['key' => 'specialty', 'label' => '特长', 'required' => false, 'type' => 'text'],
            ['key' => 'emergency_contact', 'label' => '紧急联系人', 'required' => false, 'type' => 'text'],
            ['key' => 'emergency_phone', 'label' => '紧急联系电话', 'required' => false, 'type' => 'number'],
        ];
    }

    /**
     * 注册表单字段（可后台配置）
     */
    public static function registerFields(): array
    {
        $raw = site_config('volunteer.register_fields', '');
        $fields = self::parseJsonList($raw);
        if (empty($fields)) {
            return self::defaultRegisterFields();
        }

        $allowed = [
            'name', 'phone', 'gender', 'age', 'education', 'political_status',
            'id_card', 'address', 'specialty', 'emergency_contact', 'emergency_phone',
        ];
        $result = [];
        foreach ($fields as $item) {
            if (!is_array($item) || empty($item['key'])) {
                continue;
            }
            $key = (string) $item['key'];
            if (!in_array($key, $allowed, true)) {
                continue;
            }
            $type = (string) ($item['type'] ?? 'text');
            if (!in_array($type, ['text', 'number', 'gender'], true)) {
                $type = $key === 'gender' ? 'gender' : ($key === 'phone' || $key === 'age' || $key === 'emergency_phone' ? 'number' : 'text');
            }
            $result[] = [
                'key' => $key,
                'label' => (string) ($item['label'] ?? $key),
                'required' => !empty($item['required']),
                'type' => $type,
            ];
        }

        return $result ?: self::defaultRegisterFields();
    }

    public static function defaultStarRules(): array
    {
        return [
            ['level' => 1, 'points' => 300, 'name' => '一星志愿者'],
            ['level' => 2, 'points' => 600, 'name' => '二星志愿者'],
            ['level' => 3, 'points' => 1000, 'name' => '三星志愿者'],
            ['level' => 4, 'points' => 1500, 'name' => '四星志愿者'],
            ['level' => 5, 'points' => 2000, 'name' => '五星志愿者'],
        ];
    }

    /**
     * 星级规则（按 points 升序）
     */
    public static function starRules(): array
    {
        $raw = site_config('volunteer.star_rules', '');
        $rules = self::parseJsonList($raw);
        if (empty($rules)) {
            $rules = self::defaultStarRules();
        }

        $normalized = [];
        foreach ($rules as $item) {
            if (!is_array($item)) {
                continue;
            }
            $level = (int) ($item['level'] ?? 0);
            $points = (int) ($item['points'] ?? 0);
            if ($level < 1 || $points < 1) {
                continue;
            }
            $normalized[] = [
                'level' => $level,
                'points' => $points,
                'name' => (string) ($item['name'] ?? ($level . '星志愿者')),
            ];
        }

        if (empty($normalized)) {
            $normalized = self::defaultStarRules();
        }

        usort($normalized, fn ($a, $b) => $a['points'] <=> $b['points']);
        return $normalized;
    }

    public static function calcStarLevel(int $points): int
    {
        $level = 0;
        foreach (self::starRules() as $rule) {
            if ($points >= (int) $rule['points']) {
                $level = (int) $rule['level'];
            }
        }
        return $level;
    }

    /**
     * 小程序注册表单配置
     */
    public static function registerConfig(): array
    {
        return [
            'need_audit' => self::needAudit(),
            'agreement_text' => self::agreementText(),
            'fields' => self::registerFields(),
        ];
    }

    /**
     * 按服务时长结算积分
     */
    public static function settlePoints(int $activityPoints, float $hours, int $startTime, int $endTime): int
    {
        if (self::pointsSettleMode() === 'fixed') {
            return max(0, $activityPoints);
        }

        $plannedHours = 1.0;
        if ($startTime > 0 && $endTime > $startTime) {
            $plannedHours = max(0.5, ($endTime - $startTime) / 3600);
        }

        $ratio = min(1.5, max(0.2, $hours / $plannedHours));
        return max(1, (int) round($activityPoints * $ratio));
    }

    private static function parseJsonList(mixed $raw): array
    {
        if (is_array($raw)) {
            return $raw;
        }
        $text = trim((string) $raw);
        if ($text === '') {
            return [];
        }
        $decoded = json_decode($text, true);
        return is_array($decoded) ? $decoded : [];
    }
}
