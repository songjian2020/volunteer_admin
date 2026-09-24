<?php

namespace Modules\SystemTool\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\AnnoRoute\AnnoRoute;
use Modules\SystemTool\Services\SysSiteConfigService;

class SystemToolServiceProvider extends ServiceProvider
{

    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(SysSiteConfigService::class, SysSiteConfigService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(AnnoRoute $annoRoute): void
    {
        // 注册路由
        $annoRoute->register(base_path('modules/SystemTool/Http/Controllers'));
    }
}
