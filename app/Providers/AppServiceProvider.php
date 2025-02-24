<?php

namespace App\Providers;

use App\Services\Payments\PaymentsService;
use App\Services\External\GreenSMSService;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Event;
use App\Services\AuthService;
use App\Services\UserService;
use App\Services\JwtService;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Add social network
        Event::listen(function (\SocialiteProviders\Manager\SocialiteWasCalled $event) {
            $event->extendSocialite('vkontakte', \SocialiteProviders\VKontakte\Provider::class);
            $event->extendSocialite('telegram', \SocialiteProviders\Telegram\Provider::class);
            $event->extendSocialite('google', \SocialiteProviders\Google\Provider::class);
            $event->extendSocialite('apple', \SocialiteProviders\Apple\Provider::class);
        });


        // Telescope
        if (class_exists(\Laravel\Telescope\TelescopeServiceProvider::class)) {
            $this->app->register(\Laravel\Telescope\TelescopeServiceProvider::class);
            $this->app->register(TelescopeServiceProvider::class);
        }

//        if (class_exists(\Barryvdh\Debugbar\ServiceProvider::class)) {
//            $this->app->register(DebugBarServiceProvider::class);
//        }

        // Auth service
        $this->app->bind(GreenSMSService::class, function ($app) {
            return new GreenSMSService();
        });

        $this->app->bind(AuthService::class, function ($app) {
            return new AuthService(
                $app->make(GreenSMSService::class)
            );
        });

        $this->app->bind(UserService::class, function ($app) {
            return new UserService();
        });

        // Default service
        $this->app->singleton(PaymentsService::class, function ($app) {
            return new PaymentsService($app);
        });

        $this->app->singleton(\App\Services\MailService::class);

        $this->app->singleton(JwtService::class, function () {
            return new JwtService();
        });

    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if ($this->app->environment('production')) {
            \URL::forceScheme('https');
        }
    }
}
