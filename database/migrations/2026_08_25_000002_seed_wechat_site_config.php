<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Modules\SystemTool\Services\SysSiteConfigService;

return new class extends Migration
{
    public function up(): void
    {
        $date = now();

        $groupId = DB::table('sys_site_config_group')->where('key', 'wechat')->value('id');
        if (!$groupId) {
            $groupId = DB::table('sys_site_config_group')->insertGetId([
                'title' => '微信小程序',
                'key' => 'wechat',
                'remark' => '小程序授权登录相关配置（AppID / AppSecret）',
                'created_at' => $date,
                'updated_at' => $date,
            ]);
        }

        $items = [
            [
                'key' => 'app_id',
                'title' => '小程序 AppID',
                'describe' => '微信公众平台 → 开发管理 → 开发设置 中的 AppID（小程序ID）',
                'values' => '',
                'type' => 'Input',
                'options' => '',
                'sort' => 0,
            ],
            [
                'key' => 'app_secret',
                'title' => '小程序 AppSecret',
                'describe' => '微信公众平台 → 开发管理 → 开发设置 中的 AppSecret（小程序密钥），请妥善保管',
                'values' => '',
                'type' => 'Input',
                'options' => '',
                'sort' => 1,
            ],
            [
                'key' => 'enable_dev_login',
                'title' => '开发模式登录',
                'describe' => '开启后，未配置 AppID 或开发者工具调试时允许使用模拟登录（正式环境请关闭）',
                'values' => '1',
                'type' => 'Switch',
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
        $groupId = DB::table('sys_site_config_group')->where('key', 'wechat')->value('id');
        if ($groupId) {
            DB::table('sys_site_config_items')->where('group_id', $groupId)->delete();
            DB::table('sys_site_config_group')->where('id', $groupId)->delete();
        }
    }
};
