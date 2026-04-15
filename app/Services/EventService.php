<?php

namespace App\Services;

use App\Models\Event;
use App\Models\User;
use App\Enums\UserRole;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class EventService {

    public const int VOUCHES_TO_VERIFY = 3;

    public function filter(array $eventData) 
    {
        $query = Event::with(['organizer', 'genres']);

        if (isset($eventData['verified']) && $eventData['verified'] === 'false') {
            // WAITING ROOM
            $query->where('is_verified', false)->latest();
        } else {
            // MAIN DASHBOARD
            $query->where('is_verified', true)
                ->where('date', '>=', now()->toDateString()); 
        }

       
        if (!empty($eventData['search'])) {
            $search = '%' . $eventData['search'] . '%';
            $query->where(function($q) use ($search) {
                $q->where('title', 'like', $search)
                ->orWhere('lineup', 'like', $search);
            });
        }

        if (!empty($request['neighborhood'])) {
            $query->where('neighborhood', $request['neighborhood']);
        }

        if (!empty($request['genre'])) {
            $query->whereHas('genres', function($genreQuery) use ($request) {
                $genreQuery->where('genres.id', $request['genre']);
            });
        }

        $order = $request['price'] ?? null;

        if ($order === 'asc' || $order === 'desc') {
            $query->orderBy('price', $order);
        } else {
            $query->orderBy('date', 'asc');
        }

        return $query->get();

    }

    public function store($user, array $eventData, $file = null)
    {
        $eventData['price_info'] = $this->processPriceInfo($eventData);
        $eventData['stamp_token'] = Str::random(32); 
        $eventData['is_verified'] = false; 

        if ($file) {
            $eventData['flyer'] = $file->store('flyers', 'public');
        }

        $event = $user->events()->create($eventData);

        if (!empty($eventData['genres'])) {
            $event->genres()->attach($eventData['genres']);
        }

        return $event;
    }

    private function processPriceInfo(array $eventData): string 
    {
        if (!empty($eventData['price_info'])) return $eventData['price_info'];
        return ($eventData['price'] ?? 0) == 0 ? 'Entrada gratuita' : '';
    }

    public function update(User $user, Event $event, array $eventData, $file = null)
    {
        $eventData['price_info'] = $this->processPriceInfo($eventData);

    
        if ($file) {
            if ($event->flyer) {
                Storage::disk('public')->delete($event->flyer);
            }
            $eventData['flyer'] = $file->store('flyers', 'public');
        }

      
        if ($user->role !== UserRole::ADMIN) {
            $eventData['is_verified'] = false; 
        } else {
            $eventData['is_verified'] = $eventData['is_verified'] ?? $event->is_verified;
        }

        $event->update($eventData);

        if (isset($eventData['genres'])) {
            $event->genres()->sync($eventData['genres']);
        }

        return $event;
    }

    public function delete($event)
    {
        if ($event->flyer) {
            Storage::disk('public')->delete($event->flyer);
        }
        
        return $event->delete();
    }

    public function vouch($event, $user)
    {
        $event->vouches()->attach($user->id);

        return $this->verifyEvent($event);
    }

    public function processFlyer(Event $event, $file) {
        if ($file) {
            if ($event->flyer) {
                Storage::disk('public')->delete($event->flyer);
            }
            return $file->store('flyers', 'public');
        }
        return $event->flyer;
    }

    public function verifyEvent(Event $event): bool 
    {
        if ($event->is_verified) return true;

        if ($event->vouches()->count() >= self::VOUCHES_TO_VERIFY) {
            $event->update(['is_verified' => true]);

            $owner = $event->organizer; 
            
            if ($owner && $owner->role === UserRole::CLUBBER) {
                $owner->update(['role' => UserRole::ORGANIZER]);
            }
            return true;
        }
        return false;
    }

}