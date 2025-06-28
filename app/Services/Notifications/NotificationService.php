<?php

namespace App\Services\Notifications;

use Illuminate\Support\Facades\Log;

class NotificationService
{
    /**
     * @param mixed $tokens
     * @param string $message
     * @param string $title
     * @param array $additionalData
     * @return bool
     */
    public function sendPushNotification(mixed $tokens, string $message, string $title, array $additionalData = []): bool
    {
        try {
            // Checking push tokens
            if (empty($tokens)) {
                Log::error('[NotificationService] Empty tokens to send push notification');
            }

            // String to array
            if (is_string($tokens)) {
                $tokens = [$tokens];
            }

            // Parse tokens
            foreach ($tokens as $token) {
                // Detect current provider
                $provider = $this->getProvider($token);

                // Provider not found
                if ($provider === 'unknown') {
                    Log::error("Invalid push token {$token}");
                    continue;
                }

                // Send message
                app(NotificationManager::class)->sendMessage($provider, [
                    'data' => $additionalData,
                    'sound' => 'default',
                    'body' => $message,
                    'title' => $title,
                    'to' => $token,
                ]);
            }
            return true;
        } catch (\Exception $e) {
            Log::error('[NotificationService] Exception error', [
                $e
            ]);
            return false;
        }
    }

    /**
     * @param array $messages
     * @param int $chunkSize
     * @return array
     */
    public static function chunkPushNotifications(array $messages, int $chunkSize = 100): array
    {
        return array_chunk($messages, $chunkSize);
    }

    /**
     * @param $token
     * @return string
     */
    protected function getProvider($token): string
    {
        if (preg_match('/^[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}$/i', $token)) {
            return 'onesignal';
        } else if (preg_match('/^ExponentPushToken\[[a-zA-Z0-9_-]+\]$/', $token)) {
            return 'expo';
        }
        return 'unknown';
    }
}
