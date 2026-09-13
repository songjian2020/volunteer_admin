<?php

namespace Modules\Volunteer\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\AnnoRoute\Attribute\GetRoute;
use Modules\AnnoRoute\Attribute\PostRoute;
use Modules\AnnoRoute\Attribute\RequestAttribute;
use Modules\Common\Http\Controllers\BaseController;
use Modules\SystemTool\Services\SysFileService;
use Modules\Volunteer\Http\Middleware\MerchantAuthMiddleware;
use Modules\Volunteer\Http\Middleware\MiniProgramAuthMiddleware;
use Modules\Volunteer\Models\VolGoodsModel;
use Modules\Volunteer\Models\VolMerchantModel;
use Modules\Volunteer\Models\VolOrderModel;
use Modules\Volunteer\Services\PointsService;
use Modules\Volunteer\Services\VolunteerContext;

#[RequestAttribute('/api/volunteer.merchant')]
class MerchantController extends BaseController
{
    #[GetRoute('/list', false)]
    public function list(Request $request): JsonResponse
    {
        $lat = (float) $request->input('latitude', 0);
        $lng = (float) $request->input('longitude', 0);
        $keyword = trim((string) $request->input('keyword', ''));

        $query = VolMerchantModel::query()
            ->where('audit_status', 1)
            ->where('status', 1)
            ->orderByDesc('id');

        if ($keyword !== '') {
            $query->where(function ($q) use ($keyword) {
                $q->where('name', 'like', "%{$keyword}%")
                    ->orWhere('business_type', 'like', "%{$keyword}%")
                    ->orWhere('address', 'like', "%{$keyword}%");
            });
        }

        $list = $query->get([
            'id', 'name', 'logo', 'business_type', 'phone', 'contact',
            'province', 'city', 'district', 'address', 'longitude', 'latitude', 'description',
        ])->map(function (VolMerchantModel $item) use ($lat, $lng) {
            $distance = null;
            $distanceText = '';
            if ($lat && $lng && $item->latitude && $item->longitude) {
                $distance = VolMerchantModel::distanceKm($lat, $lng, (float) $item->latitude, (float) $item->longitude);
                $distanceText = $distance < 1
                    ? (round($distance * 1000) . 'm')
                    : ($distance . 'km');
            }
            return [
                'id' => $item->id,
                'name' => $item->name,
                'logo' => $item->logo,
                'business_type' => $item->business_type,
                'phone' => $item->phone,
                'contact' => $item->contact,
                'full_address' => $item->full_address,
                'longitude' => $item->longitude,
                'latitude' => $item->latitude,
                'description' => $item->description,
                'distance' => $distance,
                'distance_text' => $distanceText,
            ];
        })->values();

        if ($lat && $lng) {
            $list = $list->sortBy(fn ($row) => $row['distance'] ?? 999999)->values();
        }

        return $this->success($list);
    }

    /**
     * 当前微信用户是否已是商户（按志愿者手机号匹配商户联系电话）
     */
    #[GetRoute('/checkStatus', false, MiniProgramAuthMiddleware::class)]
    public function checkStatus(Request $request): JsonResponse
    {
        $volunteer = VolunteerContext::volunteer($request);
        $phone = trim((string) ($volunteer->phone ?? ''));
        if ($phone === '') {
            return $this->success([
                'is_merchant' => false,
                'audit_status' => null,
                'merchant_name' => '',
            ]);
        }

        $merchant = VolMerchantModel::query()
            ->where('phone', $phone)
            ->orderByDesc('id')
            ->first();

        if (!$merchant) {
            return $this->success([
                'is_merchant' => false,
                'audit_status' => null,
                'merchant_name' => '',
            ]);
        }

        return $this->success([
            // 已提交过入驻（含待审/通过/拒绝）均视为“是商户”，入口显示登录
            'is_merchant' => true,
            'audit_status' => (int) $merchant->audit_status,
            'merchant_name' => $merchant->name,
            'can_login' => (int) $merchant->audit_status === 1 && (int) $merchant->status === 1,
        ]);
    }

    #[PostRoute('/login', false)]
    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'account' => 'required|string',
            'password' => 'required|string',
        ]);

        $merchant = VolMerchantModel::where('account', $data['account'])->first();
        if (!$merchant || !password_verify($data['password'], $merchant->password)) {
            return $this->error('账号或密码错误');
        }
        if ($merchant->audit_status != 1) {
            return $this->error('商户审核未通过，请等待社区审核');
        }
        if ($merchant->status != 1) {
            return $this->error('账号已被禁用');
        }

        $token = Str::random(60);
        $merchant->update([
            'api_token' => $token,
            'token_expire_at' => now()->addDays(7),
        ]);

        return $this->success([
            'token' => $token,
            'merchant' => [
                'id' => $merchant->id,
                'name' => $merchant->name,
                'business_type' => $merchant->business_type,
            ],
        ], '登录成功');
    }

    #[PostRoute('/register', false)]
    public function register(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:100',
            'contact' => 'required|string|max:30',
            'phone' => 'required|string|max:20',
            'province' => 'nullable|string|max:50',
            'city' => 'nullable|string|max:50',
            'district' => 'nullable|string|max:50',
            'address' => 'required|string|max:300',
            'longitude' => 'nullable|numeric',
            'latitude' => 'nullable|numeric',
            'logo' => 'nullable|string|max:500',
            'business_type' => 'required|string|max:50',
            'license_no' => 'nullable|string|max:50',
            'main_business' => 'nullable|string|max:200',
            'qualifications' => 'nullable|array',
        ]);

        $account = 'm' . date('ymd') . mt_rand(1000, 9999);
        $password = substr(md5(uniqid()), 0, 8);

        VolMerchantModel::create([
            'name' => $data['name'],
            'account' => $account,
            'password' => password_hash($password, PASSWORD_DEFAULT),
            'contact' => $data['contact'],
            'phone' => $data['phone'],
            'province' => $data['province'] ?? '',
            'city' => $data['city'] ?? '',
            'district' => $data['district'] ?? '',
            'address' => $data['address'],
            'longitude' => $data['longitude'] ?? null,
            'latitude' => $data['latitude'] ?? null,
            'logo' => $data['logo'] ?? '',
            'business_type' => $data['business_type'],
            'license_no' => $data['license_no'] ?? '',
            'main_business' => $data['main_business'] ?? '',
            'qualifications' => $data['qualifications'] ?? [],
            'audit_status' => 0,
            'status' => 1,
        ]);

        return $this->success([
            'account' => $account,
            'password' => $password,
        ], '入驻申请已提交，审核通过后将通知您登录账号');
    }

    #[GetRoute('/dashboard', false, MerchantAuthMiddleware::class)]
    public function dashboard(Request $request): JsonResponse
    {
        $merchantId = $request->attributes->get('merchant_id');
        /** @var VolMerchantModel $merchant */
        $merchant = $request->attributes->get('merchant');
        $todayStart = strtotime('today');

        $todayVerify = VolOrderModel::where('verify_merchant_id', $merchantId)
            ->where('status', 2)
            ->where('verify_time', '>=', $todayStart)
            ->count();

        $totalVerify = VolOrderModel::where('verify_merchant_id', $merchantId)
            ->where('status', 2)
            ->count();

        $totalPoints = VolOrderModel::where('verify_merchant_id', $merchantId)
            ->where('status', 2)
            ->sum('points');

        $recentRecords = VolOrderModel::with('volunteer:id,name')
            ->where('verify_merchant_id', $merchantId)
            ->where('status', 2)
            ->orderByDesc('verify_time')
            ->limit(10)
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'goods_name' => $item->goods_name,
                    'time' => $item->verify_time ? date('m-d H:i', $item->verify_time) : '',
                    'points' => $item->points,
                    'user' => $item->volunteer->name ?? '',
                ];
            });

        return $this->success([
            'merchant' => [
                'id' => $merchant->id,
                'name' => $merchant->name,
                'business_type' => $merchant->business_type,
                'contact' => $merchant->contact,
                'phone' => $merchant->phone,
            ],
            'stats' => [
                'todayVerify' => $todayVerify,
                'totalVerify' => $totalVerify,
                'totalPoints' => (int) $totalPoints,
            ],
            'recentRecords' => $recentRecords,
        ]);
    }

    #[PostRoute('/verify', false, MerchantAuthMiddleware::class)]
    public function verify(Request $request): JsonResponse
    {
        $merchantId = $request->attributes->get('merchant_id');
        $code = PointsService::normalizeExchangeCode((string) $request->input('code', ''));
        if ($code === '') {
            return $this->error('请输入兑换码');
        }

        $order = VolOrderModel::with('volunteer:id,name')
            ->whereRaw('UPPER(exchange_code) = ?', [$code])
            ->first();

        if (!$order) {
            return $this->error('兑换码无效');
        }
        if ($order->status == 2) {
            return $this->error('该兑换码已核销');
        }
        if ($order->status == 3) {
            return $this->error('该兑换码已取消');
        }
        if ($order->merchant_id > 0 && $order->merchant_id != $merchantId) {
            return $this->error('该兑换码不属于本商户');
        }

        DB::transaction(function () use ($order, $merchantId) {
            $order->update([
                'status' => 2,
                'verify_time' => time(),
                'verify_merchant_id' => $merchantId,
            ]);
            VolMerchantModel::where('id', $merchantId)->increment('total_verify_count');
            VolMerchantModel::where('id', $merchantId)->increment('total_points', $order->points);
        });

        return $this->success([
            'success' => true,
            'message' => '商品核销成功，请交付商品',
            'goods_name' => $order->goods_name,
            'num' => $order->num,
            'points' => $order->points,
            'user' => $order->volunteer->name ?? '',
            'time' => date('H:i'),
        ]);
    }

    #[GetRoute('/goods', false, MerchantAuthMiddleware::class)]
    public function goods(Request $request): JsonResponse
    {
        $merchantId = $request->attributes->get('merchant_id');
        $status = $request->input('status', '');

        $query = VolGoodsModel::where('merchant_id', $merchantId)->orderByDesc('id');
        if ($status !== '') {
            $query->where('status', (int) $status);
        }

        return $this->success($query->get());
    }

    #[GetRoute('/goods/detail', false, MerchantAuthMiddleware::class)]
    public function goodsDetail(Request $request): JsonResponse
    {
        $merchantId = $request->attributes->get('merchant_id');
        $id = (int) $request->input('id', 0);
        $goods = VolGoodsModel::where('merchant_id', $merchantId)->find($id);
        if (!$goods) {
            return $this->error('商品不存在');
        }
        return $this->success($goods);
    }

    #[PostRoute('/goods/save', false, MerchantAuthMiddleware::class)]
    public function saveGoods(Request $request): JsonResponse
    {
        $merchantId = $request->attributes->get('merchant_id');
        $data = $request->validate([
            'id' => 'nullable|integer',
            'name' => 'required|string|max:100',
            'category' => 'nullable|string|max:50',
            'points' => 'required|integer|min:1',
            'stock' => 'required|integer|min:0',
            'image_url' => 'nullable|string|max:500',
            'description' => 'nullable|string|max:1000',
        ]);

        $id = (int) ($data['id'] ?? 0);
        if ($id > 0) {
            $goods = VolGoodsModel::where('merchant_id', $merchantId)->find($id);
            if (!$goods) {
                return $this->error('商品不存在');
            }
            $goods->fill([
                'name' => $data['name'],
                'category' => $data['category'] ?? ($goods->category ?: '其他'),
                'points' => $data['points'],
                'stock' => $data['stock'],
                'image_url' => $data['image_url'] ?? '',
                'description' => $data['description'] ?? '',
            ]);
            if ((int) $goods->status === 1) {
                $goods->status = 2;
            }
            $goods->save();
            return $this->success($goods, '商品已更新，等待审核');
        }

        $goods = VolGoodsModel::create([
            'merchant_id' => $merchantId,
            'name' => $data['name'],
            'category' => $data['category'] ?? '其他',
            'points' => $data['points'],
            'stock' => $data['stock'],
            'image_url' => $data['image_url'] ?? '',
            'description' => $data['description'] ?? '',
            'status' => 2,
            'sort' => 0,
        ]);

        return $this->success($goods, '商品已提交，等待审核');
    }

    #[PostRoute('/goods/toggle', false, MerchantAuthMiddleware::class)]
    public function toggleGoods(Request $request): JsonResponse
    {
        $merchantId = $request->attributes->get('merchant_id');
        $id = (int) $request->input('id', 0);
        $goods = VolGoodsModel::where('merchant_id', $merchantId)->find($id);
        if (!$goods) {
            return $this->error('商品不存在');
        }
        if ($goods->status == 2) {
            return $this->error('商品待审核，无法操作');
        }

        $goods->status = $goods->status == 1 ? 0 : 1;
        $goods->save();

        return $this->success([], '操作成功');
    }

    #[PostRoute('/upload', false, MerchantAuthMiddleware::class)]
    public function upload(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|max:5120|mimes:jpg,jpeg,png,gif,webp',
        ]);
        $merchantId = $request->attributes->get('merchant_id');
        try {
            $service = new SysFileService();
            $result = $service->upload($request->file('file'), 0, 20, (int) $merchantId);
            return $this->success([
                'url' => $result['file_url'] ?? $result['preview_url'] ?? '',
            ], '上传成功');
        } catch (\Throwable $e) {
            return $this->error($e->getMessage() ?: '上传失败');
        }
    }

    #[GetRoute('/verifyRecords', false, MerchantAuthMiddleware::class)]
    public function verifyRecords(Request $request): JsonResponse
    {
        $merchantId = $request->attributes->get('merchant_id');
        $todayStart = strtotime('today');
        $all = (int) $request->input('all', 0);

        $query = VolOrderModel::with('volunteer:id,name')
            ->where('verify_merchant_id', $merchantId)
            ->where('status', 2);
        if (!$all) {
            $query->where('verify_time', '>=', $todayStart);
        }

        $records = $query->orderByDesc('verify_time')
            ->limit($all ? 200 : 100)
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'goods_name' => $item->goods_name,
                    'user' => $item->volunteer->name ?? '',
                    'time' => $item->verify_time ? date('Y-m-d H:i', $item->verify_time) : '',
                    'points' => $item->points,
                ];
            });

        return $this->success($records);
    }

    #[GetRoute('/settlement', false, MerchantAuthMiddleware::class)]
    public function settlement(Request $request): JsonResponse
    {
        $merchantId = $request->attributes->get('merchant_id');
        $month = trim((string) $request->input('month', date('Y-m')));
        if (!preg_match('/^\d{4}-\d{2}$/', $month)) {
            $month = date('Y-m');
        }

        $start = strtotime($month . '-01 00:00:00');
        $end = strtotime('+1 month', $start) - 1;

        $orders = VolOrderModel::with('volunteer:id,name')
            ->where('verify_merchant_id', $merchantId)
            ->where('status', 2)
            ->whereBetween('verify_time', [$start, $end])
            ->orderByDesc('verify_time')
            ->get();

        $totalPoints = (int) $orders->sum('points');
        $totalCount = $orders->count();
        $fundAmount = round($totalPoints * 0.01, 2);

        $daily = [];
        foreach ($orders as $order) {
            $day = $order->verify_time ? date('Y-m-d', $order->verify_time) : '';
            if ($day === '') {
                continue;
            }
            if (!isset($daily[$day])) {
                $daily[$day] = ['date' => $day, 'count' => 0, 'points' => 0];
            }
            $daily[$day]['count']++;
            $daily[$day]['points'] += (int) $order->points;
        }
        krsort($daily);

        $records = $orders->take(50)->map(function ($item) {
            return [
                'id' => $item->id,
                'goods_name' => $item->goods_name,
                'user' => $item->volunteer->name ?? '',
                'time' => $item->verify_time ? date('m-d H:i', $item->verify_time) : '',
                'points' => $item->points,
            ];
        })->values();

        return $this->success([
            'month' => $month,
            'summary' => [
                'total_count' => $totalCount,
                'total_points' => $totalPoints,
                'fund_amount' => $fundAmount,
            ],
            'daily' => array_values($daily),
            'records' => $records,
        ]);
    }

    #[GetRoute('/profile', false, MerchantAuthMiddleware::class)]
    public function profile(Request $request): JsonResponse
    {
        /** @var VolMerchantModel $merchant */
        $merchant = $request->attributes->get('merchant');
        return $this->success([
            'id' => $merchant->id,
            'name' => $merchant->name,
            'account' => $merchant->account,
            'contact' => $merchant->contact,
            'phone' => $merchant->phone,
            'address' => $merchant->address,
            'business_type' => $merchant->business_type,
            'license_no' => $merchant->license_no,
            'main_business' => $merchant->main_business,
            'description' => $merchant->description,
        ]);
    }

    #[PostRoute('/profile/update', false, MerchantAuthMiddleware::class)]
    public function updateMerchantProfile(Request $request): JsonResponse
    {
        /** @var VolMerchantModel $merchant */
        $merchant = $request->attributes->get('merchant');
        $data = $request->validate([
            'name' => 'required|string|max:100',
            'contact' => 'required|string|max:30',
            'phone' => 'required|string|max:20',
            'address' => 'required|string|max:200',
            'business_type' => 'nullable|string|max:50',
            'license_no' => 'nullable|string|max:50',
            'main_business' => 'nullable|string|max:200',
            'description' => 'nullable|string|max:500',
        ]);
        $merchant->update($data);
        return $this->success([], '资料已保存');
    }

    #[PostRoute('/password', false, MerchantAuthMiddleware::class)]
    public function changePassword(Request $request): JsonResponse
    {
        /** @var VolMerchantModel $merchant */
        $merchant = $request->attributes->get('merchant');
        $data = $request->validate([
            'old_password' => 'required|string',
            'new_password' => 'required|string|min:6|max:32',
        ]);
        if (!password_verify($data['old_password'], $merchant->password)) {
            return $this->error('原密码不正确');
        }
        $merchant->update([
            'password' => password_hash($data['new_password'], PASSWORD_DEFAULT),
        ]);
        return $this->success([], '密码已修改');
    }
}
