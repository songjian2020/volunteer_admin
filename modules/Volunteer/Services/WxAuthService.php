<?php

namespace Modules\Volunteer\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Modules\Volunteer\Models\VolWxUserModel;
use RuntimeException;

class WxAuthService
{
    public function getAppId(): string
    {
        $fromSite = trim((string) site_config('wechat.app_id', ''));
        if ($fromSite !== '') {
            return $fromSite;
        }
        return trim((string) config('volunteer.wx_app_id', env('WX_APP_ID', '')));
    }

    public function getAppSecret(): string
    {
        $fromSite = trim((string) site_config('wechat.app_secret', ''));
        if ($fromSite !== '') {
            return $fromSite;
        }
        return trim((string) config('volunteer.wx_app_secret', env('WX_APP_SECRET', '')));
    }

    public function enableDevLogin(): bool
    {
        $value = site_config('wechat.enable_dev_login', '1');
        if (is_bool($value)) {
            return $value;
        }
        return in_array(strtolower((string) $value), ['1', 'true', 'on', 'yes'], true);
    }

    /**
     * 微信小程序登录（code2session）
     */
    public function loginByCode(string $code, array $profile = []): array
    {
        $code = trim($code);
        if ($code === '') {
            throw new RuntimeException('缺少微信登录凭证 code');
        }

        $session = $this->code2Session($code);
        $openid = $session['openid'];

        $wxUser = VolWxUserModel::query()->firstOrCreate(
            ['openid' => $openid],
            [
                'nickname' => $profile['nickname'] ?? '微信用户',
                'avatar' => $profile['avatar'] ?? '',
                'unionid' => $session['unionid'] ?? '',
            ]
        );

        $updates = [
            'api_token' => Str::random(60),
            'token_expire_at' => now()->addDays(30),
        ];
        if (!empty($session['unionid'])) {
            $updates['unionid'] = $session['unionid'];
        }
        if (!empty($profile['nickname'])) {
            $updates['nickname'] = $profile['nickname'];
        }
        if (!empty($profile['avatar'])) {
            $updates['avatar'] = $profile['avatar'];
        }
        $wxUser->update($updates);
        $wxUser->refresh();

        return [
            'token' => $wxUser->api_token,
            'openid' => $openid,
            'nickname' => $wxUser->nickname,
            'avatar' => $wxUser->avatar,
            'is_dev' => !empty($session['is_dev']),
        ];
    }

    /**
     * @return array{openid:string,unionid?:string,session_key?:string,is_dev?:bool}
     */
    protected function code2Session(string $code): array
    {
        // 开发模式固定 code（仅后台开启开发模式登录时可用）
        if ($code === 'dev_mode_fixed_code') {
            if (!$this->enableDevLogin()) {
                throw new RuntimeException('未开启开发模式登录，请使用真实微信授权');
            }
            return [
                'openid' => 'dev_openid_' . md5('dev_mode_fixed_code'),
                'unionid' => '',
                'is_dev' => true,
            ];
        }

        $appId = $this->getAppId();
        $appSecret = $this->getAppSecret();

        if ($appId === '' || $appSecret === '') {
            if ($this->enableDevLogin()) {
                Log::warning('微信 AppID/AppSecret 未配置，已使用开发模式登录');
                return [
                    'openid' => 'dev_openid_' . md5($code),
                    'unionid' => '',
                    'is_dev' => true,
                ];
            }
            throw new RuntimeException('请先在后台【网站配置 → 微信小程序】填写 AppID 和 AppSecret');
        }

        $response = Http::timeout(8)->get('https://api.weixin.qq.com/sns/jscode2session', [
            'appid' => $appId,
            'secret' => $appSecret,
            'js_code' => $code,
            'grant_type' => 'authorization_code',
        ]);

        if (!$response->ok()) {
            Log::error('微信 code2session 请求失败', ['status' => $response->status(), 'body' => $response->body()]);
            throw new RuntimeException('微信登录服务暂时不可用，请稍后重试');
        }

        $data = $response->json() ?: [];
        if (empty($data['openid'])) {
            $errcode = $data['errcode'] ?? '';
            $errmsg = $data['errmsg'] ?? 'unknown';
            Log::warning('微信 code2session 失败', $data);
            throw new RuntimeException('微信授权失败：' . $errmsg . ($errcode !== '' ? "（{$errcode}）" : ''));
        }

        return [
            'openid' => $data['openid'],
            'unionid' => $data['unionid'] ?? '',
            'session_key' => $data['session_key'] ?? '',
            'is_dev' => false,
        ];
    }
}
