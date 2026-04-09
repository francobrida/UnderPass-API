<?php

use App\Models\Event;
use App\Models\User;
use Laravel\Passport\Passport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use function Pest\Laravel\postJson;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseCount;

uses(RefreshDatabase::class);

test('a user can leave a vibecheck on a finished event', function () {
    $user = User::factory()->create(['name' => 'Reviewer']);
    
    $event = Event::factory()->create([
        'date' => now()->subDay()->toDateString(),
        'is_verified' => true
    ]);

    /** @var \App\Models\User $user */
    Passport::actingAs($user);

    $payload = [
        'rating' => 5,
        'comment' => 'The vibe was incredible!',
    ];

    $response = postJson("/api/v1/events/{$event->id}/vibechecks", $payload);

    $response->assertStatus(201);
    assertDatabaseHas('vibechecks', [
        'user_id' => $user->id,
        'event_id' => $event->id,
        'rating' => 5
    ]);
});

test('cannot leave a vibecheck with an invalid rating', function () {
    $user = User::factory()->create(['name' => 'Reviewer']);
    $event = Event::factory()->create(['date' => now()->subDay()->toDateString()]);

    /** @var \App\Models\User $user */
    Passport::actingAs($user);

    
    $response = postJson("/api/v1/events/{$event->id}/vibechecks", [
        'rating' => 10, 
        'comment' => 'Too high'
    ]);

    $response->assertStatus(422); 
});

test('cannot leave a vibecheck for an event that has not happened yet', function () {
    $user = User::factory()->create(['name' => 'Reviewer']);
    $futureEvent = Event::factory()->create([
        'date' => now()->addDay()->toDateString() 
    ]);

    /** @var \App\Models\User $user */
    Passport::actingAs($user);

    $response = postJson("/api/v1/events/{$futureEvent->id}/vibechecks", [
        'rating' => 4,
        'comment' => 'I am from the future'
    ]);

    $response->assertStatus(422);
    $response->assertJsonPath('message', 'You cannot review an event that has not ended yet.');
});

test('a user cannot review the same event twice', function () {
    $user = User::factory()->create(['name' => 'Reviewer']);
    $event = Event::factory()->create(['date' => now()->subDay()->toDateString()]);

    /** @var \App\Models\User $user */
    Passport::actingAs($user);

    postJson("/api/v1/events/{$event->id}/vibechecks", ['rating' => 5, 'comment' => 'First']);
    
    $response = postJson("/api/v1/events/{$event->id}/vibechecks", ['rating' => 1, 'comment' => 'Second']);

    $response->assertStatus(422);
    assertDatabaseCount('vibechecks', 1);
});

test('vibecheck comment is optional but rating is mandatory', function () {
    $user = User::factory()->create(['name' => 'Reviewer']);
    $event = Event::factory()->create(['date' => now()->subDay()->toDateString()]);

    /** @var \App\Models\User $user */
    Passport::actingAs($user);

    $response = postJson("/api/v1/events/{$event->id}/vibechecks", ['rating' => 4]);
    $response->assertStatus(201);
 
    $responseError = postJson("/api/v1/events/{$event->id}/vibechecks", ['comment' => 'Nice']);
    $responseError->assertStatus(422);
});