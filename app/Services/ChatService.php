<?php

namespace App\Services;

use App\Models\Secondaryuser as User;
use App\Models\Conversation;
use App\Events\MessageSent;
use App\Models\ChatMessage;
use App\Models\Gifts;
use App\Models\UserReaction;
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
            // Check for mutual likes before creating conversation
            if (!UserReaction::haveMutualLikes($payload['sender_id'], $payload['user_id'])) {
                throw new \Exception('Conversation can only be created after mutual likes');
            }
            
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
     * @param string|null $fileUrl
     * @param string|null $fileType
     * @return array[]|null[]
     */
    public function emitMessage(
        string  $senderId,
        int     $conversationId,
        string  $message,
        string  $type,
        ?string $gift = null,
        ?string $contactType = null,
        bool    $isFirst = false,
        ?string $fileUrl = null,
        ?string $fileType = null
    )
    {
        try {
            // Check if conversation exists and user has access
            $conversation = Conversation::where('id', $conversationId)
                ->where(function ($query) use ($senderId) {
                    $query->where('user1_id', $senderId)
                          ->orWhere('user2_id', $senderId);
                })
                ->first();

            if (!$conversation) {
                return [
                    'error' => [
                        'message' => 'Conversation not found or access denied',
                        'status' => 404
                    ]
                ];
            }

            $receiverId = $conversation->user1_id != $senderId
                ? $conversation->user1_id
                : $conversation->user2_id;

//            $receiverSettings = UserSettings::with(['user.deviceTokens', 'user'])
//                ->where('user_id', $receiverId)
//                ->first();

            $senderInfo = User::find($senderId, ['name', 'age']);
            
            if (!$senderInfo) {
                return [
                    'error' => [
                        'message' => 'Sender not found',
                        'status' => 404
                    ]
                ];
            }

            // Create message
            $chatMessage = ChatMessage::create([
                'sender_id' => $senderId,
                'receiver_id' => $receiverId,
                'message' => $message,
                'conversation_id' => $conversationId,
                'type' => $type,
                'gift' => $gift,
                'contact_type' => $contactType
            ]);

            // Broadcast events via WebSocket
            $messageData = [
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
            ];

            // Add file info for media messages
            if ($type === 'media' && $fileUrl) {
                $messageData['data']['file_url'] = $fileUrl;
                $messageData['data']['file_type'] = $fileType;
            }

            $this->webSocketService->sendMessageToUser($receiverId, $messageData);

            // Also send via traditional broadcasting for backward compatibility
            broadcast(new MessageSent($receiverId, $messageData));

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
            \Log::error('Failed to emit message: ' . $e->getMessage());
            return [
                'error' => [
                    'message' => 'Failed to send message: ' . $e->getMessage(),
                    'status' => 500
                ]
            ];
        }
    }
}
