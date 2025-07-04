<?php

namespace App\Services\Notifications\Providers;

use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;
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
                'Content-Type' => 'application/json',
                'Authorization' => 'Basic ' . $this->restApiKey,
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
                'en' => $params['body'],
            ],
            'headings' => [
                'en' => $params['title'],
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

        // Send request
        $response = $this->client->post($this->apiUrl, [
            'json' => $requestParams
        ]);

        // Response
        $getResponse = json_decode(
            $response->getBody(),
            true
        );

        if (!empty($getResponse['errors'])) {
            Log::error("Failed to send messages: ", [
                "request" => $requestParams,
                "response" => $getResponse
            ]);
        }

        // Check if request was successful
        return isset($getResponse['id']) && !empty($getResponse['recipients']);
    }

    /**
     * Send message to specific user
     * @param string $playerId
     * @param string $message
     * @param array $data
     * @return bool
     * @throws GuzzleException
     */
    public function sendToUser(string $playerId, string $message, array $data = []): bool
    {
        $params = [
            'include_player_ids' => [$playerId],
            'contents' => [
                'en' => $message
            ],
            'data' => $data
        ];

        return $this->sendMessage($params);
    }

    /**
     * Send message to multiple users
     * @param array $playerIds
     * @param string $message
     * @param array $data
     * @return bool
     * @throws GuzzleException
     */
    public function sendToUsers(array $playerIds, string $message, array $data = []): bool
    {
        $params = [
            'include_player_ids' => $playerIds,
            'contents' => [
                'en' => $message
            ],
            'data' => $data
        ];

        return $this->sendMessage($params);
    }
}
