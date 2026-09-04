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
use Modules\Volunteer\Models\VolShowcaseModel;

#[RequestAttribute('/volunteer/showcase', 'volunteer.showcase')]
class ShowcaseManageController extends BaseController
{
    protected array $quickSearchField = ['title'];

    protected array $searchField = [
        'type' => '=',
        'status' => '=',
        'title' => 'like',
    ];

    #[GetRoute(authorize: 'query')]
    public function query(Request $request): JsonResponse
    {
        $pageSize = $request->input('pageSize', 10);
        $data = $this->buildSearch($request->all(), VolShowcaseModel::query())
            ->orderByDesc('sort')
            ->paginate($pageSize)
            ->toArray();
        return $this->success($data);
    }

    #[PostRoute(authorize: 'create')]
    public function create(Request $request): JsonResponse
    {
        VolShowcaseModel::create($this->validateData($request));
        return $this->success();
    }

    #[PutRoute(route: '/{id}', authorize: 'update', where: ['id' => '[0-9]+'])]
    public function update(int $id, Request $request): JsonResponse
    {
        $model = VolShowcaseModel::find($id);
        if (!$model) return $this->error('记录不存在');
        $model->update($this->validateData($request));
        return $this->success();
    }

    #[DeleteRoute(route: '/{id}', authorize: 'delete', where: ['id' => '[0-9]+'])]
    public function delete(int $id): JsonResponse
    {
        VolShowcaseModel::destroy($id);
        return $this->success();
    }

    protected function validateData(Request $request): array
    {
        return $request->validate([
            'title' => 'required|string|max:100',
            'type' => 'nullable|string|max:10',
            'cover_url' => 'nullable|string|max:500',
            'content' => 'nullable|string',
            'images' => 'nullable|array',
            'status' => 'nullable|integer',
            'sort' => 'nullable|integer',
        ]);
    }
}
