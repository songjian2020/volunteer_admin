<?php

namespace Modules\Volunteer\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\AnnoRoute\Attribute\GetRoute;
use Modules\AnnoRoute\Attribute\PostRoute;
use Modules\AnnoRoute\Attribute\RequestAttribute;
use Modules\Common\Http\Controllers\BaseController;
use Modules\Volunteer\Http\Middleware\MiniProgramAuthMiddleware;
use Modules\Volunteer\Models\VolActivityModel;
use Modules\Volunteer\Models\VolActivitySignupModel;
use Modules\Volunteer\Services\PointsService;
use Modules\Volunteer\Services\VolunteerConfigService;
use Modules\Volunteer\Services\VolunteerContext;

#[RequestAttribute('/api/volunteer.activity')]
class ActivityController extends BaseController
{
    protected array $searchField = [
        'type' => '=',
        'status' => '=',
        'title' => 'like',
    ];

    #[GetRoute('/types', false)]
    public function types(): JsonResponse
    {
        return $this->success([
            ['value' => '邻里守望', 'label' => '邻里守望'],
            ['value' => '矛盾调解', 'label' => '矛盾调解'],
            ['value' => '助残帮困', 'label' => '助残帮困'],
            ['value' => '心理辅导', 'label' => '心理辅导'],
            ['value' => '突发事件处置', 'label' => '突发事件处置'],
        ]);
    }

    #[GetRoute('/list', false)]
    public function list(Request $request): JsonResponse
    {
        $pageSize = (int) ($request->input('pageSize', 10));
        $current = (int) ($request->input('current', 1));
        $query = VolActivityModel::query()->where('status', 1);
        $data = $this->buildSearch($request->all(), $query)
            ->orderByDesc('start_time')
            ->paginate($pageSize, ['*'], 'page', $current)
            ->toArray();
        return $this->success($data);
    }

    #[GetRoute('/detail', false)]
    public function detail(Request $request): JsonResponse
    {
        $id = (int) $request->input('id', 0);
        $activity = VolActivityModel::find($id);
        if (!$activity) {
            return $this->error('活动不存在');
        }

        $detail = $activity->toArray();
        $detail['is_signed'] = false;
        $detail['checkin_status'] = 0;
        $detail['checkin_status_text'] = '未签到';
        $detail['checkin_start_time'] = '';
        $detail['checkin_end_time'] = '';
        $detail['service_hours'] = 0;
        $detail['earned_points'] = 0;

        $token = $request->header('token');
        if ($token) {
            $volunteer = VolunteerContext::volunteer($request);
            if ($volunteer) {
                $signup = VolActivitySignupModel::where('activity_id', $id)
                    ->where('volunteer_id', $volunteer->id)
                    ->first();
                if ($signup) {
                    $detail['is_signed'] = true;
                    $detail['checkin_status'] = $signup->status - 1;
                    $detail['checkin_status_text'] = $signup->status_text;
                    $detail['checkin_start_time'] = $signup->checkin_start_time;
                    $detail['checkin_end_time'] = $signup->checkin_end_time;
                    $detail['service_hours'] = $signup->service_hours;
                    $detail['earned_points'] = $signup->earned_points;
                }
            }
        }

        return $this->success($detail);
    }

    #[PostRoute('/signup', false, MiniProgramAuthMiddleware::class)]
    public function signup(Request $request): JsonResponse
    {
        try {
            $volunteer = VolunteerContext::requireVolunteer($request);
            $id = (int) $request->input('id', 0);
            $activity = VolActivityModel::find($id);
            if (!$activity || $activity->status != 1) {
                return $this->error('活动不存在或已结束');
            }

            $now = time();
            if ($activity->signup_start_time && $now < $activity->signup_start_time) {
                return $this->error('报名尚未开始');
            }
            if ($activity->signup_end_time && $now > $activity->signup_end_time) {
                return $this->error('报名已结束');
            }
            if ($activity->signup_count >= $activity->recruit_count) {
                return $this->error('报名人数已满');
            }

            $exists = VolActivitySignupModel::where('activity_id', $id)
                ->where('volunteer_id', $volunteer->id)
                ->exists();
            if ($exists) {
                return $this->error('您已报名该活动');
            }

            DB::transaction(function () use ($activity, $volunteer, $id) {
                VolActivitySignupModel::create([
                    'activity_id' => $id,
                    'volunteer_id' => $volunteer->id,
                    'status' => 1,
                ]);
                $activity->increment('signup_count');
            });

            return $this->success([], '报名成功');
        } catch (\Throwable $e) {
            return $this->error($e->getMessage());
        }
    }

    #[PostRoute('/checkin', false, MiniProgramAuthMiddleware::class)]
    public function checkin(Request $request): JsonResponse
    {
        try {
            $volunteer = VolunteerContext::requireVolunteer($request);
            $activityId = (int) $request->input('activity_id', 0);
            $code = $request->input('code', '');
            $type = $request->input('type', 'in');

            $activity = VolActivityModel::find($activityId);
            if (!$activity) {
                return $this->error('活动不存在');
            }

            $signup = VolActivitySignupModel::where('activity_id', $activityId)
                ->where('volunteer_id', $volunteer->id)
                ->first();
            if (!$signup) {
                return $this->error('您未报名该活动');
            }

            if ($type === 'in') {
                if ($code === '' || $code !== $activity->checkin_code_in) {
                    return $this->error('入场签到码不正确，请扫描活动入场二维码');
                }
                if ($signup->checkin_start_time > 0) {
                    return $this->error('您已入场签到');
                }
                $signup->update([
                    'status' => 2,
                    'checkin_start_time' => time(),
                ]);
                return $this->success(['checkin_status' => 1, 'checkin_status_text' => '服务中'], '入场签到成功');
            }

            if ($signup->checkin_start_time <= 0) {
                return $this->error('请先入场签到');
            }
            if ($code === '' || $code !== $activity->checkin_code_out) {
                return $this->error('离场签到码不正确，请扫描活动离场二维码');
            }
            if ($signup->status == 3) {
                return $this->error('您已完成离场签到');
            }

            $endTime = time();
            $hours = round(max(0.5, ($endTime - $signup->checkin_start_time) / 3600), 1);
            $points = VolunteerConfigService::settlePoints(
                (int) $activity->points,
                $hours,
                (int) $activity->start_time,
                (int) $activity->end_time
            );

            DB::transaction(function () use ($signup, $endTime, $hours, $points, $volunteer, $activity) {
                $signup->update([
                    'status' => 3,
                    'checkin_end_time' => $endTime,
                    'service_hours' => $hours,
                    'earned_points' => $points,
                ]);
                $volunteer->increment('activity_count');
                $volunteer->increment('total_hours', $hours);
                $activity->increment('participant_count');
                PointsService::addPoints(
                    $volunteer->id,
                    $points,
                    '参与' . $activity->title . '（服务' . $hours . '小时）',
                    'activity',
                    'activity',
                    $activity->id
                );
            });

            return $this->success([
                'service_hours' => $hours,
                'earned_points' => $points,
                'checkin_status' => 2,
                'checkin_status_text' => '已完成',
                'certificate_ready' => true,
            ], '离场签到成功，志愿服务证已生成');
        } catch (\Throwable $e) {
            return $this->error($e->getMessage());
        }
    }
}
