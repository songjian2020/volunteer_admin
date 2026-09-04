<?php

namespace Modules\Volunteer\Http\Controllers\Admin;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\AnnoRoute\Attribute\GetRoute;
use Modules\AnnoRoute\Attribute\RequestAttribute;
use Modules\Common\Http\Controllers\BaseController;
use Modules\Volunteer\Models\VolOrderModel;

#[RequestAttribute('/volunteer/order', 'volunteer.order')]
class OrderManageController extends BaseController
{
    protected array $quickSearchField = ['order_no', 'exchange_code', 'goods_name'];
    protected array $searchField = ['status' => '='];

    #[GetRoute(authorize: 'query')]
    public function query(Request $request): JsonResponse
    {
        $pageSize = $request->input('pageSize', 10);
        $data = $this->buildSearch($request->all(), VolOrderModel::with(['volunteer:id,name,phone', 'goods:id,image_url']))
            ->orderByDesc('id')
            ->paginate($pageSize)
            ->toArray();
        return $this->success($data);
    }
}
