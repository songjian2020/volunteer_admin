<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Modules\SystemTool\Services\SysSiteConfigService;

return new class extends Migration
{
    public function up(): void
    {
        $date = now();
        $groupId = DB::table('sys_site_config_group')->where('key', 'oss')->value('id');
        if (! $groupId) {
            $groupId = DB::table('sys_site_config_group')->insertGetId([
                'title' => 'OSS 存储',
                'key' => 'oss',
                'remark' => '阿里云 OSS 图片访问优化',
                'created_at' => $date,
                'updated_at' => $date,
            ]);
        }

        $items = [
            [
                'key' => 'image_process_enabled',
                'title' => '启用图片处理参数',
                'describe' => '开启后 OSS 图片 URL 将追加 x-oss-process 以加速列表/缩略图加载',
                'values' => 'true',
                'type' => 'Switch',
                'options' => '',
                'sort' => 0,
            ],
            [
                'key' => 'image_process_params',
                'title' => '列表/缩略图处理参数',
                'describe' => '不含 x-oss-process= 前缀，如 image/resize,w_300/quality,q_75/format,webp',
                'values' => 'image/resize,w_300/quality,q_75/format,webp',
                'type' => 'TextArea',
                'options' => '',
                'sort' => 1,
            ],
            [
                'key' => 'image_process_detail',
                'title' => '详情页处理参数',
                'describe' => '详情页专用；留空则详情页使用原图。示例：image/resize,w_1200/quality,q_85/format,webp',
                'values' => '',
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
        $groupId = DB::table('sys_site_config_group')->where('key', 'oss')->value('id');
        if ($groupId) {
            DB::table('sys_site_config_items')->where('group_id', $groupId)->delete();
            DB::table('sys_site_config_group')->where('id', $groupId)->delete();
        }

        SysSiteConfigService::refreshSiteConfig();
    }
};
