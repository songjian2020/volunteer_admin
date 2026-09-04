<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\SystemTool\Services\SysSiteConfigService;

return new class extends Migration
{
    public function up(): void
    {
        // values 原字段过短，无法存 JSON 配置
        if (Schema::hasTable('sys_site_config_items')) {
            DB::statement('ALTER TABLE `sys_site_config_items` MODIFY COLUMN `values` TEXT NULL');
        }

        $date = now();
        $groupId = DB::table('sys_site_config_group')->where('key', 'volunteer')->value('id');
        if (!$groupId) {
            return;
        }

        $defaultFields = [
            ['key' => 'name', 'label' => '姓名', 'required' => true, 'type' => 'text'],
            ['key' => 'phone', 'label' => '电话', 'required' => true, 'type' => 'number'],
            ['key' => 'gender', 'label' => '性别', 'required' => true, 'type' => 'gender'],
            ['key' => 'age', 'label' => '年龄', 'required' => false, 'type' => 'number'],
            ['key' => 'address', 'label' => '住址', 'required' => false, 'type' => 'text'],
            ['key' => 'specialty', 'label' => '特长', 'required' => false, 'type' => 'text'],
            ['key' => 'emergency_contact', 'label' => '紧急联系人', 'required' => false, 'type' => 'text'],
            ['key' => 'emergency_phone', 'label' => '紧急联系电话', 'required' => false, 'type' => 'number'],
        ];

        $defaultStarRules = [
            ['level' => 1, 'points' => 300, 'name' => '一星志愿者'],
            ['level' => 2, 'points' => 600, 'name' => '二星志愿者'],
            ['level' => 3, 'points' => 1000, 'name' => '三星志愿者'],
            ['level' => 4, 'points' => 1500, 'name' => '四星志愿者'],
            ['level' => 5, 'points' => 2000, 'name' => '五星志愿者'],
        ];

        $items = [
            [
                'key' => 'register_fields',
                'title' => '注册表单字段',
                'describe' => 'JSON：key/label/required/type(text|number|gender)',
                'values' => json_encode($defaultFields, JSON_UNESCAPED_UNICODE),
                'type' => 'TextArea',
                'options' => '',
                'sort' => 3,
            ],
            [
                'key' => 'star_rules',
                'title' => '星级评定规则',
                'describe' => 'JSON：level/points/name',
                'values' => json_encode($defaultStarRules, JSON_UNESCAPED_UNICODE),
                'type' => 'TextArea',
                'options' => '',
                'sort' => 4,
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
            DB::table('sys_site_config_items')
                ->where('group_id', $groupId)
                ->whereIn('key', ['register_fields', 'star_rules'])
                ->delete();
        }
    }
};
