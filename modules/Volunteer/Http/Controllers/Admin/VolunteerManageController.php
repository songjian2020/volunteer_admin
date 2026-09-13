<?php

namespace Modules\Volunteer\Http\Controllers\Admin;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\AnnoRoute\Attribute\DeleteRoute;
use Modules\AnnoRoute\Attribute\GetRoute;
use Modules\AnnoRoute\Attribute\PostRoute;
use Modules\AnnoRoute\Attribute\PutRoute;
use Modules\AnnoRoute\Attribute\RequestAttribute;
use Modules\Common\Http\Controllers\BaseController;
use Modules\Volunteer\Models\VolVolunteerModel;
use Modules\Volunteer\Models\VolWxUserModel;
use Modules\Volunteer\Services\PointsService;

#[RequestAttribute('/volunteer/volunteer', 'volunteer.volunteer')]
class VolunteerManageController extends BaseController
{
    protected array $quickSearchField = ['name', 'phone'];
    protected array $searchField = ['audit_status' => '=', 'name' => 'like', 'phone' => 'like'];

    #[GetRoute(authorize: 'query')]
    public function query(Request $request): JsonResponse
    {
        $pageSize = $request->input('pageSize', 10);
        $data = $this->buildSearch($request->all(), VolVolunteerModel::query())
            ->orderByDesc('id')
            ->paginate($pageSize)
            ->toArray();
        return $this->success($data);
    }

    /** 后台手动新增已通过志愿者 */
    #[PostRoute(authorize: 'create')]
    public function create(Request $request): JsonResponse
    {
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
            'wx_user_id' => 'nullable|integer|min:1',
        ]);

        if (VolVolunteerModel::where('phone', $data['phone'])->exists()) {
            return $this->error('该手机号已存在志愿者档案');
        }

        $wxUserId = !empty($data['wx_user_id']) ? (int) $data['wx_user_id'] : null;
        unset($data['wx_user_id']);
        if ($wxUserId) {
            if (!VolWxUserModel::where('id', $wxUserId)->exists()) {
                return $this->error('绑定的微信用户不存在');
            }
            if (VolVolunteerModel::where('wx_user_id', $wxUserId)->exists()) {
                return $this->error('该微信用户已绑定志愿者');
            }
        }

        $model = VolVolunteerModel::create([
            ...$data,
            'wx_user_id' => $wxUserId,
            'gender' => $data['gender'] ?? '1',
            'age' => $data['age'] ?? 0,
            'audit_status' => 1,
            'total_points' => 0,
            'total_hours' => 0,
            'activity_count' => 0,
            'star_level' => 0,
        ]);
        $model->update([
            'certificate_no' => str_pad((string) $model->id, 4, '0', STR_PAD_LEFT),
        ]);

        return $this->success(['id' => $model->id], '新增成功');
    }

    /** 审批通过/拒绝（PUT/POST 均可，审批页与列表页共用） */
    #[PutRoute(route: '/{id}/audit', authorize: 'query', where: ['id' => '[0-9]+'])]
    #[PostRoute(route: '/{id}/audit', authorize: 'query', where: ['id' => '[0-9]+'])]
    public function audit(int $id, Request $request): JsonResponse
    {
        $status = (int) $request->input('audit_status', 1);
        if (!in_array($status, [1, 2], true)) {
            return $this->error('无效的审核状态');
        }
        $model = VolVolunteerModel::find($id);
        if (!$model) {
            return $this->error('志愿者不存在');
        }
        $data = ['audit_status' => $status];
        if ($status === 1 && empty($model->certificate_no)) {
            $data['certificate_no'] = str_pad((string) $model->id, 4, '0', STR_PAD_LEFT);
        }
        $model->update($data);
        return $this->success([], $status === 1 ? '已通过' : '已拒绝');
    }

    #[PostRoute(route: '/{id}/points', authorize: 'points', where: ['id' => '[0-9]+'])]
    public function adjustPoints(int $id, Request $request): JsonResponse
    {
        $points = (int) $request->input('points', 0);
        $reason = $request->input('reason', '管理员调整积分');
        if ($points == 0) return $this->error('积分不能为0');
        try {
            PointsService::addPoints($id, $points, $reason, 'manual');
            return $this->success();
        } catch (\Throwable $e) {
            return $this->error($e->getMessage());
        }
    }

    #[DeleteRoute(route: '/{id}', authorize: 'delete', where: ['id' => '[0-9]+'])]
    public function delete(int $id): JsonResponse
    {
        VolVolunteerModel::destroy($id);
        return $this->success();
    }
}
