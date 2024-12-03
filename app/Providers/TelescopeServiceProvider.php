<?php

namespace App\Providers;

use Laravel\Telescope\TelescopeApplicationServiceProvider;
use Laravel\Telescope\IncomingEntry;
use Illuminate\Support\Collection;
use Laravel\Telescope\Telescope;

class TelescopeServiceProvider extends TelescopeApplicationServiceProvider
{
    /**
     * Checking backpack admin authorisation
     */
    protected function authorization(): void
    {
        Telescope::auth(function ($request) {
            return backpack_auth()->check() && backpack_user()->hasRole('Superadmin');
        });
    }

    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->hideSensitiveRequestDetails();

        Telescope::filterBatch(function (Collection $entries) {
            if ($this->app->environment('local')) {
                return true;
            }

            return $entries->contains(function (IncomingEntry $entry) {
                return $entry->isReportableException() ||
                    $entry->isClientRequest() ||
                    $entry->isScheduledTask() ||
                    $entry->isFailedJob() ||
                    $entry->isSlowQuery();
            });
        });
    }

    /**
     * Prevent sensitive request details from being logged by Telescope.
     */
    protected function hideSensitiveRequestDetails(): void
    {
        Telescope::hideRequestParameters(['_token']);
        Telescope::hideRequestHeaders([
            'x-csrf-token',
            'x-xsrf-token',
            'cookie',
        ]);
    }

}
