<?php

namespace App\Services\Notifications\Providers;

use NotificationChannels\OneSignal\OneSignalChannel;
use NotificationChannels\OneSignal\OneSignalMessage;

class OneSignalProvider
{
    protected $appId;
    protected $restApiKey;

    public function __construct($appId, $restApiKey)
    {
        $this->appId = $appId;
        $this->restApiKey = $restApiKey;
    }

    public function send($to, $notification)
    {
        $message = OneSignalMessage::create()
            ->setSubject($notification->getTitle())
            ->setBody($notification->getBody())
            ->setData('data', $notification->getData());

        // Здесь должна быть логика отправки через OneSignal API
        // Можно использовать официальный SDK или HTTP-клиент

        return $this->sendViaChannel($to, $message);
    }

    protected function sendViaChannel($to, $message)
    {
        // Эмуляция отправки через Laravel Notification Channel
        $channel = new OneSignalChannel(
            app('config')->get('services.onesignal')
        );

        return $channel->send(new class {
            public function routeNotificationForOneSignal() {
                return $this->onesignal_player_id;
            }
        }, $message);
    }
}
