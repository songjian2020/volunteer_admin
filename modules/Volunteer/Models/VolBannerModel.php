<?php

namespace Modules\Volunteer\Models;

use Illuminate\Database\Eloquent\Model;

class VolBannerModel extends Model
{
    protected $table = 'vol_banner';

    protected $fillable = [
        'type', 'title', 'sub', 'image_url', 'link_type', 'link_value', 'sort', 'status',
    ];

    protected $appends = ['type_text'];

    public function getTypeTextAttribute(): string
    {
        return match ((int) $this->type) {
            2 => '广告',
            default => '幻灯片',
        };
    }
}
