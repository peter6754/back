<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use GuzzleHttp\Client;

class ExpoNotificationService
{
    const SUCCEEDED_VERIFICATION_REQUEST_PUSH = [
        "message" => "Поздравляем! Вы успешно прошли верификацию.",
        "title" => "Успешная верификация!"
    ];

    const FAILED_VERIFICATION_REQUEST_PUSH = [
        "message" => "Сожалеем, но ваш аккаунт не прошел верификацию. Пройдите заново и сфотографируйтесь по всем правилам, чтобы пройти верификацию.",
        "title" => "Аккаунт не верифицирован!"
    ];

    const NEW_GIFT_PUSH = [
        "message" => "У вас новый подарок! Вы можете ответить на него и начать диалог с пользователем.",
        "title" => "Вам прислали подарок!"
    ];

    const NEW_OPERATOR_MESSAGE_PUSH = [
        "message" => "Поступил ответ от оператора чат-поддержки. Зайдите в чат, чтобы посмотреть."
    ];

    const FEEDBACK_CHANGE_PUSH = [
        "message" => "Кто-то отредактировал свой отзыв о вас. Зайдите в приложение и проверьте изменения!",
        "title" => "Изменение отзыва!"
    ];

    const NEW_FEEDBACK_PUSH = [
        "message" => "Вам оставлен отзыв в TinderOne. Зайдите в приложение и посмотрите что о вас думают другие пользователи!",
        "title" => "Новый отзыв!"
    ];

    const NEW_SUPERLIKE_PUSH = [
        "message" => "Вам поставили суперлайк! Заходите в TinderOne, чтобы найти свою пару!",
        "title" => "Вы кому-то нравитесь!"
    ];

    const NEW_MATCH_PUSH = [
        "message" => "У вас совпала новая пара! Зайдите, чтобы посмотреть и начать общение.",
        "title" => "Новая пара!"
    ];
    const LIKES_UPDATE_PUSH = [
        "message" => "Ваши лайки обновились! Заходи и ищи свою пару в TinderOne!",
        "title" => "Пора знакомиться:)"
    ];

    const SUBSCRIPTION_EXPIRATION_PUSH = [
        "message" => "Ваша подписка аннулирована! Зайдите, чтобы подключить заново.",
        "title" => "Подписка аннулирована!"
    ];


    protected string $receiptsUrl = 'https://exp.host/--/api/v2/push/getReceipts';
    protected string $apiUrl = 'https://exp.host/--/api/v2/push/send';
    protected Client $client;


//$expo = new ExpoNotificationService();
//$response = $expo->sendPushNotification([
//"ExponentPushToken[TtCzy6NWfPxNshBiTPGF0i]"
//],"Message Test","Привет андрей!");
//var_dump($response);

    public function __construct()
    {
        $this->client = new Client([
            'headers' => [
                'Content-Type' => 'application/json',
                'Accept-Encoding' => 'gzip, deflate',
                'Accept' => 'application/json',
            ],
        ]);
    }

    public function sendPushNotification(
        array  $tokens,
        string $message,
        string $title,
               $additionalData = [],
        bool   $withSound = true
    ): array
    {
        $messages = [];
        foreach ($tokens as $token) {
            if (!$this->isExpoPushToken($token)) {
                Log::error("Invalid push token {$token}");
                continue;
            }

            $messages[] = [
                'to' => $token,
                'sound' => $withSound ? 'default' : '',
                'title' => $title,
                'body' => $message,
                'data' => (object)$additionalData,
            ];
        }

        $chunks = $this->chunkPushNotifications($messages);
        $tickets = [];

        foreach ($chunks as $chunk) {
            try {
                $response = $this->client->post($this->apiUrl, [
                    'json' => $chunk,
                ]);
                $body = json_decode($response->getBody(), true);
                if (isset($body['data'])) {
                    $tickets = array_merge($tickets, $body['data']);
                }
            } catch (\Exception $e) {
                Log::error($e->getMessage());
            }
        }

        return $tickets;
    }

    public function checkReceipts(array $receiptIds): array
    {
        $results = [];
        try {
            $response = $this->client->post($this->receiptsUrl, [
                'json' => ['ids' => $receiptIds],
            ]);
            $body = json_decode($response->getBody(), true);

            if (isset($body['data'])) {
                foreach ($body['data'] as $receiptId => $receipt) {
                    if ($receipt['status'] === 'ok') {
                        $results[] = ['receiptId' => $receiptId, 'status' => 'ok'];
                    } else if ($receipt['status'] === 'error') {
                        $results[] = ['receiptId' => $receiptId, 'status' => 'error', 'message' => $receipt['details']['error'] ?? ''];
                    } else {
                        $results[] = ['receiptId' => $receiptId, 'status' => 'unknown'];
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error($e->getMessage());
        }
        return $results;
    }

    protected function isExpoPushToken($token): bool
    {
        // Expo push tokens always start with "ExponentPushToken["
        return is_string($token) && preg_match('/^ExponentPushToken\[(.+)\]$/', $token);
    }

    protected function chunkPushNotifications(array $messages, $chunkSize = 100): array
    {
        // Expo recommends not sending more than 100 notifications per request
        return array_chunk($messages, $chunkSize);
    }
}
