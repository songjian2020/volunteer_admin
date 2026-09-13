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
use Modules\Volunteer\Models\VolGoodsModel;
use Modules\Volunteer\Models\VolOrderModel;
use Modules\Volunteer\Services\PointsService;
use Modules\Volunteer\Services\VolunteerContext;

#[RequestAttribute('/api/volunteer.mall')]
class MallController extends BaseController
{
    protected array $searchField = [
        'name' => 'like',
        'category' => '=',
    ];

    #[GetRoute('/goodsList', false)]
    public function goodsList(Request $request): JsonResponse
    {
        $pageSize = (int) ($request->input('pageSize', 10));
        $current = (int) ($request->input('current', 1));
        $query = VolGoodsModel::query()->where('status', 1);
        $data = $this->buildSearch($request->all(), $query)
            ->orderByDesc('sort')
            ->orderByDesc('id')
            ->paginate($pageSize, ['*'], 'page', $current)
            ->toArray();
        return $this->success($data);
    }

    #[GetRoute('/goodsDetail', false)]
    public function goodsDetail(Request $request): JsonResponse
    {
        $id = (int) $request->input('id', 0);
        $goods = VolGoodsModel::where('status', 1)->find($id);
        if (!$goods) {
            return $this->error('商品不存在或已下架');
        }
        return $this->success($goods);
    }

    #[PostRoute('/exchange', false, MiniProgramAuthMiddleware::class)]
    public function exchange(Request $request): JsonResponse
    {
        try {
            $volunteer = VolunteerContext::requireVolunteer($request);
            $id = (int) $request->input('id', 0);
            $num = max(1, (int) $request->input('num', 1));

            $goods = VolGoodsModel::where('status', 1)->find($id);
            if (!$goods) {
                return $this->error('商品不存在或已下架');
            }
            if ($goods->stock < $num) {
                return $this->error('库存不足');
            }

            $totalPoints = $goods->points * $num;
            if ($volunteer->total_points < $totalPoints) {
                return $this->error('积分不足');
            }

            $order = DB::transaction(function () use ($volunteer, $goods, $num, $totalPoints) {
                $goods->decrement('stock', $num);
                PointsService::deductPoints(
                    $volunteer->id,
                    $totalPoints,
                    '兑换' . $goods->name,
                    'exchange',
                    'goods',
                    $goods->id
                );

                return VolOrderModel::create([
                    'order_no' => PointsService::generateOrderNo(),
                    'volunteer_id' => $volunteer->id,
                    'goods_id' => $goods->id,
                    'merchant_id' => $goods->merchant_id,
                    'goods_name' => $goods->name,
                    'num' => $num,
                    'points' => $totalPoints,
                    'exchange_code' => PointsService::generateExchangeCode(),
                    'status' => 1,
                ]);
            });

            return $this->success([
                'order_id' => $order->id,
                'exchange_code' => $order->exchange_code,
                'order_no' => $order->order_no,
            ], '兑换成功');
        } catch (\Throwable $e) {
            return $this->error($e->getMessage());
        }
    }

    #[GetRoute('/myOrders', false, MiniProgramAuthMiddleware::class)]
    public function myOrders(Request $request): JsonResponse
    {
        $volunteer = VolunteerContext::volunteer($request);
        if (!$volunteer) {
            return $this->success([]);
        }

        $status = $request->input('status');
        $query = VolOrderModel::with('goods:id,image_url')
            ->where('volunteer_id', $volunteer->id)
            ->orderByDesc('id');

        if ($status !== null && $status !== '' && $status !== 'all') {
            $query->where('status', (int) $status);
        }

        $list = $query->get()->map(function ($item) {
            $row = $item->toArray();
            $row['image_url'] = $item->goods->image_url ?? '';
            $row['status'] = (string) $item->status;
            unset($row['goods']);
            return $row;
        });

        return $this->success($list);
    }

    #[GetRoute('/orderDetail', false, MiniProgramAuthMiddleware::class)]
    public function orderDetail(Request $request): JsonResponse
    {
        $volunteer = VolunteerContext::volunteer($request);
        if (!$volunteer) {
            return $this->error('请先登录');
        }
        $id = (int) $request->input('id', 0);
        $order = VolOrderModel::with('goods:id,image_url,name')
            ->where('volunteer_id', $volunteer->id)
            ->find($id);
        if (!$order) {
            return $this->error('订单不存在');
        }
        $row = $order->toArray();
        $row['image_url'] = $order->goods->image_url ?? '';
        $row['status'] = (string) $order->status;
        unset($row['goods']);
        return $this->success($row);
    }
}
