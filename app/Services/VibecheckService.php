<?php

namespace App\Services;

use App\Models\VibeCheck;
use App\Models\Stamp;
use App\Models\Event;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class VibecheckService 
{
    public const int POINTS_FOR_VIBECHECK = 5;

    public function getEventFeedback(Event $event): array
    {
        $vibechecks = $event->vibechecks()->with('user:id,name')->get();

        return [
            'event_title'    => $event->title,
            'total_reviews'  => $vibechecks->count(),
            'average_sound'  => round($vibechecks->avg('sound_score'), 1),
            'average_safety' => round($vibechecks->avg('safe_space_score'), 1),
            'data'           => $vibechecks
        ];
    }

    public function store(User $user, Event $event, array $data): Vibecheck
    {
        
        if ($event->date >= now()->toDateString()) {
            throw ValidationException::withMessages([
                'event' => ['You cannot review an event that has not ended yet.']
            ]);
        }

        $hasStamp = Stamp::where('user_id', $user->id)
            ->where('event_id', $event->id)
            ->exists();

        if (!$hasStamp) {
            throw ValidationException::withMessages([
                'event' => ['You must collect the event stamp before leaving a vibecheck.']
            ]);
        }

        $alreadyVoted = VibeCheck::where('user_id', $user->id)
            ->where('event_id', $event->id)
            ->exists();

        if ($alreadyVoted) {
            throw ValidationException::withMessages([
                'event' => ['You have already reviewed this event.']
            ]);
        }

        $vibecheck = VibeCheck::create([
            'event_id'         => $event->id,
            'user_id'          => $user->id,
            'sound_score'      => $data['sound_score'],
            'safe_space_score' => $data['safe_space_score'],
            'comment'          => $data['comment'] ?? null,
        ]);

        $user->increment('points', self::POINTS_FOR_VIBECHECK);

        return $vibecheck;
    }

    public function delete(int $id): bool
    {
        return VibeCheck::findOrFail($id)->delete();
    }
}