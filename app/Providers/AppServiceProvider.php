<?php

namespace App\Providers;

use App\Support\Deployment;
use App\Support\PatientIdentitySeal;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (Deployment::isStaging()) {
            Deployment::guardLiveAddress();
            config([
                'filesystems.tenant_disk' => 'local',
            ]);
            PatientIdentitySeal::ensure();
        }

        if ($this->app->environment('production', 'staging') || Deployment::isStaging()) {
            URL::forceHttps();
        }
    }
}
