<?php

use App\Models\Event;
use App\Models\User;
use App\Enums\UserRole;
use App\Models\Vouch;
use Laravel\Passport\Passport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use function Pest\Laravel\{getJson};

uses(RefreshDatabase::class);

test('admin can see list of vouches for any event', function () {
    
    $owner = User::factory()->create(['name' => 'Organizador']);
    
    $event = Event::factory()->create([
        'user_id' => $owner->id, 
        'is_verified' => true
    ]);
    
   
    User::factory()->count(3)->create(['name' => 'Voucher User'])->each(function ($user) use ($event) {
       
        Vouch::create([
            'user_id' => $user->id,
            'event_id' => $event->id
        ]);
    });

  
    $admin = User::factory()->create([
        'name' => 'Admin User',
        'role' => \App\Enums\UserRole::ADMIN
    ]);
    /**  @var \App\Models\User $admin */
    Passport::actingAs($admin);

    $response = $this->getJson("/api/v1/events/{$event->id}/vouches");

    $response->assertStatus(200)
             ->assertJsonCount(3, 'data');
});


test('returns 404 if event for vouches does not exist', function () {
    $admin = User::factory()->create([
        'name' => 'Admin Test',
        'role' => \App\Enums\UserRole::ADMIN
    ]);
    
    /**  @var \App\Models\User $admin */
    Passport::actingAs($admin);
    
    $response = $this->getJson("/api/v1/events/999999/vouches");
    
    $response->assertStatus(404);
});
