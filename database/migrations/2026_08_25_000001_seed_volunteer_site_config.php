<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Modules\SystemTool\Services\SysSiteConfigService;

return new class extends Migration
{
    public function up(): void
    {
        $date = now();

        $groupId = DB::table('sys_site_config_group')->where('key', 'volunteer')->value('id');
        if (!$groupId) {
            $groupId = DB::table('sys_site_config_group')->insertGetId([
                'title' => '志愿服务',
                'key' => 'volunteer',
                'remark' => '志愿者注册审核、积分结算等业务配置',
                'created_at' => $date,
                'updated_at' => $date,
            ]);
        }

        $items = [
            [
                'key' => 'need_audit',
                'title' => '志愿者注册需审核',
                'describe' => '开启后，居民注册志愿者需后台审批通过才能报名活动；关闭则注册后立即生效',
                'values' => '1',
                'type' => 'Switch',
                'options' => '',
                'sort' => 0,
            ],
            [
                'key' => 'points_settle_mode',
                'title' => '积分结算方式',
                'describe' => 'fixed=按活动固定积分；hourly=按实际服务时长相对计划时长比例结算',
                'values' => 'hourly',
                'type' => 'Radio',
                'options' => json_encode([
                    ['label' => '按时长比例结算', 'value' => 'hourly'],
                    ['label' => '按活动固定积分', 'value' => 'fixed'],
                ], JSON_UNESCAPED_UNICODE),
                'sort' => 1,
            ],
            [
                'key' => 'agreement_text',
                'title' => '志愿者承诺书',
                'describe' => '小程序注册页展示的承诺书内容',
                'values' => "我愿意成为一名光荣的志愿者。\n我承诺：尽己所能，不计报酬，帮助他人，服务社会，践行志愿精神，传播先进文化，为社会进步贡献力量！",
                'type' => 'TextArea',
                'options' => '',
                'sort' => 2,
            ],
        ];

        foreach ($items as $item) {
            $exists = DB::table('sys_site_config_items')
                ->where('group_id', $groupId)
                ->where('key', $item['key'])
                ->exists();
            if ($exists) {
                continue;
            }
            DB::table('sys_site_config_items')->insert(array_merge($item, [
                'group_id' => $groupId,
                'created_at' => $date,
                'updated_at' => $date,
            ]));
        }

        SysSiteConfigService::refreshSiteConfig();
    }

    public function down(): void
    {
        $groupId = DB::table('sys_site_config_group')->where('key', 'volunteer')->value('id');
        if ($groupId) {
            DB::table('sys_site_config_items')->where('group_id', $groupId)->delete();
            DB::table('sys_site_config_group')->where('id', $groupId)->delete();
        }
    }
};
