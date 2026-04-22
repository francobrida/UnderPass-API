<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Relations\{BelongsTo, BelongsToMany, HasMany};
use Illuminate\Support\Carbon;

#[Fillable(['user_id', 'title', 'lineup', 'description', 'date', 'start_time', 'end_time', 'price', 'price_info', 'ticket_link',
'location_name', 'neighborhood', 'is_verified', 'flyer', 'is_18_plus'])]

class Event extends Model
{
    use HasFactory;

    protected $casts = [
        'date' => 'date',
        'is_verified' => 'boolean',
        'is_18_plus' => 'boolean',
    ];

    public function getStartDateTimeAttribute(): Carbon
    {
        return Carbon::parse($this->date->format('Y-m-d') . ' ' . $this->start_time);
    }

    public function getEndDateTimeAttribute(): Carbon
    {
        $endDatetime = Carbon::parse($this->date->format('Y-m-d') . ' ' . $this->end_time);
        
        if ($this->end_time < $this->start_time) {
            $endDatetime->addDay();
        }
        
        return $endDatetime;
    }
    
    public function organizer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function genres(): BelongsToMany
    {
        return $this->belongsToMany(Genre::class, 'event_genre');
    }

    public function vibeChecks(): HasMany
    {
        return $this->hasMany(VibeCheck::class);
    }

    public function vouches(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'vouches');
    }

    public function getVouchProgressAttribute(): int
    {
        return $this->vouches()->count();
    }

    public function stamps(): HasMany
    {
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

    public function isReadyForVibeCheck(): bool
    {
        $now = now();
        $vibeCheckOpening = $this->end_date_time->copy()->addHours(6);

        return $now->greaterThanOrEqualTo($vibeCheckOpening);
    }

}
