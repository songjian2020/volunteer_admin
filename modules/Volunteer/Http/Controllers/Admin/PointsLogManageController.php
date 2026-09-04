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
    protected array $searchField = ['type' => '='];

    #[GetRoute(authorize: 'query')]
    public function query(Request $request): JsonResponse
    {
        $pageSize = $request->input('pageSize', 10);
        $data = $this->buildSearch($request->all(), VolPointsLogModel::with('volunteer:id,name,phone'))
            ->orderByDesc('id')
            ->paginate($pageSize)
            ->toArray();
        return $this->success($data);
    }
}
