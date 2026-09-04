<?php

use Database\Seeders\VolunteerSeeder;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        (new VolunteerSeeder())->syncMenus();
    }

    public function down(): void
    {
        // 菜单回滚由管理员在后台手动处理
    }
};
