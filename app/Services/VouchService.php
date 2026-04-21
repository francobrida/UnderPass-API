<?php

namespace App\Services;

use App\Models\Event;
use App\Models\User;
use App\Models\Vouch;
use App\Enums\UserRole;
use Illuminate\Validation\ValidationException;

class VouchService
{

    public function addVouch(User $user, Event $event): array
    {
        
        if ($event->user_id === $user->id) {
            throw ValidationException::withMessages(['vouch' => 'You cannot vote for your own event.']);
        }

        if ($event->date < now()->toDateString()) {
            throw ValidationException::withMessages(['vouch' => 'You cannot vote for a past event.']);
        }

        $alreadyVouched = Vouch::where('user_id', $user->id)
            ->where('event_id', $event->id)
            ->exists();

        if ($alreadyVouched) {
            throw ValidationException::withMessages(['vouch' => 'You have already voted for this event.']);
        }

        Vouch::create([
            'user_id' => $user->id,
            'event_id' => $event->id,
        ]);

        $event->refresh();

        return [
            'count' => $event->vouches()->count(),
            'is_verified' => (bool) $event->is_verified
        ];
    }
}