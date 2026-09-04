<?php

namespace Modules\Volunteer\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class VolWxUserModel extends Model
{
    protected $table = 'vol_wx_user';

    protected $fillable = [
        'openid', 'unionid', 'nickname', 'avatar', 'api_token', 'token_expire_at',
    ];

    protected $casts = [
        'token_expire_at' => 'datetime',
    ];

    public function volunteer(): HasOne
    {
        return $this->hasOne(VolVolunteerModel::class, 'wx_user_id');
    }
}
