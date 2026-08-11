<?php

use App\Models\LiveClass;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('live-class.{liveClassId}', function ($user, int $liveClassId) {
    $liveClass = LiveClass::query()->find($liveClassId);
    if (! $liveClass) {
        return false;
    }

    return $liveClass->canUserJoin($user);
});
