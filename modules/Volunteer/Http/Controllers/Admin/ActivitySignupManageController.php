<?php

namespace Modules\Volunteer\Http\Controllers\Admin;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\AnnoRoute\Attribute\GetRoute;
use Modules\AnnoRoute\Attribute\RequestAttribute;
use Modules\Common\Http\Controllers\BaseController;
use Modules\Volunteer\Models\VolActivitySignupModel;

#[RequestAttribute('/volunteer/signup', 'volunteer.signup')]
class ActivitySignupManageController extends BaseController
{
    protected array $quickSearchField = [];
    protected array $searchField = [
        'activity_id' => '=',
        'volunteer_id' => '=',
        'status' => '=',
    ];

    #[GetRoute(authorize: 'query')]
    public function query(Request $request): JsonResponse
    {
        $pageSize = $request->input('pageSize', 10);
        $data = $this->buildSearch($request->all(), VolActivitySignupModel::with([
            'activity:id,title,type,start_time',
            'volunteer:id,name,phone',
        ]))
            ->orderByDesc('id')
            ->paginate($pageSize)
            ->toArray();
        return $this->success($data);
    }
}
