<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('vol_merchant')) {
            return;
        }

        Schema::table('vol_merchant', function (Blueprint $table) {
            if (!Schema::hasColumn('vol_merchant', 'province')) {
                $table->string('province', 50)->default('')->after('phone')->comment('省');
            }
            if (!Schema::hasColumn('vol_merchant', 'city')) {
                $table->string('city', 50)->default('')->after('province')->comment('市');
            }
            if (!Schema::hasColumn('vol_merchant', 'district')) {
                $table->string('district', 50)->default('')->after('city')->comment('区/县');
            }
            if (!Schema::hasColumn('vol_merchant', 'longitude')) {
                $table->decimal('longitude', 10, 7)->nullable()->after('address')->comment('经度');
            }
            if (!Schema::hasColumn('vol_merchant', 'latitude')) {
                $table->decimal('latitude', 10, 7)->nullable()->after('longitude')->comment('纬度');
            }
            if (!Schema::hasColumn('vol_merchant', 'qualifications')) {
                $table->json('qualifications')->nullable()->after('logo')->comment('资质证件 JSON');
            }
        });

        DB::statement("ALTER TABLE `vol_merchant` MODIFY COLUMN `logo` VARCHAR(500) NOT NULL DEFAULT '' COMMENT '商户logo'");
        DB::statement("ALTER TABLE `vol_merchant` MODIFY COLUMN `address` VARCHAR(300) NOT NULL DEFAULT '' COMMENT '详细地址'");
    }

    public function down(): void
    {
        if (!Schema::hasTable('vol_merchant')) {
            return;
        }
        Schema::table('vol_merchant', function (Blueprint $table) {
            foreach (['province', 'city', 'district', 'longitude', 'latitude', 'qualifications'] as $col) {
                if (Schema::hasColumn('vol_merchant', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
