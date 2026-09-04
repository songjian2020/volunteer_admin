<?php

namespace Modules\Volunteer\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VolGoodsModel extends Model
{
    protected $table = 'vol_goods';

    protected $fillable = [
        'merchant_id', 'name', 'category', 'points', 'stock', 'image_url', 'description', 'status', 'sort',
    ];

    protected $appends = ['status_text'];

    public function merchant(): BelongsTo
    {
        return $this->belongsTo(VolMerchantModel::class, 'merchant_id');
    }

    public function getStatusTextAttribute(): string
    {
        return match ((int) $this->status) {
            1 => '已上架',
            0 => '已下架',
            default => '待审核',
        };
    }
}
