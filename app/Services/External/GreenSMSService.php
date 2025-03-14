<?php

namespace App\Services\External;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use GreenSMS\GreenSMS;

class GreenSMSService
{
    /**
     * Список каналов по умолчанию в порядке приоритета
     * @var array|string[]
     */
    static array $defaultChannelsPriority = [
        'telegram',
        'whatsapp',
        'sms'
    ];

    /**
     * Канал по умолчанию, если все уже использованы
     * @var string
     */
    static string $defaultChannel = 'sms';

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
    public function sendSMS(string $phone, string $message, array $exceptions = []): array
    {
        try {
            $phone = preg_replace("/[^,.0-9]/", '', $phone);
            if (app()->environment('local')) {
                return [
                    'success' => true
                ];
            }

            // Send message
            $response = $this->client->sms->send([
                'txt' => $message,
                'to' => $phone
            ]);

            return [
                'success' => (!empty($response->request_id)),
                'provider' => 'sms',
            ];
        } catch (\Exception $e) {
            Log::error("GreenSMSService::sendSMS(): {$e->getMessage()}", [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
        }

        return [
            'success' => false
        ];
    }

    /**
     * Получение баланса
     * @return float
     * @throws \Exception
     */
    public function getBalance()
    {
        $response = $this->client->account->balance();
        return $response->balance;
    }
}
