<?php

namespace App\Services;

use App\Events\MessageReceived;
use App\Events\MessageSent;
use Illuminate\Support\Facades\Log;

class WebSocketService
{
    /**
     * Обработка входящего WebSocket сообщения
     */
    public function handleIncomingMessage(string $userId, string $channel, array $data): void
    {
        try {
            event(new MessageReceived($userId, $channel, $data));
        } catch (\Exception $e) {
            Log::error('Error handling incoming WebSocket message', [
                'user_id' => $userId,
                'channel' => $channel,
                'data' => $data,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Отправка сообщения пользователю
     */
    public function sendMessageToUser(string $userId, array $messageData): void
    {
        try {
            event(new MessageSent($userId, $messageData));
        } catch (\Exception $e) {
            Log::error('Error sending WebSocket message to user', [
                'user_id' => $userId,
                'message_data' => $messageData,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Отправка broadcast сообщения на канал
     */
    public function broadcastToChannel(string $channel, array $messageData): void
    {
        try {
            broadcast(new MessageSent('', $messageData))->toOthers();
        } catch (\Exception $e) {
            Log::error('Error broadcasting WebSocket message to channel', [
                'channel' => $channel,
                'message_data' => $messageData,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Отправка уведомления о статусе онлайн
     */
    public function sendOnlineStatus(string $userId, bool $isOnline): void
    {
        $this->sendMessageToUser($userId, [
            'data' => [
                'type' => 'online_status',
                'user_id' => $userId,
                'is_online' => $isOnline,
                'timestamp' => now()->toISOString(),
            ],
        ]);
    }

    /**
     * Отправка системного уведомления
     */
    public function sendSystemNotification(string $userId, string $message, array $additionalData = []): void
    {
        $this->sendMessageToUser($userId, [
            'data' => array_merge([
                'type' => 'system_notification',
                'message' => $message,
                'timestamp' => now()->toISOString(),
            ], $additionalData),
        ]);
    }

    /**
     * Ping-pong для проверки соединения
     */
    public function sendPing(string $userId): void
    {
        $this->sendMessageToUser($userId, [
            'conversation_id' => null,
            'data' => [
                'type' => 'pong',
                'timestamp' => now()->toISOString(),
            ],
        ]);
    }
}
