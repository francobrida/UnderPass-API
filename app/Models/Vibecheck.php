<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory; 
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VibeCheck extends Model
{
    use HasFactory;

    protected $table = 'vibe_checks'; 

    protected $fillable = [
        'event_id', 
        'user_id',
        'sound_score',
        'safe_space_score',
        'comment'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}