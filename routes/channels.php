<?php

use App\Models\Conversation;
use Illuminate\Support\Facades\Broadcast;

// Broadcasting authorization routes
Broadcast::routes(['middleware' => ['auth']]);

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Private user channel authorization
Broadcast::channel('user.{userId}', function ($user, $userId) {
    return $user->id === $userId;
});

// Private conversation channel authorization
Broadcast::channel('conversation.{conversationId}', function ($user, $conversationId) {
    $conversation = Conversation::find($conversationId);

    if (! $conversation) {
        return false;
    }

    // Check if user is participant in this conversation
    return $conversation->user1_id === $user->id || $conversation->user2_id === $user->id;
});
