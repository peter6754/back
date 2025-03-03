<?php

namespace App\Services\External;

use Illuminate\Support\Facades\Log;
use GreenSMS\GreenSMS;

class GreenSMSService
{
    /**
     * Список каналов по умолчанию в порядке приоритета
     * @var array|string[]
     */
    static array $defaultChannelsPriority = [
        'whatsapp',
        'telegram',
        'sms'
    ];

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
     * Отправка кода на указанный номер телефона через доступные каналы связи
     * @param string $phone
     * @param string $message
     * @param array $exceptions
     * @return bool
     */
    public function sendCode(string $phone, string $message, array $exceptions = []): bool
    {
        try {
            // Запрещаем работу сервиса в локальной среде
            if (app()->environment('local')) {
//                return true;
            }

            // Получаем каналы связи с учётом исключений
            $channels = $this->getChannels($exceptions);

            var_dump($channels);
            exit;

            // Пробуем отправить сообщение через каждый канал в порядке приоритета
            foreach ($channels as $channel) {
                try {
                    // Параметры отправки (по умолчанию для всех каналов)
                    $sendParams = [];

                    // Канал-специфичные параметры
                    switch ($channel) {
                        case 'telegram':
                            $sendParams['txt'] = preg_replace("/[^,.0-9]/", '', $message);
                            break;
                        case 'whatsapp':
                            $sendParams['from'] = 'GREENSMS';
                            break;
                    }

                    // Пробуем отправить сообщение через канал
                    $response = $this->client->{$channel}->send(array_merge([
                        'to' => preg_replace("/[^,.0-9]/", '', $phone),
                        'txt' => $message
                    ], $sendParams));

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

    /**
     * Получение каналов за вычетом исключений
     * @param array $exceptions
     * @return array
     */
    function getChannels(array $exceptions = []): array
    {
        return array_diff(self::$defaultChannelsPriority, $exceptions);
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
