<?php

namespace App\Services\External;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use GreenSMS\GreenSMS;

class GreenSMSService
{
    /**
     * @var GreenSMS|null
     */
    protected ?GreenSMS $client = null;

    /**
     * GreenSMSService constructor.
     * @throws \Exception
     */
    public function __construct()
    {
        $authData = [
            'user' => config('greensms.sms_user'),
            'pass' => config('greensms.sms_pass')
        ];

        if (!empty(config('greensms.token'))) {
            $authData['token'] = config('greensms.token');
        }

        $this->client = new GreenSMS($authData);
    }

    /**
     * Отправка кода на указанный номер телефона через доступные каналы связи
     * @param  string  $phone
     * @param  string  $message
     * @param  array  $exceptions
     * @return array
     */
    public function sendCode(string $phone, string $message, array $exceptions = []): array
    {
        $phone = preg_replace("/[^,.0-9]/", '', $phone);
        try {
            if (app()->environment('local')) {
                return [
                    'success' => true
                ];
            }

//            if (Cache::has('sms_code_'.$phone)) {
//                throw new \Exception('SMS code already sent');
//            }

            // Send message
            $response = $this->client->sms->send([
                'txt' => $message,
                'to' => $phone
            ]);

//            Cache::put(
//                'sms_code_'.$phone,
//                $message,
//                now()->addMinutes(5)
//            );

            // Return status
            return [
                'success' => (!empty($response->request_id)),
                'provider' => 'sms',
            ];
        } catch (\Exception $e) {
            Log::error("GreenSMSService::sendCode({$phone}, {$message}): {$e->getMessage()}", [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            return [
                'message' => $e->getMessage(),
                'success' => false
            ];
        }
    }

    /**
     * Получение баланса
     * @return float
     * @throws \Exception
     */
    public function getBalance(): float
    {
        $response = $this->client->account->balance();
        return $response->balance;
    }
}
