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
class VolunteerController extends BaseController
{
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

    #[PostRoute('/updateProfile', false, MiniProgramAuthMiddleware::class)]
    public function updateProfile(Request $request): JsonResponse
    {
        $wxUser = VolunteerContext::wxUser($request);
        if (!$wxUser) {
            return $this->error('请登录后操作');
        }
        $data = $request->validate([
            'nickname' => 'nullable|string|max:50',
            'avatar' => 'nullable|string|max:500',
        ]);
        $wxUser->update(array_filter($data, fn ($v) => $v !== null && $v !== ''));
        return $this->success([
            'nickname' => $wxUser->nickname,
            'avatar' => $wxUser->avatar,
        ], '资料已更新');
    }

    #[PostRoute('/uploadAvatar', false, MiniProgramAuthMiddleware::class)]
    public function uploadAvatar(Request $request): JsonResponse
    {
        $wxUser = VolunteerContext::wxUser($request);
        if (!$wxUser) {
            return $this->error('请登录后操作');
        }
        $request->validate([
            'file' => 'required|file|max:5120|mimes:jpg,jpeg,png,gif,webp',
        ]);
        try {
            $service = new \Modules\SystemTool\Services\SysFileService();
            $result = $service->upload($request->file('file'), 0, 20, (int) $wxUser->id);
            $url = $result['file_url'] ?? $result['preview_url'] ?? '';
            if ($url !== '') {
                $wxUser->update(['avatar' => $url]);
            }
            return $this->success(['url' => $url, 'avatar' => $url], '上传成功');
        } catch (\Throwable $e) {
            return $this->error($e->getMessage() ?: '上传失败');
        }
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
                'avatar' => $wxUser->avatar ?? '',
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
        $info['avatar'] = $wxUser->avatar ?? '';
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

        $status = $request->input('status', '');
        $query = VolActivitySignupModel::with('activity:id,title,location,start_time,points')
            ->where('volunteer_id', $volunteer->id)
            ->orderByDesc('id');

        if ($status !== '') {
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
