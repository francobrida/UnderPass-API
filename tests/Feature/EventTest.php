<?php

use App\Models\Event;
use App\Models\User;
use Laravel\Passport\Passport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;
use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;
use function Pest\Laravel\putJson;
use function Pest\Laravel\deleteJson;


uses(RefreshDatabase::class);

test('a user can see only verified events', function () {
    $user = User::factory()->create();

    /**  @var \App\Models\User $user */
    Passport::actingAs($user);

    
    Event::factory()->create(['is_verified' => true, 'title' => 'Fiesta Verificada']);
    Event::factory()->create(['is_verified' => false, 'title' => 'Evento Pendiente']);

    $response = getJson('/api/v1/events');

    $response->assertStatus(200)
             ->assertJsonCount(1, 'data')
             ->assertJsonPath('data.0.title', 'Fiesta Verificada');
});

test('unauthenticated users are blocked from seeing events', function () {
    $response = getJson('/api/v1/events');
    $response->assertStatus(401);
});

test('it returns empty array when no events exist', function () {
    $user = User::factory()->create();

    /**  @var \App\Models\User $user */
    Passport::actingAs($user);

    $response = getJson('/api/v1/events');

    $response->assertStatus(200);
    $response->assertExactJson(['data' => []]);
});

test('a user can create a new event', function () {
    $user = User::factory()->create();

    /**  @var \App\Models\User $user */
    Passport::actingAs($user);

    $eventData = [
        'title' => 'Underground Techno',
        'lineup' => 'DJ Snake, Amelie Lens',
        'description' => 'Best party in the city',
        'location_name' => 'Secret Club',
        'neighborhood' => 'Poblenou',
        'date' => now()->addDays(1)->toDateString(),
        'start_time' => '23:00',
        'end_time' => '06:00',
        'price' => 15.50,
        'is_18_plus' => true
    ];

    $response = postJson('/api/v1/events', $eventData);

    $response->assertStatus(201)
             ->assertJsonPath('data.title', 'Underground Techno')
             ->assertJsonPath('data.is_verified', false);

    assertDatabaseHas('events', [
        'title' => 'Underground Techno',
        'user_id' => $user->id,
        'is_verified' => false
    ]);

});

test('event creation requires title and date', function () {
    $user = User::factory()->create();

    /**  @var \App\Models\User $user */
    Passport::actingAs($user);

    $response = postJson('/api/v1/events', []); 

    $response->assertStatus(422)
             ->assertJsonValidationErrors(['title', 'date']);
});

test('it fails if the event date is in the past', function () {
    $user = User::factory()->create();

    /**  @var \App\Models\User $user */
    Passport::actingAs($user);

    $eventData = [
        'title' => 'Retro Party',
        'lineup' => 'Old DJs',
        'description' => 'A party that already happened',
        'location_name' => 'Ghost Club',
        'neighborhood' => 'Center',
        'date' => '2020-01-01', 
        'start_time' => '22:00',
        'end_time' => '04:00',
        'price' => 10,
        'is_18_plus' => true
    ];

    $response = postJson('/api/v1/events', $eventData);

    $response->assertStatus(422)
             ->assertJsonValidationErrors(['date']);
});

test('a user cannot force an event to be verified on creation', function () {
    $user = User::factory()->create();

    /**  @var \App\Models\User $user */
    Passport::actingAs($user);

    $eventData = [
        'title' => 'Hack the system',
        'lineup' => 'Anonymous',
        'description' => 'Trying to set is_verified to true',
        'location_name' => 'Hidden Place',
        'neighborhood' => 'Dark Web',
        'date' => now()->addDays(7)->toDateString(),
        'start_time' => '20:00',
        'end_time' => '02:00',
        'price' => 0,
        'is_18_plus' => true,
        'is_verified' => true 
    ];

    $response = postJson('/api/v1/events', $eventData);

    $response->assertStatus(201);
    
    assertDatabaseHas('events', [
        'title' => 'Hack the system',
        'is_verified' => false
    ]);

});

test('a user can update their own event', function () {
    $user = User::factory()->create();

    /**  @var \App\Models\User $user */
    Passport::actingAs($user);

    $event = Event::factory()->create([
        'user_id' => $user->id,
        'title' => 'Old Title',
        'is_verified' => true 
    ]);

    $updateData = [
        'title' => 'New Awesome Title',
        'lineup' => 'Updated Lineup',
        'description' => 'Updated Desc',
        'location_name' => 'New Club',
        'neighborhood' => 'Gracia',
        'date' => now()->addDays(10)->toDateString(),
        'start_time' => '23:00',
        'end_time' => '06:00',
        'price' => 20,
        'is_18_plus' => true
    ];

    $response = putJson("/api/v1/events/{$event->id}", $updateData);

    $response->assertStatus(200);
    
    assertDatabaseHas('events', [
        'id' => $event->id,
        'title' => 'New Awesome Title',
        'is_verified' => false
    ]);
});

test('a user cannot update someone else event', function () {
    $owner = User::factory()->create();
    $hacker = User::factory()->create();
    
    $event = Event::factory()->create(['user_id' => $owner->id, 'title' => 'Original Partyy']);

    /**  @var \App\Models\User $hacker */
    Passport::actingAs($hacker);

    $response = putJson("/api/v1/events/{$event->id}", [
        'title' => 'I hacked you hahaaa'
    ]);

    
    $response->assertStatus(403);
    
    assertDatabaseHas('events', [
        'id' => $event->id,
        'title' => 'Original Partyy'
    ]);
});

test('a user can delete their own event', function () {

    $owner = User::factory()->create();

    $event = Event::factory()->create(['user_id' => $owner->id]);

    /** @var \App\Models\User $owner */
    Passport::actingAs($owner);

    $response = deleteJson("/api/v1/events/{$event->id}");

    $response->assertStatus(200)->assertJson(['message' => 'Event successfully deleted']);

    assertDatabaseMissing('events', ['id' => $event->id]);

});

test('a user cannot delete someone else event', function () {
    $owner = User::factory()->create();
    $hacker = User::factory()->create();
    
    $event = Event::factory()->create(['user_id' => $owner->id, 'title' => 'Original Partyy']);

    /**  @var \App\Models\User $hacker */
    Passport::actingAs($hacker);

    $response = deleteJson("/api/v1/events/{$event->id}");
    
    $response->assertStatus(403);
    
    assertDatabaseHas('events', ['id' => $event->id]);
});

test('a user can view an event', function () {
    $user = User::factory()->create();

    /**  @var \App\Models\User $user */
    Passport::actingAs($user);

    $event = Event::factory()->create(['is_verified' => true, 'title' => 'Fiesta Verificada']);

    $response = getJson("/api/v1/events/{$event->id}");

    $response->assertStatus(200)
             ->assertJsonPath('data.title', 'Fiesta Verificada');
});

