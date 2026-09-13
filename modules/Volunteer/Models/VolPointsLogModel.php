<?php

namespace Modules\Volunteer\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VolPointsLogModel extends Model
{
    protected $table = 'vol_points_log';

    protected $fillable = [
        'volunteer_id', 'type', 'reason', 'points', 'related_type', 'related_id',
    ];

    protected $appends = ['type_text'];

    public function volunteer(): BelongsTo
    {
        return $this->belongsTo(VolVolunteerModel::class, 'volunteer_id');
    }

    public function getTypeTextAttribute(): string
    {
        return match ((string) $this->type) {
            'activity' => '活动服务',
            'exchange' => '积分兑换',
            'manual' => '管理员调整',
            default => $this->type ?: '-',
        };
    }
}
