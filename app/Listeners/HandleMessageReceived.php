<?php

namespace App\Listeners;

use App\Events\MessageReceived;
use App\Events\MessageSent;
use Illuminate\Support\Facades\Log;

class HandleMessageReceived
{
    public function handle(MessageReceived $event): void
    {
        Log::info('WebSocket message received', [
            'user_id' => $event->userId,
            'channel' => $event->channel,
            'data' => $event->data,
        ]);

        // Обработка различных типов сообщений
        $messageType = $event->data['type'] ?? 'unknown';

        switch ($messageType) {
            case 'chat_message':
                $this->handleChatMessage($event);
                break;
            case 'typing_indicator':
                $this->handleTypingIndicator($event);
                break;
            case 'ping':
                $this->handlePing($event);
                break;
            default:
                Log::warning('Unknown message type received', ['type' => $messageType]);
        }
    }

    private function handleChatMessage(MessageReceived $event): void
    {
        // Логика обработки чат сообщения
        // Сохранение в базу данных, отправка уведомлений и т.д.

        // Отправка ответного сообщения
        if (isset($event->data['recipient_id'])) {
            event(new MessageSent($event->data['recipient_id'], [
                'conversation_id' => $event->data['conversation_id'] ?? null,
                'data' => [
                    'type' => 'message_delivered',
                    'message_id' => $event->data['message_id'] ?? null,
                    'sender_id' => $event->userId,
                ],
            ]));
        }
    }

    private function handleTypingIndicator(MessageReceived $event): void
    {
        // Пересылка индикатора печати другим участникам
        if (isset($event->data['recipient_id'])) {
            event(new MessageSent($event->data['recipient_id'], [
                'conversation_id' => $event->data['conversation_id'] ?? null,
                'data' => [
                    'type' => 'typing_indicator',
                    'sender_id' => $event->userId,
                    'is_typing' => $event->data['is_typing'] ?? false,
                ],
            ]));
        }
    }

    private function handlePing(MessageReceived $event): void
    {
        // Отправка pong ответа
        event(new MessageSent($event->userId, [
            'conversation_id' => null,
            'data' => [
                'type' => 'pong',
                'timestamp' => now()->toISOString(),
            ],
        ]));
    }
}
