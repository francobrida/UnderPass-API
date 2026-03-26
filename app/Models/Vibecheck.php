<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory; 
use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable([ 'event_id', 'user_id','sound_score','safe_space_score','comment'])]

class Vibecheck extends Model
{
    use HasFactory;
}
