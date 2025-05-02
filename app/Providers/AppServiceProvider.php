<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\RobokassaService;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(RobokassaService::class, function () {
            return new RobokassaService();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (empty(env('APP_URL'))) {
            $schema = filter_var(request()->getHost(), FILTER_VALIDATE_IP) ?  "http://" : "https://";
            \URL::forceRootUrl($schema . request()->getHttpHost());
        }
    }
}
