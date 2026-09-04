<?php

namespace App\Providers;

use Illuminate\Foundation\Exceptions\Handler as ExceptionsHandler;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Modules\AnnoRoute\AnnoRoute;


class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {

        $this->app->bind(ExceptionsHandler::class, \App\Exceptions\ExceptionsHandler::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(AnnoRoute $annoRoute): void
    {
        Schema::defaultStringLength(191);

        // 注册路由
        $annoRoute->register(app_path('Http/Controllers'));
    }
}
