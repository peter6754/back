<?php

use Illuminate\Support\Facades\Broadcast;

// Broadcasting authorization routes
Broadcast::routes(['middleware' => ['auth']]);

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});
