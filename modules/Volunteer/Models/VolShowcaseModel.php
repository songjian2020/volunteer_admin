<?php

namespace Modules\Volunteer\Models;

use Illuminate\Database\Eloquent\Model;

class VolShowcaseModel extends Model
{
    protected $table = 'vol_showcase';

    protected $fillable = [
        'title', 'type', 'cover_url', 'content', 'images', 'status', 'sort',
    ];

    protected $casts = [
        'images' => 'array',
    ];
}
