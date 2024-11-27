<?php

namespace App\Services\External;

use GreenSMS\GreenSMS;

class GreenSMSService
{
    /**
     * @var GreenSMS|null
     */
    protected ?GreenSMS $client = null;

    /**
     * @throws \Exception
     */
    public function __construct()
    {
        $this->client = new GreenSMS([
            'token' => config('greensms.token')
        ]);
    }

    /**
     * @param string $phone
     * @param string $message
     * @return bool
     */
    public function sendSMS(string $phone, string $message): bool
    {
        // Disable sending messages for local
        if (app()->environment('local')) {
            return true;
        }

        // Send message
        $response = $this->client->sms->send([
            'to' => preg_replace("/[^,.0-9]/", '', $phone),
            'txt' => $message
        ]);

        return (!empty($response->request_id));
    }
}
