<?php

namespace App\Services;

use App\Models\Stamp;
use App\Models\Event;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;

class StampService
{
    public function getUserStamps(int $userId): Collection
    {
        return Stamp::where('user_id', $userId)
            ->with('event')
            ->latest('scanned_at')
            ->get();
    }

    
    public function collect(User $user, string $token): Stamp
    {
        
        $event = Event::where('stamp_token', $token)->first();

        if (!$event) {
            throw ValidationException::withMessages(['token' => ['Invalid QR code or stamp token.']
            ]);
        }

        if (!$event->isWithinStampScannableWindow()) {
            throw ValidationException::withMessages(['token' => ['QR code is not active or has expired.']
            ]);
        }

        if ($this->hasStamp($user, $event)) {
            throw ValidationException::withMessages(['token' => ['You have already collected this stamp!']
            ]);
        }


        return Stamp::create([
            'user_id'    => $user->id,
            'event_id'   => $event->id,
            'scanned_at' => now(),
        ]);
    }

    public function hasStamp(User $user, Event $event): bool
    {
        return Stamp::where('user_id', $user->id)
            ->where('event_id', $event->id)
            ->exists();
    }

    public function delete(int $id): bool
    {
        return Stamp::findOrFail($id)->delete();
    }

}