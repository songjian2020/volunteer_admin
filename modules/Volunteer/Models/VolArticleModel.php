<?php

namespace Modules\Volunteer\Models;

use Illuminate\Database\Eloquent\Model;

class VolArticleModel extends Model
{
    protected $table = 'vol_article';

    protected $fillable = [
        'title', 'type', 'cover_url', 'content', 'sort', 'status',
    ];
}
