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
use Modules\Volunteer\Models\VolGoodsModel;

#[RequestAttribute('/volunteer/goods', 'volunteer.goods')]
class GoodsManageController extends BaseController
{
    protected array $quickSearchField = ['name'];
    protected array $searchField = [
        'status' => '=',
        'merchant_id' => '=',
        'category' => '=',
    ];

    #[GetRoute(authorize: 'query')]
    public function query(Request $request): JsonResponse
    {
        $pageSize = $request->input('pageSize', 10);
        $data = $this->buildSearch($request->all(), VolGoodsModel::with('merchant:id,name'))
            ->orderByDesc('sort')
            ->orderByDesc('id')
            ->paginate($pageSize)
            ->toArray();
        return $this->success($data);
    }

    #[PostRoute(authorize: 'create')]
    public function create(Request $request): JsonResponse
    {
        VolGoodsModel::create($this->validateData($request));
        return $this->success();
    }

    #[PutRoute(route: '/{id}', authorize: 'update', where: ['id' => '[0-9]+'])]
    public function update(int $id, Request $request): JsonResponse
    {
        $model = VolGoodsModel::find($id);
        if (!$model) return $this->error('商品不存在');
        $model->update($this->validateData($request));
        return $this->success();
    }

    #[PutRoute(route: '/{id}/audit', authorize: 'audit', where: ['id' => '[0-9]+'])]
    public function audit(int $id, Request $request): JsonResponse
    {
        $model = VolGoodsModel::find($id);
        if (!$model) return $this->error('商品不存在');
        $model->update(['status' => (int) $request->input('status', 1)]);
        return $this->success();
    }

    #[DeleteRoute(route: '/{id}', authorize: 'delete', where: ['id' => '[0-9]+'])]
    public function delete(int $id): JsonResponse
    {
        VolGoodsModel::destroy($id);
        return $this->success();
    }

    protected function validateData(Request $request): array
    {
        return $request->validate([
            'merchant_id' => 'nullable|integer',
            'name' => 'required|string|max:100',
            'category' => 'required|string|max:50',
            'points' => 'required|integer|min:0',
            'stock' => 'required|integer|min:0',
            'image_url' => 'nullable|string|max:500',
            'description' => 'nullable|string',
            'status' => 'nullable|integer',
            'sort' => 'nullable|integer',
        ]);
    }
}
