<?php

namespace App\Services\Notifications\Providers;

use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Client;

class TelegramProvider
{
    /**
     * @var string
     */
    protected string $apiUrl = 'https://api.telegram.org/bot{{TELEGRAM_TOKEN}}/sendMessage';

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
                'Accept' => 'application/json'
            ]
        ]);
    }

    /**
     * @param array $params
     * @return bool
     * @throws GuzzleException
     */
    public function sendMessage(array $params): bool
    {
        // Build URL
        $this->apiUrl = str_replace('{{TELEGRAM_TOKEN}}',
            config('services.telegram.client_secret'),
            $this->apiUrl
        );

        // Send request
        $response = $this->client->post($this->apiUrl, [
            'json' => [
                'text' => "*{$params['title']}*" . PHP_EOL . $params['body'],
                'parse_mode' => 'MarkdownV2',
                'chat_id' => $params['to']
            ]
        ]);

        // Response
        $getResponse = json_decode(
            $response->getBody(),
            true
        );

        // Return
        return !empty($getResponse['ok']);
    }
}
