<?php

namespace App\Policies;

use Illuminate\Auth\Access\Response;
use App\Models\Event;
use App\Models\User;
use App\Enums\UserRole;

class EventPolicy
{
    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Event $event): Response
    {
        return $user->id === $event->user_id || $user->role === UserRole::ADMIN
            ? Response::allow()
            : Response::deny('You are not authorized to edit this event');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Event $event): bool
    {
        return $user->id === $event->user_id || $user->role === UserRole::ADMIN;
    }
}