<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::table('sys_rule')->where('key', 'volunteer.verify')->exists()) {
            return;
        }

        $parentId = DB::table('sys_rule')->where('key', 'volunteer.group.merchant')->value('id');
        if (!$parentId) {
            return;
        }

        DB::table('sys_rule')->where('parent_id', $parentId)->where('order', '>=', 2)->increment('order');

        $now = now();
        $routeId = DB::table('sys_rule')->insertGetId([
            'parent_id' => $parentId,
            'type' => 'route',
            'key' => 'volunteer.verify',
            'name' => '核销查询',
            'path' => '/volunteer/verify',
            'icon' => '',
            'order' => 2,
            'local' => 'menu.volunteer.verify',
            'status' => 1,
            'hidden' => 1,
            'link' => 0,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $ruleId = DB::table('sys_rule')->insertGetId([
            'parent_id' => $routeId,
            'type' => 'rule',
            'key' => 'volunteer.verify.query',
            'name' => '查询',
            'path' => '',
            'icon' => '',
            'order' => 0,
            'local' => '',
            'status' => 1,
            'hidden' => 1,
            'link' => 0,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        foreach ([$routeId, $ruleId] as $id) {
            if (!DB::table('sys_role_rule')->where('role_id', 1)->where('rule_id', $id)->exists()) {
                DB::table('sys_role_rule')->insert(['role_id' => 1, 'rule_id' => $id]);
            }
        }
    }

    public function down(): void
    {
        $ids = DB::table('sys_rule')
            ->whereIn('key', ['volunteer.verify', 'volunteer.verify.query'])
            ->pluck('id');
        if ($ids->isEmpty()) {
            return;
        }
        DB::table('sys_role_rule')->whereIn('rule_id', $ids)->delete();
        DB::table('sys_rule')->whereIn('id', $ids)->delete();
    }
};
