<?php

namespace App\Observers;

use App\Models\Vouch;
use App\Enums\UserRole;

class VouchObserver
{
    private const int VOUCHES_REQUIRED_FOR_VERIFICATION = 3;

    public function created(Vouch $vouch): void
    {
        
        $event = $vouch->event; 

        $vouchCount = $event->vouches()->count();

        if ($vouchCount >= self::VOUCHES_REQUIRED_FOR_VERIFICATION && !$event->is_verified) {
            $event->update(['is_verified' => true]);

            $organizer = $event->organizer;
            
            if ($organizer && $organizer->role === UserRole::CLUBBER) {
                $organizer->update(['role' => UserRole::ORGANIZER]);
            }
        }
    }
}