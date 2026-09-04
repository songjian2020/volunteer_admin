<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('vol_goods') && !Schema::hasColumn('vol_goods', 'category')) {
            Schema::table('vol_goods', function (Blueprint $table) {
                $table->string('category', 50)->default('')->after('name')->index()->comment('商品分类');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('vol_goods') && Schema::hasColumn('vol_goods', 'category')) {
            Schema::table('vol_goods', function (Blueprint $table) {
                $table->dropColumn('category');
            });
        }
    }
};
