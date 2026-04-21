<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class Vouch extends Model
{

    protected $fillable = ['user_id', 'event_id'];

    public $incrementing = false;
    protected $keyType = 'string'; 

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }
}
