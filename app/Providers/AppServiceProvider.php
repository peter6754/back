<?php

namespace App\Providers;

use App\Services\Payments\PaymentsService;
use App\Services\ExpoNotificationService;
use Illuminate\Support\ServiceProvider;
use App\Services\JwtService;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(ExpoNotificationService::class, function () {
            return new ExpoNotificationService();
        });
        $this->app->singleton(PaymentsService::class, function ($app) {
            return new PaymentsService($app);
        });
        $this->app->singleton(JwtService::class, function () {
            return new JwtService();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (empty(env('APP_URL'))) {
            $schema = filter_var(request()->getHost(), FILTER_VALIDATE_IP) ? "http://" : "https://";
            \URL::forceRootUrl($schema . request()->getHttpHost());
        }
    }
}
