<?php

use Illuminate\Support\Facades\Broadcast;

// Broadcasting authorization routes
Broadcast::routes(['middleware' => ['auth:api']]);

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Приватный канал для пользователя с поддержкой client events
Broadcast::channel('user.{userId}', function ($user, $userId) {
    return (int) $user->id === (int) $userId ? ['id' => $user->id, 'name' => $user->name] : false;
});
