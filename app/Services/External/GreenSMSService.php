<?php

namespace App\Services\External;

use Illuminate\Support\Facades\Log;
use GreenSMS\GreenSMS;

class GreenSMSService
{
    /**
     * Default channels priority for sending messages
     * @var array|string[]
     */
    static array $defaultChannelsPriority = [
        'whatsapp',
        'telegram',
        'viber',
        'sms'
    ];

    /**
     * @var GreenSMS|null
     */
    protected ?GreenSMS $client = null;

    /**
     * @throws \Exception
     */
    public function __construct()
    {
        // Login / Password default auth
        $authData = [
            'user' => config('greensms.sms_user'),
            'pass' => config('greensms.sms_pass')
        ];

        // GreenSMS token auth
        if (!empty(config('greensms.token'))) {
            $authData['token'] = config('greensms.token');
        }

        $this->client = new GreenSMS($authData);
    }

    /**
     * Попытка отправки через каскад каналов: telegram -> viber -> whatsapp -> sms
     * @param string $phone
     * @param string $message
     * @return bool
     */
    public function sendCode(string $phone, string $message): bool
    {
        try {
            if (app()->environment('local')) {
                return true;
            }

            // Пробуем отправить сообщение через каждый канал в порядке приоритета
            foreach (self::$defaultChannelsPriority as $channel) {
                try {
                    // Пробуем отправить сообщение через канал
                    $response = $this->client->{$channel}->send([
                        'to' => preg_replace("/[^,.0-9]/", '', $phone),
                        'txt' => $message
                    ]);

                    // Если отправка успешна, логируем и выходим из функции
                    if (!empty($response->request_id)) {
                        Log::info("GreenSMSService: сообщение отправлено через {$channel}", [
                            'phone' => $phone,
                            'channel' => $channel,
                            'response' => $response
                        ]);

                        return true;
                    }
                } catch (\Exception $e) {
                    Log::warning("GreenSMSService: не удалось отправить через {$channel}", [
                        'phone' => $phone,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            return false;
        } catch (\Exception $e) {
            Log::error("GreenSMSService::sendCode(): {$e->getMessage()}", [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    public function getBalance()
    {
        $response = $this->client->account->balance();
        return $response->balance;
    }
}
