<?php

namespace Modules\Volunteer\Http\Controllers\Admin;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\AnnoRoute\Attribute\GetRoute;
use Modules\AnnoRoute\Attribute\PostRoute;
use Modules\AnnoRoute\Attribute\RequestAttribute;
use Modules\Common\Http\Controllers\BaseController;
use Modules\Volunteer\Models\VolMerchantModel;
use Modules\Volunteer\Models\VolOrderModel;

#[RequestAttribute('/volunteer/order', 'volunteer.order')]
class OrderManageController extends BaseController
{
    protected array $quickSearchField = [];
    protected array $searchField = [
        'order_no' => 'like',
        'goods_name' => 'like',
        'exchange_code' => 'like',
        'status' => '=',
    ];

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

    #[GetRoute(route: '/{id}', authorize: 'query', where: ['id' => '[0-9]+'])]
    public function show(int $id): JsonResponse
    {
        $order = VolOrderModel::with(['volunteer:id,name,phone', 'goods:id,image_url,name'])->find($id);
        if (!$order) {
            return $this->error('订单不存在');
        }
        return $this->success($order);
    }

    #[PostRoute(route: '/{id}/verify', authorize: 'query', where: ['id' => '[0-9]+'])]
    public function verify(int $id): JsonResponse
    {
        $order = VolOrderModel::find($id);
        if (!$order) {
            return $this->error('订单不存在');
        }
        if ((int) $order->status === 2) {
            return $this->error('该订单已核销');
        }
        if ((int) $order->status === 3) {
            return $this->error('该订单已取消');
        }
        DB::transaction(function () use ($order) {
            $order->update([
                'status' => 2,
                'verify_time' => time(),
            ]);
            if ((int) $order->merchant_id > 0) {
                VolMerchantModel::where('id', $order->merchant_id)->increment('total_verify_count');
                VolMerchantModel::where('id', $order->merchant_id)->increment('total_points', (int) $order->points);
            }
        });
        return $this->success([], '核销成功');
    }
}
