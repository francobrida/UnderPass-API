<?php

use App\Models\Event;
use App\Models\User;
use App\Models\Stamp;
use Laravel\Passport\Passport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use function Pest\Laravel\postJson;
use function Pest\Laravel\getJson;

uses(RefreshDatabase::class);

test('a user can collect a stamp and see event details in the collection', function () {
    $user = User::factory()->create();
    
    $event = Event::factory()->create([
        'title' => 'Underpass Techno Night',
        'date' => now()->subDay()->toDateString() 
    ]);

    /**  @var \App\Models\User $user */
    Passport::actingAs($user);

    $response = postJson('/api/v1/stamps', ['event_id' => $event->id]);
    
    $response->assertStatus(201);

    $listResponse = getJson('/api/v1/stamps');
    $listResponse->assertStatus(200)
                 ->assertJsonPath('data.0.event.title', 'Underpass Techno Night');
});

test('a user cannot collect a stamp for an event that has not started yet', function () {
    $user = User::factory()->create();
    $futureEvent = Event::factory()->create(['date' => now()->addDays(5)->toDateString()]);

    /**  @var \App\Models\User $user */
    Passport::actingAs($user);

    $response = postJson('/api/v1/stamps', ['event_id' => $futureEvent->id]);

    $response->assertStatus(422);
    
    $response->assertJsonPath('message', 'QR code is not active or has expired.');
});

test('a user cannot collect a stamp if the event ended more than 24h ago', function () {
    $user = User::factory()->create();
    $oldEvent = Event::factory()->create(['date' => now()->subDays(10)->toDateString()]);

    /**  @var \App\Models\User $user */
    Passport::actingAs($user);

    $response = postJson('/api/v1/stamps', ['event_id' => $oldEvent->id]);

    $response->assertStatus(422);
  
    $response->assertJsonPath('message', 'QR code is not active or has expired.');
});

