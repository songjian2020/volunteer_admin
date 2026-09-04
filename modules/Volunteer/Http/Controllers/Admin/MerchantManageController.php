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
use Modules\Volunteer\Models\VolMerchantModel;
use Modules\Volunteer\Services\GeocodeService;

#[RequestAttribute('/volunteer/merchant', 'volunteer.merchant')]
class MerchantManageController extends BaseController
{
    protected array $quickSearchField = ['name', 'account', 'phone'];
    protected array $searchField = [
        'audit_status' => '=',
        'status' => '=',
        'business_type' => '=',
        'province' => '=',
        'city' => '=',
    ];

    #[GetRoute(authorize: 'query')]
    public function query(Request $request): JsonResponse
    {
        $pageSize = $request->input('pageSize', 10);
        $data = $this->buildSearch($request->all(), VolMerchantModel::query())
            ->orderByDesc('id')
            ->paginate($pageSize)
            ->toArray();
        return $this->success($data);
    }

    #[PostRoute('/geocode', 'query')]
    public function geocode(Request $request): JsonResponse
    {
        $address = trim((string) $request->input('address', ''));
        if ($address === '') {
            return $this->error('请先填写省市区和详细地址');
        }
        $result = GeocodeService::geocode($address);
        if (!$result) {
            return $this->error('未能解析经纬度，请手动填写或检查地址');
        }
        return $this->success($result, '获取成功');
    }

    #[PostRoute(authorize: 'create')]
    public function create(Request $request): JsonResponse
    {
        $data = $this->validateData($request, true);
        $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        $data['audit_status'] = $data['audit_status'] ?? 1;
        $data['status'] = $data['status'] ?? 1;
        VolMerchantModel::create($data);
        return $this->success();
    }

    #[PutRoute(route: '/{id}', authorize: 'update', where: ['id' => '[0-9]+'])]
    public function update(int $id, Request $request): JsonResponse
    {
        $model = VolMerchantModel::find($id);
        if (!$model) {
            return $this->error('商户不存在');
        }
        $data = $this->validateData($request, false, $id);
        if (!empty($data['password'])) {
            $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        } else {
            unset($data['password']);
        }
        $model->update($data);
        return $this->success();
    }

    #[PutRoute(route: '/{id}/audit', authorize: 'audit', where: ['id' => '[0-9]+'])]
    public function audit(int $id, Request $request): JsonResponse
    {
        $model = VolMerchantModel::find($id);
        if (!$model) {
            return $this->error('商户不存在');
        }
        $model->update(['audit_status' => (int) $request->input('audit_status', 1)]);
        return $this->success();
    }

    #[DeleteRoute(route: '/{id}', authorize: 'delete', where: ['id' => '[0-9]+'])]
    public function delete(int $id): JsonResponse
    {
        VolMerchantModel::destroy($id);
        return $this->success();
    }

    protected function validateData(Request $request, bool $creating, ?int $id = null): array
    {
        $accountRule = $creating
            ? 'required|string|max:50|unique:vol_merchant,account'
            : 'required|string|max:50|unique:vol_merchant,account,' . $id;

        return $request->validate([
            'name' => 'required|string|max:100',
            'account' => $accountRule,
            'password' => $creating ? 'required|string|min:6' : 'nullable|string|min:6',
            'contact' => 'nullable|string|max:30',
            'phone' => 'nullable|string|max:20',
            'province' => 'required|string|max:50',
            'city' => 'required|string|max:50',
            'district' => 'required|string|max:50',
            'address' => 'required|string|max:300',
            'longitude' => 'required|numeric|between:-180,180',
            'latitude' => 'required|numeric|between:-90,90',
            'business_type' => 'nullable|string|max:50',
            'logo' => 'required|string|max:500',
            'qualifications' => 'nullable|array',
            'qualifications.*.type' => 'required_with:qualifications|string|max:50',
            'qualifications.*.url' => 'required_with:qualifications|string|max:500',
            'qualifications.*.number' => 'nullable|string|max:100',
            'license_no' => 'nullable|string|max:50',
            'main_business' => 'nullable|string|max:200',
            'description' => 'nullable|string',
            'audit_status' => 'nullable|integer',
            'status' => 'nullable|integer',
        ]);
    }
}
