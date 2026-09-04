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
use Modules\Volunteer\Models\VolActivityModel;
use Modules\Volunteer\Services\PointsService;

#[RequestAttribute('/volunteer/activity', 'volunteer.activity')]
class ActivityManageController extends BaseController
{
    protected array $quickSearchField = ['title', 'type'];
    protected array $searchField = ['status' => '=', 'type' => '='];

    #[GetRoute(authorize: 'query')]
    public function query(Request $request): JsonResponse
    {
        $pageSize = $request->input('pageSize', 10);
        $data = $this->buildSearch($request->all(), VolActivityModel::query())
            ->orderByDesc('id')
            ->paginate($pageSize)
            ->toArray();
        return $this->success($data);
    }

    #[PostRoute(authorize: 'create')]
    public function create(Request $request): JsonResponse
    {
        $this->normalizeTimeFields($request);
        $data = $this->validateData($request);
        $data['checkin_code_in'] = PointsService::generateCheckinCode();
        $data['checkin_code_out'] = PointsService::generateCheckinCode();
        VolActivityModel::create($data);
        return $this->success();
    }

    #[PutRoute(route: '/{id}', authorize: 'update', where: ['id' => '[0-9]+'])]
    public function update(int $id, Request $request): JsonResponse
    {
        $model = VolActivityModel::find($id);
        if (!$model) return $this->error('活动不存在');
        $this->normalizeTimeFields($request);
        $model->update($this->validateData($request));
        return $this->success();
    }

    #[DeleteRoute(route: '/{id}', authorize: 'delete', where: ['id' => '[0-9]+'])]
    public function delete(int $id): JsonResponse
    {
        VolActivityModel::destroy($id);
        return $this->success();
    }

    #[PostRoute(route: '/{id}/refreshCodes', authorize: 'update', where: ['id' => '[0-9]+'])]
    public function refreshCodes(int $id): JsonResponse
    {
        $model = VolActivityModel::find($id);
        if (!$model) {
            return $this->error('活动不存在');
        }
        $model->update([
            'checkin_code_in' => PointsService::generateCheckinCode(),
            'checkin_code_out' => PointsService::generateCheckinCode(),
        ]);
        return $this->success([
            'checkin_code_in' => $model->checkin_code_in,
            'checkin_code_out' => $model->checkin_code_out,
        ], '签到码已刷新');
    }

    protected function normalizeTimeFields(Request $request): void
    {
        $fields = ['signup_start_time', 'signup_end_time', 'start_time', 'end_time'];
        $merge = [];
        foreach ($fields as $field) {
            if (!$request->has($field)) {
                continue;
            }
            $value = $request->input($field);
            if ($value === null || $value === '') {
                $merge[$field] = null;
                continue;
            }
            if (is_int($value) || (is_string($value) && ctype_digit($value))) {
                $merge[$field] = (int) $value;
                continue;
            }
            if (is_numeric($value)) {
                $numeric = (int) $value;
                $merge[$field] = $numeric > 9999999999 ? (int) floor($numeric / 1000) : $numeric;
                continue;
            }
            if (is_string($value)) {
                $timestamp = strtotime($value);
                if ($timestamp !== false) {
                    $merge[$field] = $timestamp;
                }
            }
        }
        if ($merge !== []) {
            $request->merge($merge);
        }
    }

    protected function validateData(Request $request): array
    {
        return $request->validate([
            'title' => 'required|string|max:100',
            'type' => 'required|string|max:30',
            'theme' => 'nullable|string|max:100',
            'location' => 'nullable|string|max:200',
            'contact' => 'nullable|string|max:30',
            'contact_phone' => 'nullable|string|max:20',
            'points' => 'required|integer|min:0',
            'recruit_count' => 'required|integer|min:1',
            'description' => 'nullable|string',
            'cover_url' => 'nullable|string|max:500',
            'signup_start_time' => 'nullable|integer',
            'signup_end_time' => 'nullable|integer',
            'start_time' => 'nullable|integer',
            'end_time' => 'nullable|integer',
            'status' => 'nullable|integer',
            'summary' => 'nullable|string',
            'images' => 'nullable|array',
            'participant_count' => 'nullable|integer',
        ]);
    }
}
