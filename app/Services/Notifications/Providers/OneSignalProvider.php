<?php

namespace App\Services\Notifications\Providers;

use GuzzleHttp\Client;
use GuzzleHttp\Promise;
use Illuminate\Support\Facades\Log;

class OneSignalProvider
{
    protected string $apiUrl = 'https://api.onesignal.com/notifications?c=push';
    protected string $restApiKey;
    protected Client $client;
    protected string $appId;

    /**
     *
     */
    public function __construct()
    {
        $this->appId = config('services.onesignal.app_id');
        $this->restApiKey = config('services.onesignal.rest_api_key');

        $this->client = new Client([
            'headers' => [
                'Authorization' => 'Basic '.$this->restApiKey,
                'Content-Type' => 'application/json'
            ],
        ]);
    }

    /**
     * @param  array  $params
     * @return Promise\PromiseInterface
     */
    public function sendMessageAsync(array $params): Promise\PromiseInterface
    {
        $requestParams = [
            'app_id' => $this->appId,
            'contents' => [
                "en" => $params['body']
            ],
            'target_channel' => "push",

            'include_aliases' => [
                'onesignal_id' => [
                    $params['to']
                ]
            ],

            "small_icon" => "ic_stat_onesignal_default",
            "android_sound" => "default",
            "ios_sound" => "default",
        ];

        if (!empty($params['title'])) {
            $requestParams['headings'] = [
                "en" => $params['title']
            ];
        }

        if (!empty($params['android_sound'])) {
            $requestParams['android_sound'] = $params['android_sound'];
        }
        if (!empty($params['ios_sound'])) {
            $requestParams['ios_sound'] = $params['ios_sound'];
        }
        if (!empty($params['url'])) {
            $requestParams['url'] = $params['url'];
        }

//        echo "JSON body: ".json_encode($requestParams, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE).PHP_EOL;

        return $this->client->postAsync($this->apiUrl, [
            'json' => $requestParams
        ])->then(
            function ($response) use ($params) {
//                echo "Success: ".$response->getBody().PHP_EOL.PHP_EOL;
                return $this->handleSuccessResponse($response, $params);
            },
            function ($exception) use ($params) {
//                echo "Error: ".$exception->getMessage().PHP_EOL.PHP_EOL;
                return $this->handleError($exception, $params);
            }
        );
    }

    /**
     * @param $response
     * @param  array  $params
     * @return array
     */
    private function handleSuccessResponse($response, array $params): array
    {
        $getResponse = json_decode($response->getBody(), true);
        $success = isset($getResponse['id']) && !empty($getResponse['recipients']);

        if (!$success) {
            Log::error("[OneSignalProvider] Async send failed", [
                'user_id' => $params['to'],
                'response' => $getResponse
            ]);
        }

        return [
            'success' => $success,
            'user_id' => $params['to'],
            'response' => $getResponse
        ];
    }

    /**
     * @param $exception
     * @param  array  $params
     * @return array
     */
    private function handleError($exception, array $params): array
    {
        Log::error("[OneSignalProvider] Async request failed", [
            'user_id' => $params['to'],
            'error' => $exception->getMessage(),
            'code' => $exception->getCode()
        ]);

        return [
            'success' => false,
            'user_id' => $params['to'],
            'error' => $exception->getMessage()
        ];
    }

    /**
     * @param  array  $params
     * @return bool
     */
    public function sendMessage(array $params): bool
    {
        $promise = $this->sendMessageAsync($params);
        $result = $promise->wait();

        return $result['success'];
    }
}
