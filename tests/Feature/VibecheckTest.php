<?php

use App\Models\Event;
use App\Models\User;
use App\Models\Vibecheck;
use App\Enums\UserRole;
use Laravel\Passport\Passport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use function Pest\Laravel\{postJson, deleteJson, getJson, assertDatabaseHas, 
assertDatabaseCount, assertDatabaseMissing};

uses(RefreshDatabase::class);

test('a user can leave a vibecheck on a finished event', function () {
    $user = User::factory()->create(['name' => 'Reviewer']);
    $event = Event::factory()->create([
        'date' => now()->subDay()->toDateString(),
        'is_verified' => true
    ]);

    /** @var \App\Models\User $user */
    Passport::actingAs($user);

    $review = [
        'sound_score' => 5,
        'safe_space_score' => 4,
        'comment' => 'Niiiice, the bartender is an asshole though!',
    ];

    $response = postJson("/api/v1/events/{$event->id}/vibechecks", $review);

    $response->assertStatus(201);
    assertDatabaseHas('vibechecks', [
        'user_id' => $user->id,
        'event_id' => $event->id,
        'sound_score' => 5,
        'safe_space_score' => 4
    ]);
});

test('cannot leave a vibecheck with an invalid rating', function () {
    $user = User::factory()->create(['name' => 'Reviewer']);
    $event = Event::factory()->create(['date' => now()->subDay()->toDateString()]);

    /** @var \App\Models\User $user */
    Passport::actingAs($user);

    $response = postJson("/api/v1/events/{$event->id}/vibechecks", [
        'sound_score' => 10, // not valid (max 5)
        'safe_space_score' => 5,
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
        'sound_score' => 4,
        'safe_space_score' => 4
    ]);

    $response->assertStatus(422);
});

test('a user cannot review the same event twice', function () {
    $user = User::factory()->create(['name' => 'Reviewer']);
    $event = Event::factory()->create(['date' => now()->subDay()->toDateString()]);

    /** @var \App\Models\User $user */
    Passport::actingAs($user);

    postJson("/api/v1/events/{$event->id}/vibechecks", [
        'sound_score' => 5, 
        'safe_space_score' => 5, 
        'comment' => 'First'
    ]);
    

    $response = postJson("/api/v1/events/{$event->id}/vibechecks", [
        'sound_score' => 1, 
        'safe_space_score' => 1, 
        'comment' => 'Second'
    ]);

    $response->assertStatus(422);
    assertDatabaseCount('vibechecks', 1);
});

test('vibecheck comment is optional but scores are mandatory', function () {
    $user = User::factory()->create(['name' => 'Reviewer']);
    $event = Event::factory()->create(['date' => now()->subDay()->toDateString()]);

    /** @var \App\Models\User $user */
    Passport::actingAs($user);

    
    $response = postJson("/api/v1/events/{$event->id}/vibechecks", [
        'sound_score' => 4,
        'safe_space_score' => 4
    ]);
    $response->assertStatus(201);

    
    $responseError = postJson("/api/v1/events/{$event->id}/vibechecks", [
        'comment' => 'Nice'
    ]);
    $responseError->assertStatus(422);
});

test('an organizer can view vibechecks for their own event', function () {
    $organizer = User::factory()->create();
    $event = Event::factory()->create(['user_id' => $organizer->id]);
    

    \App\Models\Vibecheck::create([
        'user_id' => User::factory()->create()->id,
        'event_id' => $event->id,
        'sound_score' => 5,
        'safe_space_score' => 5,
        'comment' => 'Epic night!'
    ]);

    /** @var \App\Models\User $organizer */
    Passport::actingAs($organizer);

    $response = getJson("/api/v1/events/{$event->id}/vibechecks");

    $response->assertStatus(200)
             ->assertJsonStructure(['event_title', 'average_sound', 'data']);
});

test('a user cannot view vibechecks for an event they do not own', function () {
    $organizer = User::factory()->create();
    $otherUser = User::factory()->create();
    $event = Event::factory()->create(['user_id' => $organizer->id]);

    /** @var \App\Models\User $otherUser */
    Passport::actingAs($otherUser);

    $response = getJson("/api/v1/events/{$event->id}/vibechecks");

    $response->assertStatus(403);
});


test('admin can delete any vibecheck', function () {
   
    $user = User::factory()->create();
    $event = Event::factory()->create(['user_id' => $user->id]);
    $vibecheck = Vibecheck::factory()->create([
        'user_id' => $user->id,
        'event_id' => $event->id,
        'comment' => 'This vibecheck will be erased'
    ]);

    $admin = User::factory()->create(['role' => UserRole::ADMIN]);
    /** @var \App\Models\User $admin */
    Passport::actingAs($admin);

    $response = deleteJson("/api/v1/vibechecks/{$vibecheck->id}");

    $response->assertStatus(204);
    assertDatabaseMissing('vibechecks', ['id' => $vibecheck->id]);
});

test('non-admin user cannot delete a vibecheck that is not theirs', function () {
    $owner = User::factory()->create();
    $vibecheck = Vibecheck::factory()->create(['user_id' => $owner->id]);

    $hacker = User::factory()->create(['role' => UserRole::CLUBBER]);

    /** @var \App\Models\User $hacker */
    Passport::actingAs($hacker);

    $response = deleteJson("/api/v1/vibechecks/{$vibecheck->id}");

    $response->assertStatus(403);
});