<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable(['user_id', 'event_id'])]

class Vouch extends Model
{
    public $incrementing = false; 
    protected $primaryKey = ['user_id', 'event_id']; // Composite primary key
}
