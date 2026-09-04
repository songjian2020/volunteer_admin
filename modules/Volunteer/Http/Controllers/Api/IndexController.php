<?php

namespace Modules\Volunteer\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\AnnoRoute\Attribute\GetRoute;
use Modules\AnnoRoute\Attribute\RequestAttribute;
use Modules\Common\Http\Controllers\BaseController;
use Modules\Volunteer\Models\VolActivityModel;
use Modules\Volunteer\Models\VolArticleModel;
use Modules\Volunteer\Models\VolBannerModel;
use Modules\Volunteer\Models\VolGoodsModel;
use Modules\Volunteer\Models\VolMerchantModel;
use Modules\Volunteer\Models\VolShowcaseModel;
use Modules\Volunteer\Models\VolVolunteerModel;
use Modules\Volunteer\Services\GeocodeService;

#[RequestAttribute('/api/volunteer.index')]
class IndexController extends BaseController
{
    #[GetRoute('/index', false)]
    public function index(): JsonResponse
    {
        $banners = VolBannerModel::query()
            ->where('status', 1)
            ->where('type', 1)
            ->orderByDesc('sort')
            ->orderByDesc('id')
            ->get(['id', 'title', 'sub', 'image_url', 'type', 'link_type', 'link_value']);

        $ads = VolBannerModel::query()
            ->where('status', 1)
            ->where('type', 2)
            ->orderByDesc('sort')
            ->orderByDesc('id')
            ->limit(10)
            ->get(['id', 'title', 'sub', 'image_url', 'type', 'link_type', 'link_value']);

        $activities = VolActivityModel::query()
            ->where('status', 1)
            ->orderByDesc('start_time')
            ->limit(5)
            ->get();

        $goods = VolGoodsModel::query()
            ->where('status', 1)
            ->where('stock', '>', 0)
            ->orderByDesc('sort')
            ->limit(8)
            ->get(['id', 'name', 'category', 'points', 'stock', 'image_url']);

        $announcements = VolArticleModel::query()
            ->where('type', 3)
            ->where('status', 1)
            ->orderByDesc('sort')
            ->orderByDesc('id')
            ->limit(8)
            ->get(['id', 'title']);

        $merchants = VolMerchantModel::query()
            ->where('status', 1)
            ->where('audit_status', 1)
            ->orderByDesc('id')
            ->limit(12)
            ->get(['id', 'name', 'logo', 'business_type', 'address', 'district', 'main_business']);

        $intro = VolArticleModel::query()
            ->where('type', 1)
            ->where('status', 1)
            ->orderByDesc('sort')
            ->first(['id', 'title', 'type']);

        $showcase = VolShowcaseModel::query()
            ->where('status', 1)
            ->orderByDesc('sort')
            ->orderByDesc('id')
            ->limit(6)
            ->get(['id', 'title', 'type', 'cover_url', 'content']);

        $volunteerCount = VolVolunteerModel::query()->where('audit_status', 1)->count();
        $activityCount = VolActivityModel::query()->whereIn('status', [1, 2])->count();
        $totalHours = (float) VolVolunteerModel::query()->where('audit_status', 1)->sum('total_hours');
        $hoursText = $totalHours >= 1000
            ? ((int) floor($totalHours / 100) * 100) . '+'
            : (string) (int) round($totalHours);

        return $this->success([
            'banners' => $banners,
            'ads' => $ads,
            'announcements' => $announcements,
            'activities' => $activities,
            'goods' => $goods,
            'merchants' => $merchants,
            'intro' => $intro,
            'showcase' => $showcase,
            'stats' => [
                'volunteer_count' => $volunteerCount,
                'activity_count' => $activityCount,
                'total_hours' => round($totalHours, 1),
                'total_hours_text' => $hoursText,
            ],
        ]);
    }

    #[GetRoute('/geocode', false)]
    public function geocode(Request $request): JsonResponse
    {
        $address = trim((string) $request->input('address', ''));
        if ($address === '') {
            return $this->error('地址不能为空');
        }
        $result = GeocodeService::geocode($address);
        if (!$result) {
            return $this->error('地址解析失败，请复制地址后在地图中搜索');
        }
        return $this->success($result);
    }

    #[GetRoute('/article', false)]
    public function article(Request $request): JsonResponse
    {
        $id = (int) $request->input('id', 0);
        $type = (int) $request->input('type', 1);

        $query = VolArticleModel::query()->where('status', 1);
        if ($id > 0) {
            $article = $query->find($id);
        } else {
            $article = $query->where('type', $type)->orderByDesc('sort')->first();
        }

        if (!$article) {
            return $this->success([
                'id' => 0,
                'title' => $type == 2 ? '积分商城兑换说明' : '万寿山社区积分制简介',
                'content' => '暂无内容，请联系社区管理员配置。',
            ]);
        }

        return $this->success($article);
    }

    #[GetRoute('/announcements', false)]
    public function announcements(Request $request): JsonResponse
    {
        $pageSize = (int) ($request->input('pageSize', 10));
        $current = (int) ($request->input('current', 1));

        $data = VolArticleModel::query()
            ->where('type', 3)
            ->where('status', 1)
            ->orderByDesc('sort')
            ->orderByDesc('id')
            ->paginate($pageSize, ['*'], 'page', $current)
            ->toArray();

        return $this->success($data);
    }
}
