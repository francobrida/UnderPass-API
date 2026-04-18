<?php

use App\Models\Event;
use App\Models\User;
use App\Models\Vibecheck;
use App\Models\Stamp;
use App\Enums\UserRole;
use Laravel\Passport\Passport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use function Pest\Laravel\{postJson, deleteJson, getJson, assertDatabaseHas, 
assertDatabaseCount, assertDatabaseMissing};

uses(RefreshDatabase::class);

function createStampForUser(User $user, Event $event) {
    Stamp::create([
        'user_id' => $user->id,
        'event_id' => $event->id,
        'scanned_at' => now()->subDay(),
    ]);
}

test('a user can leave a vibecheck on a finished event', function () {
    $user = User::factory()->create();

    /** @var \App\Models\User $user */
    $this->actingAs($user, 'api');

    $event = Event::factory()->create([
        'date' => now()->subDay(),
        'start_time' => '20:00',
        'end_time' => '02:00',
    ]);

    \App\Models\Stamp::factory()->create([
        'user_id' => $user->id,
        'event_id' => $event->id,
    ]);

    $this->travelTo(now()->addDay());

    $review = [
        'sound_score' => 5,
        'safe_space_score' => 5,
    ];

    $response = $this->postJson("/api/v1/events/{$event->id}/vibechecks", $review);

    $response->assertStatus(201);
    
});

test('cannot leave a vibecheck without a stamp', function () {
    $user = User::factory()->create();
    $event = Event::factory()->create(['date' => now()->subDays(2)]); 

    /** @var \App\Models\User $user */
    $this->actingAs($user, 'api');
    $this->travelTo(now()); 

    $response = $this->postJson("/api/v1/events/{$event->id}/vibechecks", [
        'sound_score' => 5,
        'safe_space_score' => 5
    ]);

    $response->assertStatus(422); 
    $response->assertJsonValidationErrors(['event']);
});

test('cannot leave a vibecheck with an invalid rating', function () {
    $user = User::factory()->create();
    $event = Event::factory()->create(['date' => now()->subDay()->toDateString()]);
    createStampForUser($user, $event);

    /** @var \App\Models\User $user */
    Passport::actingAs($user);

    $response = postJson("/api/v1/events/{$event->id}/vibechecks", [
        'sound_score' => 10, // Max is 5
        'safe_space_score' => 5,
    ]);

    $response->assertStatus(422);
});

test('cannot leave a vibecheck for an event that has not happened yet', function () {
    $user = User::factory()->create();
    $futureEvent = Event::factory()->create([
        'date' => now()->addDay()->toDateString() 
    ]);
    createStampForUser($user, $futureEvent);

    /** @var \App\Models\User $user */
    Passport::actingAs($user);

    $response = postJson("/api/v1/events/{$futureEvent->id}/vibechecks", [
        'sound_score' => 4,
        'safe_space_score' => 4
    ]);

    $response->assertStatus(403);
});

test('a user cannot review the same event twice', function () {
    $user = User::factory()->create();
    
    /** @var \App\Models\User $user */
    $this->actingAs($user, 'api');

    $event = Event::factory()->create(['date' => now()->subDays(2)]);

    createStampForUser($user, $event);

    $this->travelTo(now());

    $this->postJson("/api/v1/events/{$event->id}/vibechecks", [
        'sound_score' => 5, 'safe_space_score' => 5
    ])->assertStatus(201);

    $response = $this->postJson("/api/v1/events/{$event->id}/vibechecks", [
        'sound_score' => 1, 'safe_space_score' => 1
    ]);

    $response->assertStatus(422); 

    $response->assertJsonValidationErrors(['event']); 
});

test('an organizer can view vibechecks for their own event', function () {
    $organizer = User::factory()->create();
    $event = Event::factory()->create(['user_id' => $organizer->id]);
    

    Vibecheck::create([
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

test('admin can delete any vibecheck', function () {
    $vibecheck = Vibecheck::factory()->create();
    $admin = User::factory()->create(['role' => UserRole::ADMIN]);

    /** @var \App\Models\User $admin */
    Passport::actingAs($admin);

    $response = deleteJson("/api/v1/vibechecks/{$vibecheck->id}");

    $response->assertStatus(204);
    assertDatabaseMissing('vibechecks', ['id' => $vibecheck->id]);
});