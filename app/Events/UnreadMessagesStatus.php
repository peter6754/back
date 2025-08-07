<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UnreadMessagesStatus implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public string $receiverId,
        public bool $hasUnread
    ) {}

    public function broadcastOn()
    {
        return new Channel('user.'.$this->receiverId);
    }

    public function broadcastAs()
    {
        return 'unread.messages.status';
    }

    public function broadcastWith()
    {
        return [
            'has_unread' => $this->hasUnread,
            'timestamp' => now()->toISOString(),
        ];
    }
}
