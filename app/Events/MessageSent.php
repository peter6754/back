<?php

namespace App\Events;

use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\Channel;

class MessageSent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public string $receiverId,
        public array $messageData
    ) {}

    public function broadcastOn()
    {
        return new Channel('user.'.$this->receiverId);
    }

    public function broadcastAs()
    {
        return 'message';
    }
}
