<?php

namespace App\Providers;

use Laravel\Telescope\EntryType;
use Laravel\Telescope\TelescopeApplicationServiceProvider;
use Laravel\Telescope\IncomingEntry;
use Illuminate\Support\Collection;
use Laravel\Telescope\Telescope;
use function Symfony\Component\String\b;

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

            // Temperary not working
            return $entries->contains(function (IncomingEntry $entry) {
                return $this->myCustomRules($entry) ||
                    $entry->isReportableException() ||
                    $entry->isScheduledTask() ||
                    $entry->isFailedJob() ||
                    $entry->isSlowQuery() ||
                    $entry->isLog()
                    ;
            });
        });
    }

    /**
     * @param IncomingEntry $entry
     * @return bool
     */
    private function myCustomRules(IncomingEntry $entry): bool
    {
        return match ($entry->type) {
            EntryType::REQUEST => (function () use ($entry) {
                $status = $entry->content['response_status'] ?? 200;
                return $status > 200 && $status !== 404;
            })(),
            default => false
        };
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
