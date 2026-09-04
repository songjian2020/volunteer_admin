<?php

namespace Modules\Volunteer\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VolActivityModel extends Model
{
    protected $table = 'vol_activity';

    protected $fillable = [
        'title', 'type', 'theme', 'location', 'contact', 'contact_phone', 'points',
        'recruit_count', 'signup_count', 'description', 'cover_url',
        'signup_start_time', 'signup_end_time', 'start_time', 'end_time',
        'checkin_code_in', 'checkin_code_out', 'status', 'summary', 'images', 'participant_count',
    ];

    protected $casts = [
        'images' => 'array',
    ];

    public function signups()
    {
        return $this->hasMany(VolActivitySignupModel::class, 'activity_id');
    }
}
