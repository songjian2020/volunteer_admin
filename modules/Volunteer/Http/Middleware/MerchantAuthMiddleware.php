<?php

namespace Modules\Volunteer\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\Volunteer\Models\VolMerchantModel;
use Symfony\Component\HttpFoundation\Response;

class MerchantAuthMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->header('token') ?: $request->bearerToken();
        if (!$token) {
            return response()->json(['success' => false, 'msg' => '请登录后操作', 'code' => 401], 200);
        }

        $merchant = VolMerchantModel::query()->where('api_token', $token)->where('status', 1)->first();
        if (!$merchant || $merchant->audit_status != 1) {
            return response()->json(['success' => false, 'msg' => '请登录后操作', 'code' => 401], 200);
        }
        if ($merchant->token_expire_at && $merchant->token_expire_at->isPast()) {
            return response()->json(['success' => false, 'msg' => '登录已过期', 'code' => 401], 200);
        }

        $request->attributes->set('merchant', $merchant);
        $request->attributes->set('merchant_id', $merchant->id);

        return $next($request);
    }
}
