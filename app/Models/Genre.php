<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable(['name', 'slug'])]

class Genre extends Model
{
    public function events() 
    {
    return $this->belongsToMany(Event::class, 'event_genre');
    }

}
