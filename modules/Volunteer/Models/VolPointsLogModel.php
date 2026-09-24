<?php

namespace Modules\Volunteer\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Volunteer\Enum\PointsLogType;

class VolPointsLogModel extends Model
{
    protected $table = 'vol_points_log';

    protected $fillable = [
        'volunteer_id', 'type', 'reason', 'points', 'related_type', 'related_id', 'operator_id',
    ];

    protected $appends = ['type_text'];

    public function volunteer(): BelongsTo
    {
        return $this->belongsTo(VolVolunteerModel::class, 'volunteer_id');
    }

    public function getTypeTextAttribute(): string
    {
        return PointsLogType::labelOf((string) $this->type);
    }
}
