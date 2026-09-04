<?php

namespace Modules\Volunteer\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VolVolunteerModel extends Model
{
    protected $table = 'vol_volunteer';

    protected $fillable = [
        'wx_user_id', 'name', 'phone', 'gender', 'age', 'education', 'political_status',
        'id_card', 'address', 'specialty', 'emergency_contact', 'emergency_phone',
        'audit_status', 'total_points', 'total_hours', 'activity_count', 'star_level', 'certificate_no',
    ];

    protected $casts = [
        'total_hours' => 'float',
    ];

    protected $appends = ['is_volunteer', 'star_level_text', 'audit_status_text'];

    public function wxUser(): BelongsTo
    {
        return $this->belongsTo(VolWxUserModel::class, 'wx_user_id');
    }

    public function pointsLogs(): HasMany
    {
        return $this->hasMany(VolPointsLogModel::class, 'volunteer_id');
    }

    public function getIsVolunteerAttribute(): bool
    {
        return $this->audit_status == 1;
    }

    public function getStarLevelTextAttribute(): string
    {
        return self::levelToText((int) $this->star_level);
    }

    public function getAuditStatusTextAttribute(): string
    {
        return match ((int) $this->audit_status) {
            1 => '已通过',
            2 => '已拒绝',
            default => '待审核',
        };
    }

    public static function levelToText(int $level): string
    {
        $map = [0 => '零', 1 => '一', 2 => '二', 3 => '三', 4 => '四', 5 => '五'];
        return $map[$level] ?? (string) $level;
    }

    public static function calcStarLevel(int $points): int
    {
        return \Modules\Volunteer\Services\VolunteerConfigService::calcStarLevel($points);
    }

    public static function getRank(int $volunteerId): int
    {
        $volunteer = self::find($volunteerId);
        if (!$volunteer) return 0;
        return self::query()
            ->where('audit_status', 1)
            ->where('total_points', '>', $volunteer->total_points)
            ->count() + 1;
    }
}
