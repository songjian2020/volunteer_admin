<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('vol_article') && !Schema::hasColumn('vol_article', 'cover_url')) {
            Schema::table('vol_article', function (Blueprint $table) {
                $table->string('cover_url', 500)->default('')->after('type')->comment('缩略图');
            });
        }

        if (Schema::hasTable('vol_showcase')) {
            // 富文本内容扩为 longtext（避免依赖 doctrine/dbal）
            DB::statement('ALTER TABLE `vol_showcase` MODIFY COLUMN `content` LONGTEXT NULL');
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('vol_article') && Schema::hasColumn('vol_article', 'cover_url')) {
            Schema::table('vol_article', function (Blueprint $table) {
                $table->dropColumn('cover_url');
            });
        }
    }
};
