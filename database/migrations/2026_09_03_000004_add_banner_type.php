<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('vol_banner') && !Schema::hasColumn('vol_banner', 'type')) {
            Schema::table('vol_banner', function (Blueprint $table) {
                $table->unsignedTinyInteger('type')->default(1)->after('id')->comment('1幻灯片 2广告');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('vol_banner') && Schema::hasColumn('vol_banner', 'type')) {
            Schema::table('vol_banner', function (Blueprint $table) {
                $table->dropColumn('type');
            });
        }
    }
};
