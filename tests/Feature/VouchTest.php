<?php

use App\Models\Event;
use App\Models\User;
use Laravel\Passport\Passport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use function Pest\Laravel\postJson;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseCount;

uses(RefreshDatabase::class);

test('a user can vouch an event', function () {
    
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $event = Event::factory()->create(['user_id' => $otherUser->id]);

    /** @var \App\Models\User $user */
    Passport::actingAs($user);
    
    $response = postJson("/api/v1/events/{$event->id}/vouches");

    $response->assertStatus(201); 
    
    assertDatabaseHas('vouches', [
        'user_id' => $user->id,
        'event_id' => $event->id
    ]);
});

test('a user cannot vouch their own event', function () {
    
    $user = User::factory()->create();
    
    /** @var \App\Models\User $user */
    Passport::actingAs($user);

    $event = Event::factory()->create(['user_id' => $user->id,]);

    $response = postJson("/api/v1/events/{$event->id}/vouches");

    $response->assertStatus(403); 
    
    assertDatabaseCount('vouches', 0);
});


test('a user cannot vouch the same event twice', function () {
    $user = User::factory()->create();
    $event = Event::factory()->create(); 
    
    /** @var \App\Models\User $user */
    Passport::actingAs($user);

    postJson("/api/v1/events/{$event->id}/vouches");
    
    $response = postJson("/api/v1/events/{$event->id}/vouches");

    $response->assertStatus(422);
    assertDatabaseCount('vouches', 1); 
});

test('an event becomes verified automatically upon receiving the 3rd vouch', function () {

    $event = Event::factory()->create(['is_verified' => false]);
    
    $users = User::factory()->count(2)->create([
        'name' => 'Votante Antiguo' 
    ]);

    foreach ($users as $user) {
        \App\Models\Vouch::create([
            'user_id' => $user->id,
            'event_id' => $event->id
        ]);
    }

    $thirdUser = User::factory()->create([
        'name' => 'VotanteDecisivo'
    ]);

    /** @var \App\Models\User $thirdUser */
    Passport::actingAs($thirdUser);

    $response = postJson("/api/v1/events/{$event->id}/vouches");

    $response->assertStatus(201);
    
    $event->refresh();
    expect((bool)$event->is_verified)->toBeTrue();
});


test('cannot vouch an event that has already ended', function () {
    $user = User::factory()->create();
    $pastEvent = Event::factory()->create([
        'date' => now()->subDay()->toDateString() // Ayer
    ]);

    /** @var \App\Models\User $user */
    Passport::actingAs($user);

    $response = postJson("/api/v1/events/{$pastEvent->id}/vouches");

    $response->assertStatus(422);
});