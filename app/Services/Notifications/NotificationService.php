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

                Log::info("[NotificationService] provider {$provider}, push token {$token}");

                // Send message
                if (app(NotificationManager::class)->sendMessage($provider, [
                    'data' => $additionalData,
                    'sound' => 'default',
                    'body' => $message,
                    'title' => $title,
                    'to' => $token,
                ]) === false) {
                    Log::error("[NotificationService] provider {$provider}, push token {$token} not sent");
                }
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
     * @param $token
     * @return string
     */
    protected function getProvider($token): string
    {
        if (preg_match('/^[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}$/i', $token)) {
            return 'onesignal';
        } else if (preg_match('/^ExponentPushToken\[[a-zA-Z0-9_-]+\]$/', $token)) {
            return 'expo';
        } else if (is_numeric($token)) {
            return 'telegram';
        }

        return 'unknown';
    }
}
