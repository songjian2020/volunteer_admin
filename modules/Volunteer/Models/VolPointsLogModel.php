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

    public function volunteer(): BelongsTo
    {
        return $this->belongsTo(VolVolunteerModel::class, 'volunteer_id');
    }
}
