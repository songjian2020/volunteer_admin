<?php

namespace Modules\Volunteer\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VolOrderModel extends Model
{
    protected $table = 'vol_order';

    protected $fillable = [
        'order_no', 'volunteer_id', 'goods_id', 'merchant_id', 'goods_name', 'num', 'points',
        'exchange_code', 'status', 'verify_time', 'verify_merchant_id',
    ];

    protected $appends = ['status_text'];

    public function volunteer(): BelongsTo
    {
        return $this->belongsTo(VolVolunteerModel::class, 'volunteer_id');
    }

    public function goods(): BelongsTo
    {
        return $this->belongsTo(VolGoodsModel::class, 'goods_id');
    }

    public function getStatusTextAttribute(): string
    {
        return match ((int) $this->status) {
            2 => '已核销',
            3 => '已取消',
            default => '待核销',
        };
    }
}
