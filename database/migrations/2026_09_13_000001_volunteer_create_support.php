<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('vol_volunteer') && Schema::hasColumn('vol_volunteer', 'wx_user_id')) {
            // 允许后台新增未绑定微信用户的志愿者
            try {
                DB::statement('ALTER TABLE `vol_volunteer` DROP INDEX `vol_volunteer_wx_user_id_unique`');
            } catch (\Throwable) {
                // 索引名不一致时忽略
            }
            DB::statement('ALTER TABLE `vol_volunteer` MODIFY `wx_user_id` BIGINT UNSIGNED NULL COMMENT \'微信用户ID，后台新增可为空\'');
            try {
                DB::statement('ALTER TABLE `vol_volunteer` ADD UNIQUE `vol_volunteer_wx_user_id_unique` (`wx_user_id`)');
            } catch (\Throwable) {
                // 唯一索引已存在时忽略
            }
        }

        if (DB::table('sys_rule')->where('key', 'volunteer.volunteer.create')->exists()) {
            return;
        }

        $parentId = DB::table('sys_rule')->where('key', 'volunteer.volunteer')->value('id');
        if (!$parentId) {
            return;
        }

        $now = now();
        $ruleId = DB::table('sys_rule')->insertGetId([
            'parent_id' => $parentId,
            'type' => 'rule',
            'key' => 'volunteer.volunteer.create',
            'name' => '新增',
            'path' => '',
            'icon' => '',
            'order' => 1,
            'local' => '',
            'status' => 1,
            'hidden' => 1,
            'link' => 0,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        if (!DB::table('sys_role_rule')->where('role_id', 1)->where('rule_id', $ruleId)->exists()) {
            DB::table('sys_role_rule')->insert(['role_id' => 1, 'rule_id' => $ruleId]);
        }
    }

    public function down(): void
    {
        $ids = DB::table('sys_rule')->where('key', 'volunteer.volunteer.create')->pluck('id');
        if ($ids->isNotEmpty()) {
            DB::table('sys_role_rule')->whereIn('rule_id', $ids)->delete();
            DB::table('sys_rule')->whereIn('id', $ids)->delete();
        }
    }
};
