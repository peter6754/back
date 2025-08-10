<?php

namespace App\Services;

use App\Models\Secondaryuser as User;
use App\Events\UnreadMessagesStatus;
use App\Models\Conversation;
use App\Events\MessageSent;
use App\Models\ChatMessage;
use App\Models\Gifts;
use App\Services\WebSocketService;

class ChatService
{
    public function __construct(
        private WebSocketService $webSocketService
    ) {}
    /**
     * @param array $payload
     * @return void
     */
    public function sendMedia(array $payload)
    {
        foreach ($payload['media'] as $media) {
            $this->emitMessage(
                $payload['sender_id'],
                (int)$payload['conversation_id'],
                $media,
                'media'
            );
        }
    }

    /**
     * @param array $payload
     * @param bool $isFirst
     * @return void
     */
    public function sendGift(array $payload, bool $isFirst = false)
    {
        $conversation = Conversation::where(function ($query) use ($payload) {
            $query->where('user1_id', $payload['user_id'])
                ->where('user2_id', $payload['sender_id']);
        })
            ->orWhere(function ($query) use ($payload) {
                $query->where('user1_id', $payload['sender_id'])
                    ->where('user2_id', $payload['user_id']);
            })
            ->first();
        $gift = Gifts::findOrFail($payload['gift_id']);

        if (!$conversation) {
            $conversation = Conversation::create([
                'user1_id' => $payload['sender_id'],
                'user2_id' => $payload['user_id']
            ]);
        }

        $this->emitMessage(
            $payload['sender_id'],
            $conversation->id,
            $gift->message,
            'gift',
            $gift->image,
            null,
            $isFirst
        );
    }

    /**
     * @param string $senderId
     * @param int $conversationId
     * @param string $message
     * @param string $type
     * @param string|null $gift
     * @param string|null $contactType
     * @param bool $isFirst
     * @return array[]|null[]
     */
    public function emitMessage(
        string  $senderId,
        int     $conversationId,
        string  $message,
        string  $type,
        ?string $gift = null,
        ?string $contactType = null,
        bool    $isFirst = false
    )
    {
        try {
            $conversation = Conversation::findOrFail($conversationId);

            $receiverId = $conversation->user1_id != $senderId
                ? $conversation->user1_id
                : $conversation->user2_id;

//            $receiverSettings = UserSettings::with(['user.deviceTokens', 'user'])
//                ->where('user_id', $receiverId)
//                ->first();

            $senderInfo = User::findOrFail($senderId, ['name', 'age']);

            // Create message
            ChatMessage::create([
                'sender_id' => $senderId,
                'receiver_id' => $receiverId,
                'message' => $message,
                'conversation_id' => $conversationId,
                'type' => $type,
                'gift' => $gift,
                'contact_type' => $contactType
            ]);

            // Broadcast events via WebSocket
            $this->webSocketService->sendMessageToUser($receiverId, [
                'conversation_id' => $conversationId,
                'data' => [
                    'type' => $type,
                    'message' => $message,
                    'gift' => $gift,
                    'contact_type' => $contactType,
                    'sender_info' => [
                        'id' => $senderId,
                        'name' => $senderInfo->name,
                        'age' => $senderInfo->age
                    ]
                ]
            ]);

            // Also send via traditional broadcasting for backward compatibility
            broadcast(new MessageSent($receiverId, [
                'conversation_id' => $conversationId,
                'data' => [
                    'type' => $type,
                    'message' => $message,
                    'gift' => $gift,
                    'contact_type' => $contactType
                ]
            ]));

            broadcast(new UnreadMessagesStatus($receiverId, true));

            // Send notifications
//            if (!$isFirst && $receiverSettings->new_messages_push) {
//                $tokens = $receiverSettings->user->deviceTokens->pluck('token')->toArray();
//                // Implement your Expo notification service here
//                app('expo.service')->sendPushNotification(
//                    $tokens,
//                    "Пользователь {$senderInfo->name}, {$senderInfo->age} написал вам сообщение! Зайдите, чтобы посмотреть.",
//                    "Новое сообщение!",
//                    ['conversation_id' => $conversationId]
//                );
//            }
//
//            if ($receiverSettings->new_messages_email && $receiverSettings->user->email) {
//                Mail::send('emails.new_message', [
//                    'senderName' => $senderInfo->name,
//                    'senderAge' => $senderInfo->age
//                ], function ($message) use ($receiverSettings) {
//                    $message->to($receiverSettings->user->email)
//                        ->subject('Новое сообщение!');
//                });
//            }

            return ['error' => null];
        } catch (\Exception $e) {
            echo $e->getMessage();
            return [
                'error' => [
                    'message' => 'Conversation not found',
                    'status' => 406
                ]
            ];
        }
    }
}
