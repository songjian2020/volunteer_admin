<?php

namespace Modules\Volunteer\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\AnnoRoute\Attribute\GetRoute;
use Modules\AnnoRoute\Attribute\RequestAttribute;
use Modules\Common\Http\Controllers\BaseController;
use Modules\Volunteer\Models\VolShowcaseModel;

#[RequestAttribute('/api/volunteer.showcase')]
class ShowcaseController extends BaseController
{
    #[GetRoute('/list', false)]
    public function list(Request $request): JsonResponse
    {
        $pageSize = (int) ($request->input('pageSize', 10));
        $current = (int) ($request->input('current', 1));
        $type = $request->input('type', '');

        $query = VolShowcaseModel::query()->where('status', 1);
        if ($type !== '') {
            $query->where('type', $type);
        }

        $data = $query->orderByDesc('sort')
            ->orderByDesc('id')
            ->paginate($pageSize, ['*'], 'page', $current)
            ->toArray();

        return $this->success($data);
    }

    #[GetRoute('/detail', false)]
    public function detail(Request $request): JsonResponse
    {
        $id = (int) $request->input('id', 0);
        $showcase = VolShowcaseModel::where('status', 1)->find($id);
        if (!$showcase) {
            return $this->error('内容不存在');
        }
        return $this->success($showcase);
    }
}
