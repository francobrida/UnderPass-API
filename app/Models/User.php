<?php

namespace App\Models;

use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;


class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = ['name', 'email', 'password', 'role', 'points'];
    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class, 
            'points' => 'integer',
        ];
    }

    public function events()
    {
        return $this->hasMany(Event::class);
    }

    public function stamps(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Stamp::class);
    }

    public function stampedEvents() {
        return $this->belongsToMany(Event::class, 'event_user_stamps')->withPivot('stamped_at');
    }
}
