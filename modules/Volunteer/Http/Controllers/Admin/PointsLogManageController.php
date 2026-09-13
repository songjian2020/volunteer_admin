<?php

namespace Modules\Volunteer\Http\Controllers\Admin;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\AnnoRoute\Attribute\GetRoute;
use Modules\AnnoRoute\Attribute\RequestAttribute;
use Modules\Common\Http\Controllers\BaseController;
use Modules\Volunteer\Models\VolPointsLogModel;

#[RequestAttribute('/volunteer/points', 'volunteer.points')]
class PointsLogManageController extends BaseController
{
    protected array $quickSearchField = [];
    protected array $searchField = [
        'type' => '=',
        'created_at' => 'betweenDate',
    ];

    #[GetRoute(authorize: 'query')]
    public function query(Request $request): JsonResponse
    {
        $pageSize = $request->input('pageSize', 10);
        $query = VolPointsLogModel::with('volunteer:id,name,phone');
        $name = trim((string) $request->input('volunteer_name', ''));
        $phone = trim((string) $request->input('volunteer_phone', ''));
        if ($name !== '') {
            $query->whereHas('volunteer', fn ($q) => $q->where('name', 'like', '%'.$name.'%'));
        }
        if ($phone !== '') {
            $query->whereHas('volunteer', fn ($q) => $q->where('phone', 'like', '%'.$phone.'%'));
        }
        $data = $this->buildSearch($request->all(), $query)
            ->orderByDesc('id')
            ->paginate($pageSize)
            ->toArray();
        return $this->success($data);
    }
}
