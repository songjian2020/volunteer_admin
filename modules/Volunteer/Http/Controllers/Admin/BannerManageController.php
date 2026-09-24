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
use Modules\Volunteer\Models\VolBannerModel;

#[RequestAttribute('/volunteer/banner', 'volunteer.banner')]
class BannerManageController extends BaseController
{
    protected array $quickSearchField = ['title', 'sub'];

    protected array $searchField = [
        'status' => '=',
        'type' => '=',
        'title' => 'like',
    ];

    #[GetRoute(authorize: 'query')]
    public function query(Request $request): JsonResponse
    {
        $pageSize = $request->input('pageSize', 10);
        $data = $this->buildSearch($request->all(), VolBannerModel::query())
            ->orderByDesc('sort')
            ->orderByDesc('id')
            ->paginate($pageSize)
            ->toArray();
        return $this->success($data);
    }

    #[PostRoute(authorize: 'create')]
    public function create(Request $request): JsonResponse
    {
        VolBannerModel::create($this->validateData($request));
        return $this->success();
    }

    #[PutRoute(route: '/{id}', authorize: 'update', where: ['id' => '[0-9]+'])]
    public function update(int $id, Request $request): JsonResponse
    {
        $model = VolBannerModel::find($id);
        if (!$model) {
            return $this->error('轮播不存在');
        }
        $model->update($this->validateData($request));
        return $this->success();
    }

    #[DeleteRoute(route: '/{id}', authorize: 'delete', where: ['id' => '[0-9]+'])]
    public function delete(int $id): JsonResponse
    {
        VolBannerModel::destroy($id);
        return $this->success();
    }

    protected function validateData(Request $request): array
    {
        return $request->validate([
            'type' => 'required|integer|in:1,2',
            'title' => 'nullable|string|max:100',
            'sub' => 'nullable|string|max:200',
            'image_url' => 'required|string|max:1000',
            'link_type' => 'nullable|string|max:20',
            'link_value' => 'nullable|string|max:255',
            'sort' => 'nullable|integer',
            'status' => 'nullable|integer',
        ]);
    }
}
