<?php

namespace Modules\Volunteer\Services;

use Illuminate\Http\Request;
use Modules\Volunteer\Models\VolVolunteerModel;
use Modules\Volunteer\Models\VolWxUserModel;

class VolunteerContext
{
    public static function wxUser(Request $request): ?VolWxUserModel
    {
        $wxUser = $request->attributes->get('wx_user');
        if ($wxUser) {
            return $wxUser;
        }

        $token = $request->header('token') ?: $request->bearerToken();
        if (!$token) {
            return null;
        }

        return VolWxUserModel::query()->where('api_token', $token)->first();
    }

    public static function volunteer(Request $request): ?VolVolunteerModel
    {
        $wxUser = self::wxUser($request);
        if (!$wxUser) {
            return null;
        }
        return VolVolunteerModel::where('wx_user_id', $wxUser->id)->first();
    }

    public static function requireVolunteer(Request $request): VolVolunteerModel
    {
        $volunteer = self::volunteer($request);
        if (!$volunteer) {
            throw new \RuntimeException('请先注册成为志愿者');
        }
        if ($volunteer->audit_status != 1) {
            throw new \RuntimeException('志愿者审核未通过，请等待审核');
        }
        return $volunteer;
    }
}
