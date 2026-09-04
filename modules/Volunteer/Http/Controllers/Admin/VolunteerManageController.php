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

    #[PutRoute(route: '/{id}/audit', authorize: 'audit', where: ['id' => '[0-9]+'])]
    public function audit(int $id, Request $request): JsonResponse
    {
        $status = (int) $request->input('audit_status', 1);
        $model = VolVolunteerModel::find($id);
        if (!$model) return $this->error('志愿者不存在');
        $data = ['audit_status' => $status];
        if ($status === 1 && empty($model->certificate_no)) {
            $data['certificate_no'] = str_pad((string) $model->id, 4, '0', STR_PAD_LEFT);
        }
        $model->update($data);
        return $this->success();
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
