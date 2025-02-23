<?php

namespace App\Services\Notifications\Providers;

use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;
use GuzzleHttp\Client;

class ExpoProvider
{
    /**
     * @var string
     */
    protected string $apiUrl = 'https://exp.host/--/api/v2/push/send';

    /**
     * @var Client
     */
    protected Client $client;

    /**
     *
     */
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
     * @return bool
     * @throws GuzzleException
     */
    public function sendMessage(array $params): bool
    {
        // Formatted message
        if (isset($params['data'])) {
            $params['data'] = (object)$params['data'];
        }

        // Send request
        $response = $this->client->post($this->apiUrl, [
            'json' => $params,
        ]);

        // Response
        $getResponse = json_decode(
            $response->getBody(),
            true
        );

        $status = (!empty($getResponse['data']['status']) && $getResponse['data']['status'] === "ok");

        if ($status === false) {
            Log::error(
                "[NotificationService] provider expo, push token {$params['to']}",
                $getResponse
            );
        }

        // Return
        return $status;
    }
}
