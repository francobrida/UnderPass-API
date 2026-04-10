<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Support\Carbon;

#[Fillable(['user_id', 'title', 'lineup', 'description', 'date', 'start_time', 'end_time', 'price', 'price_info', 'ticket_link',
'location_name', 'neighborhood', 'is_verified', 'flyer', 'is_18_plus'])]

class Event extends Model
{
    use HasFactory;
    
    public function organizer() {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function genres() {
        return $this->belongsToMany(Genre::class, 'event_genre');
    }

    public function vibeChecks() {
        return $this->hasMany(VibeCheck::class);
    }

    public function vouches() {
        return $this->belongsToMany(User::class, 'vouches');
    }

    public function getVouchProgressAttribute()
    {
        return $this->vouches()->count();
    }

    public function stamps() {
        return $this->hasMany(Stamp::class);
    }
    
    public function isWithinStampScannableWindow(): bool
    {
        $now = now();
        $eventDate = Carbon::parse($this->date);

        return $now->between(
            $eventDate->copy()->startOfDay(), 
            $eventDate->copy()->addDay()->endOfDay()
        );
    }

}
