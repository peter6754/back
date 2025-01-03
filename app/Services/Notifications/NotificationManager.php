<?php

namespace App\Services\Notifications;

use App\Services\Notifications\Providers\OneSignalProvider;
use App\Services\Notifications\Providers\ExpoProvider;
use Illuminate\Support\Manager;

/**
 *
 */
class NotificationManager extends Manager
{
    /**
     * @return OneSignalProvider
     */
    public function createOnesignalDriver(): OneSignalProvider
    {
        return new OneSignalProvider();
    }

    /**
     * @return ExpoProvider
     */
    public function createExpoDriver(): ExpoProvider
    {
        return new ExpoProvider();
    }

    /**
     * @param string $provider
     * @param $params
     * @return bool
     */
    public function sendMessage(string $provider, $params): bool
    {
        return $this->driver($provider)
            ->sendMessage($params);
    }

    /**
     * @return mixed
     */
    public function getDefaultDriver(): mixed
    {
        return $this->config->get('notification.default');
    }
}
