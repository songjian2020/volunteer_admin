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
use Modules\Volunteer\Models\VolWxUserModel;

#[RequestAttribute('/volunteer/user', 'volunteer.user')]
class WxUserManageController extends BaseController
{
    protected array $quickSearchField = ['openid', 'nickname'];

    #[GetRoute(authorize: 'query')]
    public function query(Request $request): JsonResponse
    {
        $pageSize = $request->input('pageSize', 10);
        $data = $this->buildSearch($request->all(), VolWxUserModel::with('volunteer:id,wx_user_id,name,phone,audit_status'))
            ->orderByDesc('id')
            ->paginate($pageSize)
            ->toArray();
        return $this->success($data);
    }

    #[PutRoute(route: '/{id}', authorize: 'update', where: ['id' => '[0-9]+'])]
    public function update(int $id, Request $request): JsonResponse
    {
        $model = VolWxUserModel::find($id);
        if (!$model) {
            return $this->error('用户不存在');
        }
        $data = $request->validate([
            'nickname' => 'nullable|string|max:50',
            'avatar' => 'nullable|string|max:255',
        ]);
        $model->update($data);
        return $this->success();
    }

    #[PostRoute(route: '/{id}/revoke', authorize: 'revoke', where: ['id' => '[0-9]+'])]
    public function revoke(int $id): JsonResponse
    {
        $model = VolWxUserModel::find($id);
        if (!$model) {
            return $this->error('用户不存在');
        }
        $model->update([
            'api_token' => '',
            'token_expire_at' => null,
        ]);
        return $this->success();
    }

    #[DeleteRoute(route: '/{id}', authorize: 'delete', where: ['id' => '[0-9]+'])]
    public function delete(int $id): JsonResponse
    {
        $model = VolWxUserModel::with('volunteer')->find($id);
        if (!$model) {
            return $this->error('用户不存在');
        }
        if ($model->volunteer) {
            return $this->error('该用户已绑定志愿者信息，请先删除志愿者档案');
        }
        $model->delete();
        return $this->success();
    }
}
