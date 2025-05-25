<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use GuzzleHttp\Client;

class ExpoNotificationService
{
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
