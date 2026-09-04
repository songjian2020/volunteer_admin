<?php

namespace Modules\Volunteer\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VolActivitySignupModel extends Model
{
    protected $table = 'vol_activity_signup';

    protected $fillable = [
        'activity_id', 'volunteer_id', 'status', 'checkin_start_time', 'checkin_end_time',
        'service_hours', 'earned_points',
    ];

    protected $casts = [
        'service_hours' => 'float',
    ];

    protected $appends = ['status_text'];

    public function activity(): BelongsTo
    {
        return $this->belongsTo(VolActivityModel::class, 'activity_id');
    }

    public function volunteer(): BelongsTo
    {
        return $this->belongsTo(VolVolunteerModel::class, 'volunteer_id');
    }

    public function getStatusTextAttribute(): string
    {
        return match ((int) $this->status) {
            2 => '进行中',
            3 => '已完成',
            default => '已报名',
        };
    }
}
