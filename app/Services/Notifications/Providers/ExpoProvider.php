<?php

namespace App\Services\Notifications\Providers;

use GuzzleHttp\Client;
use GuzzleHttp\Promise;
use Illuminate\Support\Facades\Log;

class ExpoProvider
{
    protected string $apiUrl = 'https://exp.host/--/api/v2/push/send';
    protected Client $client;

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

    /**
     * @param array $params
     * @return Promise\PromiseInterface
     */
    public function sendMessageAsync(array $params): Promise\PromiseInterface
    {
        if (isset($params['data'])) {
            $params['data'] = (object)$params['data'];
        }

        return $this->client->postAsync($this->apiUrl, [
            'json' => $params
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
     * @param array $messagesParams
     * @return Promise\PromiseInterface
     */
    public function sendBatchMessagesAsync(array $messagesParams): Promise\PromiseInterface
    {
        return $this->client->postAsync($this->apiUrl, [
            'json' => $messagesParams
        ])->then(
            function ($response) {
                return $this->handleBatchSuccessResponse($response);
            },
            function ($exception) {
                return $this->handleBatchError($exception);
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
        $status = !empty($getResponse['data']['status']) && $getResponse['data']['status'] === "ok";

        if (!$status) {
            Log::error("[ExpoProvider] Async send failed", [
                'token' => $params['to'],
                'response' => $getResponse
            ]);
        }

        return [
            'success' => $status,
            'token' => $params['to'],
            'response' => $getResponse
        ];
    }

    /**
     * @param $response
     * @return array
     */
    private function handleBatchSuccessResponse($response): array
    {
        $getResponse = json_decode($response->getBody(), true);
        $results = [];

        if (!empty($getResponse['data'])) {
            foreach ($getResponse['data'] as $item) {
                $status = !empty($item['status']) && $item['status'] === "ok";
                $results[] = [
                    'success' => $status,
                    'response' => $item
                ];

                if (!$status) {
                    Log::error("[ExpoProvider] Batch async send failed", [
                        'response' => $item
                    ]);
                }
            }
        }

        return $results;
    }

    /**
     * @param $exception
     * @param array $params
     * @return array
     */
    private function handleError($exception, array $params): array
    {
        Log::error("[ExpoProvider] Async request failed", [
            'token' => $params['to'],
            'error' => $exception->getMessage(),
            'code' => $exception->getCode()
        ]);

        return [
            'success' => false,
            'token' => $params['to'],
            'error' => $exception->getMessage()
        ];
    }

    /**
     * @param $exception
     * @return array
     */
    private function handleBatchError($exception): array
    {
        Log::error("[ExpoProvider] Batch async request failed", [
            'error' => $exception->getMessage(),
            'code' => $exception->getCode()
        ]);

        return [
            'success' => false,
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
