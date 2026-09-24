<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vol_points_log', function (Blueprint $table) {
            $table->unsignedBigInteger('operator_id')->default(0)->after('related_id')->comment('操作管理员ID');
        });
    }

    public function down(): void
    {
        Schema::table('vol_points_log', function (Blueprint $table) {
            $table->dropColumn('operator_id');
        });
    }
};
