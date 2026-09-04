<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vol_wx_user', function (Blueprint $table) {
            $table->id();
            $table->string('openid', 64)->unique()->comment('微信openid');
            $table->string('unionid', 64)->default('')->comment('微信unionid');
            $table->string('nickname', 50)->default('')->comment('昵称');
            $table->string('avatar', 255)->default('')->comment('头像');
            $table->string('api_token', 80)->default('')->index()->comment('接口token');
            $table->timestamp('token_expire_at')->nullable()->comment('token过期时间');
            $table->timestamps();
            $table->comment('微信小程序用户');
        });

        Schema::create('vol_volunteer', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('wx_user_id')->unique()->comment('微信用户ID');
            $table->string('name', 30)->default('')->comment('姓名');
            $table->string('phone', 20)->default('')->index()->comment('联系电话');
            $table->string('gender', 2)->default('1')->comment('性别 1男 2女');
            $table->unsignedTinyInteger('age')->default(0)->comment('年龄');
            $table->string('education', 30)->default('')->comment('文化程度');
            $table->string('political_status', 30)->default('')->comment('政治面貌');
            $table->string('id_card', 20)->default('')->comment('身份证号');
            $table->string('address', 200)->default('')->comment('家庭住址');
            $table->string('specialty', 200)->default('')->comment('特长');
            $table->string('emergency_contact', 30)->default('')->comment('紧急联系人');
            $table->string('emergency_phone', 20)->default('')->comment('紧急联系电话');
            $table->unsignedTinyInteger('audit_status')->default(0)->comment('审核状态 0待审 1通过 2拒绝');
            $table->unsignedInteger('total_points')->default(0)->comment('累计积分');
            $table->decimal('total_hours', 8, 1)->default(0)->comment('服务时长');
            $table->unsignedInteger('activity_count')->default(0)->comment('参与活动数');
            $table->unsignedTinyInteger('star_level')->default(0)->comment('星级');
            $table->string('certificate_no', 20)->default('')->comment('证书编号');
            $table->timestamps();
            $table->comment('志愿者信息');
        });

        Schema::create('vol_merchant', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->comment('商户名称');
            $table->string('account', 50)->unique()->comment('登录账号');
            $table->string('password', 100)->comment('登录密码');
            $table->string('contact', 30)->default('')->comment('联系人');
            $table->string('phone', 20)->default('')->comment('联系电话');
            $table->string('address', 200)->default('')->comment('商户地址');
            $table->string('business_type', 50)->default('')->comment('经营类型');
            $table->string('license_no', 50)->default('')->comment('营业执照号');
            $table->string('main_business', 200)->default('')->comment('主营业务');
            $table->string('logo', 255)->default('')->comment('商户logo');
            $table->text('description')->nullable()->comment('商户简介');
            $table->unsignedTinyInteger('audit_status')->default(0)->comment('审核状态 0待审 1通过 2拒绝');
            $table->unsignedTinyInteger('status')->default(1)->comment('状态 0禁用 1启用');
            $table->unsignedInteger('total_verify_count')->default(0)->comment('累计核销数');
            $table->unsignedInteger('total_points')->default(0)->comment('累计积分');
            $table->string('api_token', 80)->default('')->index()->comment('接口token');
            $table->timestamp('token_expire_at')->nullable();
            $table->timestamps();
            $table->comment('商户信息');
        });

        Schema::create('vol_banner', function (Blueprint $table) {
            $table->id();
            $table->string('title', 100)->default('')->comment('标题');
            $table->string('sub', 200)->default('')->comment('副标题');
            $table->string('image_url', 255)->default('')->comment('图片');
            $table->string('link_type', 20)->default('')->comment('链接类型');
            $table->string('link_value', 255)->default('')->comment('链接值');
            $table->unsignedInteger('sort')->default(0);
            $table->unsignedTinyInteger('status')->default(1)->comment('0禁用 1启用');
            $table->timestamps();
            $table->comment('首页轮播');
        });

        Schema::create('vol_article', function (Blueprint $table) {
            $table->id();
            $table->string('title', 100)->default('')->comment('标题');
            $table->unsignedTinyInteger('type')->default(1)->comment('1积分制简介 2兑换说明 3公告');
            $table->longText('content')->nullable()->comment('内容');
            $table->unsignedInteger('sort')->default(0);
            $table->unsignedTinyInteger('status')->default(1);
            $table->timestamps();
            $table->comment('文章公告');
        });

        Schema::create('vol_activity', function (Blueprint $table) {
            $table->id();
            $table->string('title', 100)->comment('活动标题');
            $table->string('type', 30)->default('')->comment('活动类型');
            $table->string('theme', 100)->default('')->comment('活动主题');
            $table->string('location', 200)->default('')->comment('活动地点');
            $table->string('contact', 30)->default('')->comment('联系人');
            $table->string('contact_phone', 20)->default('')->comment('联系电话');
            $table->unsignedInteger('points')->default(0)->comment('活动积分');
            $table->unsignedInteger('recruit_count')->default(0)->comment('招募人数');
            $table->unsignedInteger('signup_count')->default(0)->comment('已报名人数');
            $table->text('description')->nullable()->comment('活动介绍');
            $table->string('cover_url', 255)->default('')->comment('封面图');
            $table->unsignedInteger('signup_start_time')->default(0);
            $table->unsignedInteger('signup_end_time')->default(0);
            $table->unsignedInteger('start_time')->default(0);
            $table->unsignedInteger('end_time')->default(0);
            $table->string('checkin_code_in', 32)->default('')->comment('入场签到码');
            $table->string('checkin_code_out', 32)->default('')->comment('离场签到码');
            $table->unsignedTinyInteger('status')->default(1)->comment('0草稿 1发布 2结束');
            $table->text('summary')->nullable()->comment('活动总结');
            $table->json('images')->nullable()->comment('活动图片');
            $table->unsignedInteger('participant_count')->default(0)->comment('实际参加人数');
            $table->timestamps();
            $table->comment('志愿活动');
        });

        Schema::create('vol_activity_signup', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('activity_id')->index();
            $table->unsignedBigInteger('volunteer_id')->index();
            $table->unsignedTinyInteger('status')->default(1)->comment('1已报名 2进行中 3已完成');
            $table->unsignedInteger('checkin_start_time')->default(0);
            $table->unsignedInteger('checkin_end_time')->default(0);
            $table->decimal('service_hours', 8, 1)->default(0);
            $table->unsignedInteger('earned_points')->default(0);
            $table->timestamps();
            $table->unique(['activity_id', 'volunteer_id']);
            $table->comment('活动报名');
        });

        Schema::create('vol_goods', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('merchant_id')->default(0)->index()->comment('商户ID 0为社区');
            $table->string('name', 100)->comment('商品名称');
            $table->unsignedInteger('points')->default(0)->comment('所需积分');
            $table->unsignedInteger('stock')->default(0)->comment('库存');
            $table->string('image_url', 255)->default('')->comment('商品图片');
            $table->text('description')->nullable()->comment('商品描述');
            $table->unsignedTinyInteger('status')->default(2)->comment('0下架 1上架 2待审核');
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();
            $table->comment('积分商品');
        });

        Schema::create('vol_order', function (Blueprint $table) {
            $table->id();
            $table->string('order_no', 32)->unique()->comment('订单号');
            $table->unsignedBigInteger('volunteer_id')->index();
            $table->unsignedBigInteger('goods_id')->index();
            $table->unsignedBigInteger('merchant_id')->default(0)->index();
            $table->string('goods_name', 100)->default('');
            $table->unsignedInteger('num')->default(1);
            $table->unsignedInteger('points')->default(0);
            $table->string('exchange_code', 20)->unique()->comment('兑换码');
            $table->unsignedTinyInteger('status')->default(1)->comment('1待核销 2已核销 3已取消');
            $table->unsignedInteger('verify_time')->default(0);
            $table->unsignedBigInteger('verify_merchant_id')->default(0);
            $table->timestamps();
            $table->comment('兑换订单');
        });

        Schema::create('vol_points_log', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('volunteer_id')->index();
            $table->string('type', 20)->default('')->comment('activity/exchange/manual');
            $table->string('reason', 200)->default('');
            $table->integer('points')->default(0);
            $table->string('related_type', 30)->default('');
            $table->unsignedBigInteger('related_id')->default(0);
            $table->timestamps();
            $table->comment('积分记录');
        });

        Schema::create('vol_showcase', function (Blueprint $table) {
            $table->id();
            $table->string('title', 100)->default('');
            $table->string('type', 10)->default('1')->comment('1活动风采 2志愿者风采');
            $table->string('cover_url', 255)->default('');
            $table->text('content')->nullable();
            $table->json('images')->nullable();
            $table->unsignedTinyInteger('status')->default(1);
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();
            $table->comment('风采展示');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vol_showcase');
        Schema::dropIfExists('vol_points_log');
        Schema::dropIfExists('vol_order');
        Schema::dropIfExists('vol_goods');
        Schema::dropIfExists('vol_activity_signup');
        Schema::dropIfExists('vol_activity');
        Schema::dropIfExists('vol_article');
        Schema::dropIfExists('vol_banner');
        Schema::dropIfExists('vol_merchant');
        Schema::dropIfExists('vol_volunteer');
        Schema::dropIfExists('vol_wx_user');
    }
};
