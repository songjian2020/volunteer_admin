<?php

namespace Modules\Volunteer\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\Volunteer\Models\VolWxUserModel;
use Symfony\Component\HttpFoundation\Response;

class MiniProgramAuthMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->header('token') ?: $request->bearerToken();
        if (!$token) {
            return response()->json(['success' => false, 'msg' => '请登录后操作', 'code' => 401], 200);
        }

        $wxUser = VolWxUserModel::query()->where('api_token', $token)->first();
        if (!$wxUser || ($wxUser->token_expire_at && $wxUser->token_expire_at->isPast())) {
            return response()->json(['success' => false, 'msg' => '请登录后操作', 'code' => 401], 200);
        }

        $request->attributes->set('wx_user', $wxUser);
        $request->attributes->set('wx_user_id', $wxUser->id);

        return $next($request);
    }
}
