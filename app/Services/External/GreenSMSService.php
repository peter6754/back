<?php

namespace App\Services\External;

use GreenSMS\GreenSMS;
use Illuminate\Support\Facades\Log;

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
        if (!empty(config('greensms.token'))) {
            // GreenSMS token auth
            $this->client = new GreenSMS([
                'token' => config('greensms.token'),
            ]);
        } else {
            // Login / Password auth
            $this->client = new GreenSMS([
                'user' => config('greensms.sms_user'),
                'pass' => config('greensms.sms_pass')
            ]);
        }

    }

    /**
     * @param string $phone
     * @param string $message
     * @return bool
     */
    public function sendSMS(string $phone, string $message): bool
    {
        try {
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
        } catch (\Exception $e) {
            Log::error("GreenSMSService::sendSMS(): {$e->getMessage()}", [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }
}
