<?php

namespace App\Services\Notifications\Providers;

use GuzzleHttp\Client;
use GuzzleHttp\Promise;
use Illuminate\Support\Facades\Log;

class TelegramProvider
{
    protected string $apiUrl = 'https://api.telegram.org/bot{{TELEGRAM_TOKEN}}/sendMessage';
    protected Client $client;

    /**
     *
     */
    public function __construct()
    {
        $this->client = new Client([
            'headers' => [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json'
            ]
        ]);

        $this->apiUrl = str_replace('{{TELEGRAM_TOKEN}}',
            config('services.telegram.client_secret'),
            $this->apiUrl
        );
    }

    /**
     * @param array $params
     * @return Promise\PromiseInterface
     */
    public function sendMessageAsync(array $params): Promise\PromiseInterface
    {
        $messageData = [
            'text' => "<b>{$params['title']}</b>" . PHP_EOL . $params['body'],
            'chat_id' => $params['to'],
            'parse_mode' => 'HTML'
        ];

        return $this->client->postAsync($this->apiUrl, [
            'json' => $messageData
        ])->then(
            function ($response) use ($params) {
                return $this->handleSuccessResponse($response, $params);
            },
            function ($exception) use ($params) {
                return $this->handleError($exception, $params);
            }
        );
    }

    /**
     * @param $response
     * @param array $params
     * @return array
     */
    private function handleSuccessResponse($response, array $params): array
    {
        $getResponse = json_decode($response->getBody(), true);
        $success = !empty($getResponse['ok']);

        if (!$success) {
            Log::error("[TelegramProvider] Async send failed", [
                'chat_id' => $params['to'],
                'response' => $getResponse
            ]);
        }

        return [
            'success' => $success,
            'chat_id' => $params['to'],
            'response' => $getResponse
        ];
    }

    /**
     * @param $exception
     * @param array $params
     * @return array
     */
    private function handleError($exception, array $params): array
    {
        Log::error("[TelegramProvider] Async request failed", [
            'chat_id' => $params['to'],
            'error' => $exception->getMessage(),
            'code' => $exception->getCode()
        ]);

        return [
            'success' => false,
            'chat_id' => $params['to'],
            'error' => $exception->getMessage()
        ];
    }

    /**
     * @param array $params
     * @return bool
     */
    public function sendMessage(array $params): bool
    {
        $promise = $this->sendMessageAsync($params);
        $result = $promise->wait();

        return $result['success'];
    }
}
