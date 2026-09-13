<?php

namespace Modules\Volunteer\Http\Controllers\Admin;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\AnnoRoute\Attribute\GetRoute;
use Modules\AnnoRoute\Attribute\RequestAttribute;
use Modules\Common\Http\Controllers\BaseController;
use Modules\Volunteer\Models\VolOrderModel;

#[RequestAttribute('/volunteer/verify', 'volunteer.verify')]
class MerchantVerifyManageController extends BaseController
{
    protected array $quickSearchField = [];
    protected array $searchField = [
        'order_no' => 'like',
        'goods_name' => 'like',
        'exchange_code' => 'like',
    ];

    #[GetRoute(authorize: 'query')]
    public function query(Request $request): JsonResponse
    {
        $pageSize = $request->input('pageSize', 10);
        $query = VolOrderModel::query()
            ->with([
                'volunteer:id,name,phone',
                'verifyMerchant:id,name,account,phone',
                'merchant:id,name',
            ])
            ->where('status', 2)
            ->where('verify_merchant_id', '>', 0);

        $merchantName = trim((string) $request->input('merchant_name', ''));
        if ($merchantName !== '') {
            $query->whereHas('verifyMerchant', fn ($q) => $q->where('name', 'like', '%'.$merchantName.'%'));
        }

        $volunteerName = trim((string) $request->input('volunteer_name', ''));
        if ($volunteerName !== '') {
            $query->whereHas('volunteer', fn ($q) => $q->where('name', 'like', '%'.$volunteerName.'%'));
        }

        $volunteerPhone = trim((string) $request->input('volunteer_phone', ''));
        if ($volunteerPhone !== '') {
            $query->whereHas('volunteer', fn ($q) => $q->where('phone', 'like', '%'.$volunteerPhone.'%'));
        }

        $verifyTime = $request->input('verify_time');
        if (is_array($verifyTime) && count($verifyTime) >= 2 && $verifyTime[0] && $verifyTime[1]) {
            $start = strtotime((string) $verifyTime[0].' 00:00:00');
            $end = strtotime((string) $verifyTime[1].' 23:59:59');
            if ($start && $end) {
                $query->whereBetween('verify_time', [$start, $end]);
            }
        }

        $data = $this->buildSearch($request->all(), $query)
            ->orderByDesc('verify_time')
            ->orderByDesc('id')
            ->paginate($pageSize)
            ->toArray();

        return $this->success($data);
    }
}
