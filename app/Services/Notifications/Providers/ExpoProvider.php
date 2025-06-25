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
    protected string $receiptsUrl = 'https://exp.host/--/api/v2/push/getReceipts';

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
    public function sendMessage(array $params)
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

        // Return
        return (!empty($getResponse['status']) && $getResponse['status'] === "ok");
    }
}
