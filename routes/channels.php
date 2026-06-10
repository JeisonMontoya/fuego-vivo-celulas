<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('general-chat', function ($user) {
    if (auth()->check()) {
        return ['id' => $user->id, 'name' => $user->name, 'photo_path' => $user->photo_path];
    }

    return false;
});
