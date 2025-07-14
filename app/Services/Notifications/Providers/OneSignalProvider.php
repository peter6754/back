<?php

namespace App\Services\Notifications\Providers;

use Illuminate\Support\Facades\Log;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Client;

class OneSignalProvider
{
    /**
     * @var string
     */
    protected string $apiUrl = 'https://api.onesignal.com/notifications?c=push';

    /**
     * @var Client
     */
    protected Client $client;

    /**
     * @var string
     */
    protected string $appId;

    /**
     * @var string
     */
    protected string $restApiKey;

    /**
     * OneSignalProvider constructor.
     */
    public function __construct()
    {
        $this->appId = config('services.onesignal.app_id');
        $this->restApiKey = config('services.onesignal.rest_api_key');

        $this->client = new Client([
            'headers' => [
                'Authorization' => 'Basic ' . $this->restApiKey,
                'Content-Type' => 'application/json'
            ],
        ]);
    }

    /**
     * @param array $params
     * @return bool
     * @throws GuzzleException
     */
    public function sendMessage(array $params): bool
    {
        // Default parameters
        $defaultParams = [
            'target_channel' => 'push',
            'app_id' => $this->appId
        ];

        // Merge default parameters with custom ones
        $requestParams = array_merge($defaultParams, [
            'contents' => [
                "en" => $params['body'],
                "ru" => $params['body']
            ],
            'headings' => [
                "en" => $params['title'],
                "ru" => $params['title'],
            ],
            'include_aliases' => [
                'external_id' => [
                    $params['to']
                ],
                'onesignal_id' => [
                    $params['to']
                ]
            ]
        ]);

        try {
            // Send request
            $response = $this->client->post($this->apiUrl, [
                'json' => $requestParams
            ]);

            Log::debug("OneSignal send message response: " . json_encode($response));
            // Response
            $getResponse = json_decode(
                $response->getBody(),
                true
            );
            Log::debug("OneSignal decode response: ", $getResponse);
        } catch (\Exception $e) {
            echo $e->getMessage();
            exit;
        }

        // Return
        return isset($getResponse['id']) && !empty($getResponse['recipients']);
    }
}
