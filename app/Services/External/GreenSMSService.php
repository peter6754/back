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
     * @param string $phone
     * @param string $message
     * @param array $exceptions
     * @return bool
     */
    public function sendCode(string $phone, string $message, array $exceptions = []): bool
    {
        try {
            if (app()->environment('local')) {
                return true;
            }

            $normalizedPhone = preg_replace("/[^0-9]/", '', $phone);
            $channels = array_diff(self::$defaultChannelsPriority, $exceptions);

            // Получаем историю использованных каналов для этого номера
            $usedChannels = Cache::get('greensms_used_channels:' . $normalizedPhone, []);

            // Сортируем каналы: сначала неиспользованные, потом использованные
            $sortedChannels = [];
            foreach ($channels as $channel) {
                if (!in_array($channel, $usedChannels)) {
                    $sortedChannels[] = $channel;
                }
            }

            // Если все каналы уже использованы, берем канал по умолчанию
            if (empty($sortedChannels)) {
                $sortedChannels[] = self::$defaultChannel;
            } else {
                // Добавляем использованные каналы после неиспользованных
                foreach ($channels as $channel) {
                    if (in_array($channel, $usedChannels)) {
                        $sortedChannels[] = $channel;
                    }
                }
            }

            // Пробуем отправить сообщение через каждый канал в порядке приоритета
            foreach ($sortedChannels as $channel) {
                try {
                    $sendParams = [
                        'to' => $normalizedPhone,
                        'txt' => $message
                    ];

                    switch ($channel) {
                        case 'telegram':
                            $sendParams['txt'] = preg_replace("/[^0-9]/", '', $sendParams['txt']);
                            break;
                        case 'whatsapp':
                            $sendParams['from'] = 'GREENSMS';
                            break;
                        case 'sms':
                            $sendParams['from'] = 'TinderOne';
                            break;
                    }

                    $response = $this->client->{$channel}->send($sendParams);

                    if (!empty($response->request_id)) {
                        // Обновляем историю использованных каналов
                        if (!in_array($channel, $usedChannels)) {
                            $usedChannels[] = $channel;
                            Cache::put('greensms_used_channels:' . $normalizedPhone, $usedChannels, 120);
                        }

                        Log::channel("greensms")->info("GreenSMSService: сообщение отправлено через {$channel}", [
                            'phone' => $normalizedPhone,
                            'channel' => $channel
                        ]);

                        return true;
                    }
                } catch (\Exception $e) {
                    Log::channel("greensms")->warning("GreenSMSService: не удалось отправить через {$channel}", [
                        'phone' => $normalizedPhone,
                        'error' => $e->getMessage()
                    ]);
                    continue;
                }
            }

            return false;
        } catch (\Exception $e) {
            Log::channel("greensms")->error("GreenSMSService::sendCode(): {$e->getMessage()}", [
                'phone' => $phone,
                'error' => $e->getMessage()
            ]);
            return false;
        }
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
