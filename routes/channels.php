<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Purok Leader private channel for receiving concern assignments
Broadcast::channel('purok-leader.{purokLeaderId}', function ($user, $purokLeaderId) {
    // Only allow access if the user is a purok leader (role_id = 2) and the channel matches their ID
    return (int) $user->id === (int) $purokLeaderId && (int) $user->role_id === 2;
});
