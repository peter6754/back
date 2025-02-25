<?php

namespace App\Services\Notifications;

use App\Services\Notifications\Providers\OneSignalProvider;
use App\Services\Notifications\Providers\TelegramProvider;
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
     * @return TelegramProvider
     */
    public function createTelegramDriver(): TelegramProvider
    {
        return new TelegramProvider();
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
        try {
            return $this->driver($provider)
                ->sendMessage($params);
        } catch (\Exception $e) {
            echo $e->getMessage();
            return false;
        }
    }

    /**
     * @return mixed
     */
    public function getDefaultDriver(): mixed
    {
        return $this->config->get('notification.default');
    }
}
