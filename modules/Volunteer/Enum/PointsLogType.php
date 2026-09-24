<?php

namespace Modules\Volunteer\Enum;

enum PointsLogType: string
{
    case ACTIVITY = 'activity';
    case EXCHANGE = 'exchange';
    case OFFLINE_ACTIVITY = 'offline_activity';
    case ADMIN_GRANT = 'admin_grant';
    case ADMIN_DEDUCT = 'admin_deduct';
    case OTHER = 'other';
    case MANUAL = 'manual';

    public function label(): string
    {
        return self::labels()[$this->value] ?? $this->value;
    }

    /** @return array<string, string> */
    public static function labels(): array
    {
        return [
            self::ACTIVITY->value => '活动服务',
            self::EXCHANGE->value => '积分兑换',
            self::OFFLINE_ACTIVITY->value => '社区活动',
            self::ADMIN_GRANT->value => '管理员赋分',
            self::ADMIN_DEDUCT->value => '管理员扣减',
            self::OTHER->value => '其他调整',
            self::MANUAL->value => '管理员调整（历史）',
        ];
    }

    /** @return string[] */
    public static function manualTypeValues(): array
    {
        return [
            self::OFFLINE_ACTIVITY->value,
            self::ADMIN_GRANT->value,
            self::ADMIN_DEDUCT->value,
            self::OTHER->value,
        ];
    }

    public static function labelOf(string $type): string
    {
        return self::labels()[$type] ?? ($type ?: '-');
    }

    public static function isValid(string $type): bool
    {
        return isset(self::labels()[$type]);
    }

    public static function isValidManual(string $type): bool
    {
        return in_array($type, self::manualTypeValues(), true);
    }
}
