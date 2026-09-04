<?php

namespace Modules\Volunteer\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\AnnoRoute\AnnoRoute;

class VolunteerServiceProvider extends ServiceProvider
{
    public function register(): void
    {
    }

    public function boot(AnnoRoute $annoRoute): void
    {
        $annoRoute->register(base_path('modules/Volunteer/Http/Controllers'));
    }
}
