<?php

namespace Modules\Volunteer\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\AnnoRoute\Attribute\GetRoute;
use Modules\AnnoRoute\Attribute\PostRoute;
use Modules\AnnoRoute\Attribute\RequestAttribute;
use Modules\Common\Http\Controllers\BaseController;
use Modules\Volunteer\Http\Middleware\MiniProgramAuthMiddleware;
use Modules\Volunteer\Models\VolActivitySignupModel;
use Modules\Volunteer\Models\VolPointsLogModel;
use Modules\Volunteer\Models\VolVolunteerModel;
use Modules\Volunteer\Services\VolunteerConfigService;
use Modules\Volunteer\Services\VolunteerContext;
use Modules\Volunteer\Services\WxAuthService;

#[RequestAttribute('/api/volunteer.volunteer')]
/** 小程序志愿者 / 微信用户相关接口 */
class VolunteerController extends BaseController
{
    /** 微信 code 登录，可选写入昵称头像 */
    #[PostRoute('/login', false)]
    public function login(Request $request): JsonResponse
    {
        $code = trim((string) $request->input('code', ''));
        $nickname = trim((string) $request->input('nickname', ''));
        $avatar = trim((string) $request->input('avatar', ''));
        try {
            $service = new WxAuthService();
            $data = $service->loginByCode($code, [
                'nickname' => $nickname,
                'avatar' => $avatar,
            ]);
            return $this->success($data, '登录成功');
        } catch (\Throwable $e) {
            return $this->error($e->getMessage());
        }
    }

    /** 更新微信昵称 / 头像 URL */
    #[PostRoute('/updateProfile', false, MiniProgramAuthMiddleware::class)]
    public function updateProfile(Request $request): JsonResponse
    {
        $wxUser = VolunteerContext::wxUser($request);
        if (!$wxUser) {
            return $this->error('请登录后操作');
        }
        $data = $request->validate([
            'nickname' => 'nullable|string|max:50',
            'avatar' => 'nullable|string|max:1000',
        ]);
        $wxUser->update(array_filter($data, fn ($v) => $v !== null && $v !== ''));
        return $this->success([
            'nickname' => $wxUser->nickname,
            'avatar' => $this->normalizeAvatarUrl($wxUser->avatar),
        ], '资料已更新');
    }

    /**
     * 保存微信头像（独立落盘，不走通用文件服务 / MIME guesser）
     * 支持 multipart 文件 或 avatar_base64
     */
    #[PostRoute('/uploadAvatar', false, MiniProgramAuthMiddleware::class)]
    public function uploadAvatar(Request $request): JsonResponse
    {
        $wxUser = VolunteerContext::wxUser($request);
        if (!$wxUser) {
            return $this->error('请登录后操作');
        }

        $binary = $this->readAvatarBinary($request);
        if ($binary === null || $binary === '') {
            return $this->error('未收到头像数据');
        }
        if (strlen($binary) < 32) {
            return $this->error('头像文件无效');
        }
        if (strlen($binary) > 5 * 1024 * 1024) {
            return $this->error('头像不能超过 5MB');
        }

        $ext = $this->detectImageExt($binary);
        $rel = 'avatar/' . date('Ymd') . '/' . uniqid('av_', true) . '.' . $ext;

        try {
            \Illuminate\Support\Facades\Storage::disk('local')->put($rel, $binary);
            $url = $this->normalizeAvatarUrl(public_storage_url($rel));
            if ($url === '') {
                return $this->error('头像保存失败');
            }
            $wxUser->update(['avatar' => $url]);
            return $this->success(['url' => $url, 'avatar' => $url], '上传成功');
        } catch (\Throwable $e) {
            return $this->error('头像保存失败，请重试');
        }
    }

    /** 从 multipart 或 base64 读取原始字节，禁止调用 guessExtension / getMimeType */
    private function readAvatarBinary(Request $request): ?string
    {
        $b64 = trim((string) $request->input('avatar_base64', ''));
        if ($b64 !== '') {
            if (str_contains($b64, ',')) {
                $b64 = substr($b64, strpos($b64, ',') + 1);
            }
            $decoded = base64_decode($b64, true);
            return $decoded === false ? null : $decoded;
        }

        if (!$request->hasFile('file')) {
            return null;
        }
        $tmp = $request->file('file')->getPathname();
        if (!$tmp || !is_readable($tmp)) {
            return null;
        }
        $data = file_get_contents($tmp);
        return $data === false ? null : $data;
    }

    /** 按文件头识别图片扩展名，默认 jpg */
    private function detectImageExt(string $binary): string
    {
        if (str_starts_with($binary, "\x89PNG\r\n\x1a\n")) {
            return 'png';
        }
        if (str_starts_with($binary, 'GIF87a') || str_starts_with($binary, 'GIF89a')) {
            return 'gif';
        }
        if (str_starts_with($binary, "RIFF") && str_contains(substr($binary, 8, 4), 'WEBP')) {
            return 'webp';
        }
        return 'jpg';
    }

    /** 相对路径补全为绝对 URL，便于小程序 image 加载 */
    private function normalizeAvatarUrl(?string $url): string
    {
        $url = trim((string) $url);
        if ($url === '') {
            return '';
        }
        if (preg_match('#^https?://(127\.0\.0\.1|localhost)(:\d+)?#i', $url)) {
            $url = preg_replace('#^https?://(127\.0\.0\.1|localhost)(:\d+)?#i', '', $url) ?: '';
        }
        if (preg_match('#^https?://#i', $url)) {
            return $url;
        }
        return public_site_url($url);
    }

    #[GetRoute('/registerConfig', false)]
    public function registerConfig(): JsonResponse
    {
        return $this->success(VolunteerConfigService::registerConfig());
    }

    #[GetRoute('/info', false, MiniProgramAuthMiddleware::class)]
    public function info(Request $request): JsonResponse
    {
        $wxUser = VolunteerContext::wxUser($request);
        $volunteer = VolunteerContext::volunteer($request);

        if (!$volunteer) {
            return $this->success([
                'is_volunteer' => false,
                'has_applied' => false,
                'audit_status' => null,
                'nickname' => $wxUser->nickname ?? '',
                'avatar' => $this->normalizeAvatarUrl($wxUser->avatar ?? ''),
            ]);
        }

        $info = $volunteer->toArray();
        $info['is_volunteer'] = $volunteer->audit_status == 1;
        $info['has_applied'] = true;
        $info['audit_status'] = (int) $volunteer->audit_status;
        $info['audit_status_text'] = match ((int) $volunteer->audit_status) {
            1 => '已通过',
            2 => '已拒绝',
            default => '审核中',
        };
        $info['rank'] = VolVolunteerModel::getRank($volunteer->id);
        $info['nickname'] = $wxUser->nickname ?? '';
        $info['avatar'] = $this->normalizeAvatarUrl($wxUser->avatar ?? '');
        if (!empty($info['phone']) && strlen($info['phone']) >= 7) {
            $info['phone_display'] = substr_replace($info['phone'], '****', 3, 4);
        } else {
            $info['phone_display'] = $info['phone'] ?? '';
        }

        return $this->success($info);
    }

    #[PostRoute('/register', false, MiniProgramAuthMiddleware::class)]
    public function register(Request $request): JsonResponse
    {
        $wxUser = VolunteerContext::wxUser($request);
        $data = $request->validate([
            'name' => 'required|string|max:30',
            'phone' => 'required|string|max:20',
            'gender' => 'nullable|string|max:2',
            'age' => 'nullable|integer|min:0|max:120',
            'education' => 'nullable|string|max:30',
            'political_status' => 'nullable|string|max:30',
            'id_card' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:200',
            'specialty' => 'nullable|string|max:200',
            'emergency_contact' => 'nullable|string|max:30',
            'emergency_phone' => 'nullable|string|max:20',
        ]);

        $existing = VolVolunteerModel::where('wx_user_id', $wxUser->id)->first();
        if ($existing && (int) $existing->audit_status === 1) {
            return $this->error('您已是正式志愿者，如需修改资料请使用资料修改功能');
        }
        if ($existing && (int) $existing->audit_status === 0) {
            return $this->error('您的申请正在审核中，请耐心等待');
        }

        $needAudit = VolunteerConfigService::needAudit();
        $volunteer = $existing ?: new VolVolunteerModel(['wx_user_id' => $wxUser->id]);
        $volunteer->fill($data);
        $volunteer->audit_status = $needAudit ? 0 : 1;
        if (empty($volunteer->certificate_no) && !$needAudit) {
            $volunteer->certificate_no = str_pad((string) (VolVolunteerModel::max('id') + 1), 4, '0', STR_PAD_LEFT);
        }
        $volunteer->save();

        if ($needAudit) {
            return $this->success([
                'audit_status' => 0,
                'need_audit' => true,
            ], '提交成功，请等待社区管理员审核');
        }

        return $this->success([
            'audit_status' => 1,
            'need_audit' => false,
        ], '欢迎加入万寿山社区志愿服务队');
    }

    #[PostRoute('/update', false, MiniProgramAuthMiddleware::class)]
    public function update(Request $request): JsonResponse
    {
        $volunteer = VolunteerContext::volunteer($request);
        if (!$volunteer) {
            return $this->error('请先注册成为志愿者');
        }

        $data = $request->validate([
            'name' => 'required|string|max:30',
            'phone' => 'required|string|max:20',
            'gender' => 'nullable|string|max:2',
            'age' => 'nullable|integer|min:0|max:120',
            'education' => 'nullable|string|max:30',
            'political_status' => 'nullable|string|max:30',
            'id_card' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:200',
            'specialty' => 'nullable|string|max:200',
            'emergency_contact' => 'nullable|string|max:30',
            'emergency_phone' => 'nullable|string|max:20',
        ]);

        $volunteer->update($data);
        return $this->success([], '修改成功');
    }

    #[GetRoute('/points', false, MiniProgramAuthMiddleware::class)]
    public function points(Request $request): JsonResponse
    {
        $volunteer = VolunteerContext::volunteer($request);
        if (!$volunteer) {
            return $this->error('请先注册成为志愿者');
        }

        $pageSize = (int) ($request->input('pageSize', 20));
        $logs = VolPointsLogModel::where('volunteer_id', $volunteer->id)
            ->orderByDesc('id')
            ->paginate($pageSize)
            ->toArray();

        return $this->success([
            'total_points' => $volunteer->total_points,
            'rank' => VolVolunteerModel::getRank($volunteer->id),
            'activity_count' => $volunteer->activity_count,
            'total_hours' => $volunteer->total_hours,
            'logs' => $logs,
        ]);
    }

    #[GetRoute('/searchPoints', false)]
    public function searchPoints(Request $request): JsonResponse
    {
        $keyword = trim($request->input('keyword', ''));
        if ($keyword === '') {
            return $this->error('请输入姓名或电话');
        }

        $volunteer = VolVolunteerModel::query()
            ->where('audit_status', 1)
            ->where(function ($q) use ($keyword) {
                $q->where('name', 'like', "%{$keyword}%")
                    ->orWhere('phone', 'like', "%{$keyword}%");
            })
            ->first();

        if (!$volunteer) {
            return $this->error('未找到该志愿者');
        }

        return $this->success([
            'name' => $volunteer->name,
            'star_level' => $volunteer->star_level,
            'star_level_text' => $volunteer->star_level_text,
            'activity_count' => $volunteer->activity_count,
            'total_hours' => $volunteer->total_hours,
            'total_points' => $volunteer->total_points,
            'rank' => VolVolunteerModel::getRank($volunteer->id),
        ]);
    }

    #[GetRoute('/starLevel', false, MiniProgramAuthMiddleware::class)]
    public function starLevel(Request $request): JsonResponse
    {
        $volunteer = VolunteerContext::volunteer($request);
        if (!$volunteer) {
            return $this->error('请先注册成为志愿者');
        }

        $rules = VolunteerConfigService::starRules();

        $levels = [];
        foreach ($rules as $rule) {
            if ($volunteer->total_points >= $rule['points']) {
                $levels[] = [
                    'level' => $rule['level'],
                    'create_time' => date('Y-m-d'),
                ];
            }
        }

        return $this->success([
            'current_level' => $volunteer->star_level,
            'total_points' => $volunteer->total_points,
            'levels' => $levels,
            'rules' => $rules,
        ]);
    }

    #[GetRoute('/certificates', false, MiniProgramAuthMiddleware::class)]
    public function certificates(Request $request): JsonResponse
    {
        $volunteer = VolunteerContext::volunteer($request);
        if (!$volunteer || $volunteer->audit_status != 1) {
            return $this->success([]);
        }

        // 按流程图：服务结束后（离场签到完成）生成志愿服务证
        $completed = VolActivitySignupModel::with('activity:id,title,start_time')
            ->where('volunteer_id', $volunteer->id)
            ->where('status', 3)
            ->orderByDesc('checkin_end_time')
            ->get();

        $list = $completed->map(function (VolActivitySignupModel $item) use ($volunteer) {
            $issueTime = $item->checkin_end_time ?: strtotime($item->updated_at);
            $certNo = sprintf(
                'WS%s%s%04d',
                date('Ymd', $issueTime),
                str_pad((string) $item->activity_id, 4, '0', STR_PAD_LEFT),
                $item->id
            );
            return [
                'id' => $item->id,
                'name' => $volunteer->name,
                'cert_no' => $certNo,
                'certificate_no' => $certNo,
                'star_level' => $volunteer->star_level,
                'star_level_text' => $volunteer->star_level_text,
                'total_hours' => $item->service_hours,
                'service_hours' => $item->service_hours,
                'earned_points' => $item->earned_points,
                'activity_count' => 1,
                'participate_year' => date('Y', $issueTime),
                'activity_name' => $item->activity->title ?? '志愿服务活动',
                'year' => date('Y', $issueTime),
                'issue_time' => $issueTime,
            ];
        })->values();

        return $this->success($list);
    }

    #[GetRoute('/myActivities', false, MiniProgramAuthMiddleware::class)]
    public function myActivities(Request $request): JsonResponse
    {
        $volunteer = VolunteerContext::volunteer($request);
        if (!$volunteer) {
            return $this->success([]);
        }

        $status = $request->input('status');
        $query = VolActivitySignupModel::with('activity:id,title,location,start_time,points')
            ->where('volunteer_id', $volunteer->id)
            ->orderByDesc('id');

        if ($status !== null && $status !== '' && $status !== 'all') {
            $query->where('status', (int) $status);
        }

        $list = $query->get()->map(function ($item) {
            return [
                'id' => $item->id,
                'activity_id' => $item->activity_id,
                'title' => $item->activity->title ?? '',
                'location' => $item->activity->location ?? '',
                'start_time' => $item->activity->start_time ?? 0,
                'points' => $item->activity->points ?? 0,
                'status' => (string) $item->status,
                'status_text' => $item->status_text,
            ];
        });

        return $this->success($list);
    }
}
