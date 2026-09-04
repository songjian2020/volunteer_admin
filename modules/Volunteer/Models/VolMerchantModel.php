<?php

namespace Modules\Volunteer\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VolMerchantModel extends Model
{
    protected $table = 'vol_merchant';

    protected $hidden = ['password', 'api_token'];

    protected $fillable = [
        'name', 'account', 'password', 'contact', 'phone',
        'province', 'city', 'district', 'address',
        'longitude', 'latitude',
        'business_type', 'license_no', 'main_business', 'logo', 'qualifications',
        'description', 'audit_status', 'status',
        'total_verify_count', 'total_points', 'api_token', 'token_expire_at',
    ];

    protected $casts = [
        'token_expire_at' => 'datetime',
        'longitude' => 'float',
        'latitude' => 'float',
        'qualifications' => 'array',
    ];

    protected $appends = ['audit_status_text', 'full_address', 'region'];

    public function goods(): HasMany
    {
        return $this->hasMany(VolGoodsModel::class, 'merchant_id');
    }

    public function getAuditStatusTextAttribute(): string
    {
        return match ((int) $this->audit_status) {
            1 => '已通过',
            2 => '已拒绝',
            default => '待审核',
        };
    }

    public function getFullAddressAttribute(): string
    {
        return trim(($this->province ?? '') . ($this->city ?? '') . ($this->district ?? '') . ($this->address ?? ''));
    }

    public function getRegionAttribute(): array
    {
        return array_values(array_filter([
            $this->province ?: null,
            $this->city ?: null,
            $this->district ?: null,
        ]));
    }

    /** Haversine 距离（公里） */
    public static function distanceKm(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earth = 6371;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;
        return round($earth * 2 * atan2(sqrt($a), sqrt(1 - $a)), 2);
    }
}
