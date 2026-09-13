<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Modules\Volunteer\Models\VolActivityModel;
use Modules\Volunteer\Models\VolArticleModel;
use Modules\Volunteer\Models\VolBannerModel;
use Modules\Volunteer\Models\VolGoodsModel;
use Modules\Volunteer\Models\VolMerchantModel;
use Modules\Volunteer\Models\VolShowcaseModel;
use Modules\Volunteer\Models\VolVolunteerModel;
use Modules\Volunteer\Models\VolWxUserModel;
use Modules\Volunteer\Services\PointsService;

class VolunteerSeeder extends Seeder
{
    public function run(): void
    {
        $this->syncMenus();
        $this->seedDemoData();
    }

    public static function getMenuRules(): array
    {
        return [
            [
                'type' => 'menu',
                'key' => 'volunteer.group.volunteer',
                'name' => '志愿者管�?,
                'icon' => 'TeamOutlined',
                'local' => 'menu.volunteer.group.volunteer',
                'children' => [
                    ['type' => 'route', 'key' => 'volunteer.user', 'name' => '用户管理', 'path' => '/volunteer/user', 'local' => 'menu.volunteer.user', 'children' => [
                        ['type' => 'rule', 'name' => '查询', 'key' => 'volunteer.user.query'],
                        ['type' => 'rule', 'name' => '编辑', 'key' => 'volunteer.user.update'],
                        ['type' => 'rule', 'name' => '撤销授权', 'key' => 'volunteer.user.revoke'],
                        ['type' => 'rule', 'name' => '删除', 'key' => 'volunteer.user.delete'],
                    ]],
                    ['type' => 'route', 'key' => 'volunteer.volunteer.pending', 'name' => '志愿者审�?, 'path' => '/volunteer/volunteer/pending', 'local' => 'menu.volunteer.volunteer.pending'],
                    ['type' => 'route', 'key' => 'volunteer.volunteer', 'name' => '志愿者管�?, 'path' => '/volunteer/volunteer', 'local' => 'menu.volunteer.volunteer', 'children' => [
                        ['type' => 'rule', 'name' => '查询', 'key' => 'volunteer.volunteer.query'],
                        ['type' => 'rule', 'name' => '新增', 'key' => 'volunteer.volunteer.create'],
                        ['type' => 'rule', 'name' => '审核', 'key' => 'volunteer.volunteer.audit'],
                        ['type' => 'rule', 'name' => '调整积分', 'key' => 'volunteer.volunteer.points'],
                        ['type' => 'rule', 'name' => '删除', 'key' => 'volunteer.volunteer.delete'],
                    ]],
                    ['type' => 'route', 'key' => 'volunteer.points', 'name' => '积分记录', 'path' => '/volunteer/points', 'local' => 'menu.volunteer.points', 'children' => [
                        ['type' => 'rule', 'name' => '查询', 'key' => 'volunteer.points.query'],
                    ]],
                ],
            ],
            [
                'type' => 'menu',
                'key' => 'volunteer.group.merchant',
                'name' => '商户管理',
                'icon' => 'ShopOutlined',
                'local' => 'menu.volunteer.group.merchant',
                'children' => [
                    ['type' => 'route', 'key' => 'volunteer.merchant.pending', 'name' => '商户审批', 'path' => '/volunteer/merchant/pending', 'local' => 'menu.volunteer.merchant.pending'],
                    ['type' => 'route', 'key' => 'volunteer.merchant', 'name' => '商户管理', 'path' => '/volunteer/merchant', 'local' => 'menu.volunteer.merchant', 'children' => [
                        ['type' => 'rule', 'name' => '查询', 'key' => 'volunteer.merchant.query'],
                        ['type' => 'rule', 'name' => '审核', 'key' => 'volunteer.merchant.audit'],
                        ['type' => 'rule', 'name' => '新增', 'key' => 'volunteer.merchant.create'],
                        ['type' => 'rule', 'name' => '编辑', 'key' => 'volunteer.merchant.update'],
                        ['type' => 'rule', 'name' => '删除', 'key' => 'volunteer.merchant.delete'],
                    ]],
                    ['type' => 'route', 'key' => 'volunteer.verify', 'name' => '核销查询', 'path' => '/volunteer/verify', 'local' => 'menu.volunteer.verify', 'children' => [
                        ['type' => 'rule', 'name' => '查询', 'key' => 'volunteer.verify.query'],
                    ]],
                    ['type' => 'route', 'key' => 'volunteer.goods', 'name' => '商品管理', 'path' => '/volunteer/goods', 'local' => 'menu.volunteer.goods', 'children' => [
                        ['type' => 'rule', 'name' => '查询', 'key' => 'volunteer.goods.query'],
                        ['type' => 'rule', 'name' => '新增', 'key' => 'volunteer.goods.create'],
                        ['type' => 'rule', 'name' => '编辑', 'key' => 'volunteer.goods.update'],
                        ['type' => 'rule', 'name' => '审核', 'key' => 'volunteer.goods.audit'],
                        ['type' => 'rule', 'name' => '删除', 'key' => 'volunteer.goods.delete'],
                    ]],
                ],
            ],
            [
                'type' => 'menu',
                'key' => 'volunteer.group.order',
                'name' => '订单管理',
                'icon' => 'ShoppingOutlined',
                'local' => 'menu.volunteer.group.order',
                'children' => [
                    ['type' => 'route', 'key' => 'volunteer.order', 'name' => '兑换订单', 'path' => '/volunteer/order', 'local' => 'menu.volunteer.order', 'children' => [
                        ['type' => 'rule', 'name' => '查询', 'key' => 'volunteer.order.query'],
                    ]],
                ],
            ],
            [
                'type' => 'menu',
                'key' => 'volunteer.group.activity',
                'name' => '活动管理',
                'icon' => 'CalendarOutlined',
                'local' => 'menu.volunteer.group.activity',
                'children' => [
                    ['type' => 'route', 'key' => 'volunteer.activity', 'name' => '活动管理', 'path' => '/volunteer/activity', 'local' => 'menu.volunteer.activity', 'children' => [
                        ['type' => 'rule', 'name' => '查询', 'key' => 'volunteer.activity.query'],
                        ['type' => 'rule', 'name' => '新增', 'key' => 'volunteer.activity.create'],
                        ['type' => 'rule', 'name' => '编辑', 'key' => 'volunteer.activity.update'],
                        ['type' => 'rule', 'name' => '删除', 'key' => 'volunteer.activity.delete'],
                    ]],
                    ['type' => 'route', 'key' => 'volunteer.signup', 'name' => '报名查询', 'path' => '/volunteer/signup', 'local' => 'menu.volunteer.signup', 'children' => [
                        ['type' => 'rule', 'name' => '查询', 'key' => 'volunteer.signup.query'],
                    ]],
                ],
            ],
            [
                'type' => 'menu',
                'key' => 'volunteer.group.content',
                'name' => '信息管理',
                'icon' => 'FileTextOutlined',
                'local' => 'menu.volunteer.group.content',
                'children' => [
                    ['type' => 'route', 'key' => 'volunteer.showcase', 'name' => '风采展示', 'path' => '/volunteer/showcase', 'local' => 'menu.volunteer.showcase', 'children' => [
                        ['type' => 'rule', 'name' => '查询', 'key' => 'volunteer.showcase.query'],
                        ['type' => 'rule', 'name' => '新增', 'key' => 'volunteer.showcase.create'],
                        ['type' => 'rule', 'name' => '编辑', 'key' => 'volunteer.showcase.update'],
                        ['type' => 'rule', 'name' => '删除', 'key' => 'volunteer.showcase.delete'],
                    ]],
                    ['type' => 'route', 'key' => 'volunteer.article', 'name' => '文章公告', 'path' => '/volunteer/article', 'local' => 'menu.volunteer.article', 'children' => [
                        ['type' => 'rule', 'name' => '查询', 'key' => 'volunteer.article.query'],
                        ['type' => 'rule', 'name' => '新增', 'key' => 'volunteer.article.create'],
                        ['type' => 'rule', 'name' => '编辑', 'key' => 'volunteer.article.update'],
                        ['type' => 'rule', 'name' => '删除', 'key' => 'volunteer.article.delete'],
                    ]],
                ],
            ],
            [
                'type' => 'menu',
                'key' => 'volunteer.group.setting',
                'name' => '平台设置',
                'icon' => 'SettingOutlined',
                'local' => 'menu.volunteer.group.setting',
                'children' => [
                    ['type' => 'route', 'key' => 'volunteer.banner', 'name' => '轮播管理', 'path' => '/volunteer/banner', 'local' => 'menu.volunteer.banner', 'children' => [
                        ['type' => 'rule', 'name' => '查询', 'key' => 'volunteer.banner.query'],
                        ['type' => 'rule', 'name' => '新增', 'key' => 'volunteer.banner.create'],
                        ['type' => 'rule', 'name' => '编辑', 'key' => 'volunteer.banner.update'],
                        ['type' => 'rule', 'name' => '删除', 'key' => 'volunteer.banner.delete'],
                    ]],
                ],
            ],
        ];
    }

    public function syncMenus(): void
    {
        $ruleIds = DB::table('sys_rule')->where('key', 'like', 'volunteer%')->orWhere('key', 'volunteer')->pluck('id');
        if ($ruleIds->isNotEmpty()) {
            DB::table('sys_role_rule')->whereIn('rule_id', $ruleIds)->delete();
            DB::table('sys_rule')->whereIn('id', $ruleIds)->delete();
        }

        $seeder = new SysUserSeeder();
        $seeder->insertRules(self::getMenuRules());

        $newRuleIds = DB::table('sys_rule')->where('key', 'like', 'volunteer%')->pluck('id');
        foreach ($newRuleIds as $ruleId) {
            if (!DB::table('sys_role_rule')->where('role_id', 1)->where('rule_id', $ruleId)->exists()) {
                DB::table('sys_role_rule')->insert(['role_id' => 1, 'rule_id' => $ruleId]);
            }
        }
    }

    protected function seedDemoData(): void
    {
        if (VolBannerModel::count() > 0) {
            return;
        }

        VolBannerModel::insert([
            ['title' => '万寿山社区积分小程序', 'sub' => '微网�?积分�?· 志愿服务 · 共建和谐社区', 'image_url' => 'https://aka.doubaocdn.com/s/LVslgatU2E', 'sort' => 3, 'status' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['title' => '参与志愿服务 赚取积分奖励', 'sub' => '邻里守望 · 矛盾调解 · 助残帮困', 'image_url' => 'https://aka.doubaocdn.com/s/DnIo8pzvVV', 'sort' => 2, 'status' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['title' => '积分商城好礼兑换', 'sub' => '生活用品 · 便民服务 · 惊喜不断', 'image_url' => 'https://aka.doubaocdn.com/s/6BNkNHPrxz', 'sort' => 1, 'status' => 1, 'created_at' => now(), 'updated_at' => now()],
        ]);

        VolArticleModel::insert([
            ['title' => '万寿山社区积分制简�?, 'type' => 1, 'content' => '万寿山社区推�?微网�?积分�?志愿服务积分兑换机制，探索线上登�?线上兑换模式，实现积分记录、查询、兑换全流程便捷化�?, 'sort' => 1, 'status' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['title' => '积分商城兑换说明', 'type' => 2, 'content' => '居民参与志愿活动获得积分后，可在积分商城兑换商品。兑换成功后生成兑换码，凭兑换码到指定商户或社区服务中心领取�?, 'sort' => 1, 'status' => 1, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $merchant = VolMerchantModel::create([
            'name' => '万寿山社区便民超�?,
            'account' => 'merchant',
            'password' => password_hash('123456', PASSWORD_DEFAULT),
            'contact' => '李经�?,
            'phone' => '13800138000',
            'address' => '万寿山社区商业街1�?,
            'business_type' => '超市便利�?,
            'audit_status' => 1,
            'status' => 1,
        ]);

        VolGoodsModel::insert([
            ['merchant_id' => 0, 'name' => '洗衣液1kg装', 'category' => '清洁用品', 'points' => 200, 'stock' => 50, 'image_url' => 'https://aka.doubaocdn.com/s/GnaYceQxn5', 'description' => '深层洁净洗衣液', 'status' => 1, 'sort' => 4, 'created_at' => now(), 'updated_at' => now()],
            ['merchant_id' => 0, 'name' => '抽纸10包装', 'category' => '日用百货', 'points' => 150, 'stock' => 100, 'image_url' => 'https://aka.doubaocdn.com/s/wKuMvPaXc2', 'description' => '原生木浆抽纸', 'status' => 1, 'sort' => 3, 'created_at' => now(), 'updated_at' => now()],
            ['merchant_id' => $merchant->id, 'name' => '食用油5L', 'category' => '粮油食品', 'points' => 500, 'stock' => 20, 'image_url' => 'https://aka.doubaocdn.com/s/YqNBy3JJD2', 'description' => '非转基因食用油', 'status' => 1, 'sort' => 2, 'created_at' => now(), 'updated_at' => now()],
            ['merchant_id' => $merchant->id, 'name' => '大米10kg', 'category' => '粮油食品', 'points' => 400, 'stock' => 30, 'image_url' => 'https://aka.doubaocdn.com/s/EmwphJWLaz', 'description' => '东北长粒香大米', 'status' => 1, 'sort' => 1, 'created_at' => now(), 'updated_at' => now()],
        ]);

        VolActivityModel::create([
            'title' => '邻里守望互助活动',
            'type' => '邻里守望',
            'theme' => '关爱独居老人，传递社区温�?,
            'location' => '万寿山社区广�?,
            'contact' => '张主�?,
            'contact_phone' => '023-67644671',
            'points' => 50,
            'recruit_count' => 20,
            'signup_count' => 0,
            'description' => '上门看望社区独居老人，帮助打扫卫生、代购生活用品，陪老人聊天开展心理慰藉�?,
            'cover_url' => 'https://aka.doubaocdn.com/s/OaQ4O7pInu',
            'signup_start_time' => strtotime('-3 days'),
            'signup_end_time' => strtotime('+7 days'),
            'start_time' => strtotime('+10 days 09:00'),
            'end_time' => strtotime('+10 days 12:00'),
            'checkin_code_in' => PointsService::generateCheckinCode(),
            'checkin_code_out' => PointsService::generateCheckinCode(),
            'status' => 1,
        ]);

        VolShowcaseModel::insert([
            ['title' => '邻里守望互助活动风采', 'type' => '1', 'cover_url' => 'https://aka.doubaocdn.com/s/GpDRYNELE4', 'content' => '志愿者帮助独居老人打扫卫生', 'status' => 1, 'sort' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['title' => '矛盾调解志愿服务', 'type' => '1', 'cover_url' => 'https://aka.doubaocdn.com/s/ppWnO1qMjK', 'content' => '成功化解多起邻里纠纷', 'status' => 1, 'sort' => 2, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $wxUser = VolWxUserModel::create([
            'openid' => 'dev_openid_' . md5('dev_mode_fixed_code'),
            'nickname' => '张志愿�?,
            'avatar' => '',
            'api_token' => 'demo_volunteer_token',
            'token_expire_at' => now()->addDays(30),
        ]);

        $volunteer = VolVolunteerModel::create([
            'wx_user_id' => $wxUser->id,
            'name' => '张志愿�?,
            'phone' => '13800138001',
            'gender' => '1',
            'age' => 35,
            'address' => '万寿山社�?�?单元',
            'audit_status' => 1,
            'total_points' => 0,
            'total_hours' => 28,
            'activity_count' => 12,
            'star_level' => 0,
            'certificate_no' => '0001',
        ]);

        PointsService::addPoints($volunteer->id, 350, '初始演示积分', 'manual');
    }
}

