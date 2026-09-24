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
        // laravel/boost 仅在 require-dev；生产 --no-dev 时不存在，避免硬引用导致整站 500
        $boostClass = 'Laravel\\Boost\\Boost';
        if (class_exists($boostClass)) {
            $boostClass::registerAgent('reasonix', \Modules\SystemTool\Ai\Boots\Reasonix::class);
        }

        // 注册路由
        $annoRoute->register(base_path('modules/SystemTool/Http/Controllers'));
    }
}
