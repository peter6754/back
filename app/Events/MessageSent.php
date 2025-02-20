<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageSent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public string $receiverId,
        public array $messageData
    ) {}

    public function broadcastOn()
    {
        return [
            new PrivateChannel('user.'.$this->receiverId),
            new PrivateChannel('conversation.'.$this->messageData['conversation_id'])
        ];
    }

    public function broadcastAs()
    {
        return 'message.sent';
    }

    public function broadcastWith()
    {
        return [
            'conversation_id' => $this->messageData['conversation_id'],
            'message' => $this->messageData['data'],
            'timestamp' => now()->toISOString(),
            'sender_id' => $this->messageData['data']['sender_info']['id'] ?? null,
        ];
    }
}
