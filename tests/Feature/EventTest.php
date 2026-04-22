<?php

use App\Models\Event;
use App\Models\User;
use Laravel\Passport\Passport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use function Pest\Laravel\{assertDatabaseHas, assertDatabaseMissing, getJson, postJson,
putJson, deleteJson};

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


test('update fails if date is in the past', function () {
    $user = User::factory()->create();

    /**  @var \App\Models\User $user */
    Passport::actingAs($user);
    $event = Event::factory()->create(['user_id' => $user->id]);

    $response = putJson("/api/v1/events/{$event->id}", [
        'title' => 'New Title',
        'date' => '2020-01-01', // Pasado
        // ... enviar el resto de campos requeridos
    ]);

    $response->assertStatus(422)
             ->assertJsonValidationErrors(['date']);
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


test('it returns 404 if event does not exist', function () {
    $user = User::factory()->create();

    /**  @var \App\Models\User $user */
    Passport::actingAs($user);

    $response = getJson('/api/v1/events/999999');

    $response->assertStatus(404);
});

test('a user cannot see the detail of an unverified event if they are not the owner', function () {
    $owner = User::factory()->create();
    $stranger = User::factory()->create();
    $event = Event::factory()->create(['user_id' => $owner->id, 'is_verified' => false]);

    /**  @var \App\Models\User $stranger */
    Passport::actingAs($stranger);

    $response = getJson("/api/v1/events/{$event->id}");

    $response->assertStatus(403)
             ->assertJson(['message' => 'This event is pending verification and is not public yet.']);
});

test('a user can see the list of unverified events in the waiting room', function () {
    
    $user = User::factory()->create(['role' => \App\Enums\UserRole::CLUBBER]);

    /** @var \App\Models\User $user */
    Passport::actingAs($user);

    
    Event::factory()->create([
        'title' => 'Rave Pendiente A',
        'is_verified' => false,
        'created_at' => now()->subMinutes(10)
    ]);

    
    Event::factory()->create([
        'title' => 'Rave Pendiente B',
        'is_verified' => false,
        'created_at' => now()
    ]);

    Event::factory()->create([
        'title' => 'Evento Verificado',
        'is_verified' => true
    ]);

    $response = getJson('/api/v1/events?verified=false');

    
    $response->assertStatus(200)
             ->assertJsonCount(2, 'data') // 
             ->assertJsonPath('data.0.title', 'Rave Pendiente B') 
             ->assertJsonPath('data.1.title', 'Rave Pendiente A');
             
    
    $titles = collect($response->json('data'))->pluck('title');
    expect($titles)->not->toContain('Evento Verificado');

});

test('unauthenticated user cannot access the waiting room', function () {
    
    $response = getJson('/api/v1/events?verified=false');

    $response->assertStatus(401);
});

test('waiting room returns empty array when no pending events exist', function () {
    $user = User::factory()->create();

    /** @var \App\Models\User $user */
    Passport::actingAs($user);

    Event::factory()->create(['is_verified' => true]);

    $response = getJson('/api/v1/events?verified=false');

    $response->assertStatus(200)
             ->assertJsonCount(0, 'data');
});


test('it returns 404 if the user does not exist when fetching events', function () {
    $user = User::factory()->create();

    /** @var \App\Models\User $user */
    Passport::actingAs($user);

    $response = getJson('/api/v1/users/99999/events');

    $response->assertStatus(404);
});

test('a user can see their own private event list', function () {

    $user = User::factory()->create(['name' => 'Organizador VIP']);
    
    /** @var \App\Models\User $user */
    Passport::actingAs($user);

    Event::factory()->count(2)->create(['user_id' => $user->id]);

    $response = getJson("/api/v1/users/{$user->id}/events");

    $response->assertStatus(200)
             ->assertJsonCount(2, 'data')
             ->assertJsonPath('data.0.organizer', 'Organizador VIP');
});

test('a user cannot see the events of another user (Access Denied)', function () {

    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    
    /** @var \App\Models\User $user1 */
    Passport::actingAs($user1);

    
    $response = getJson("/api/v1/users/{$user2->id}/events");

    $response->assertStatus(403); 
});

test('unauthenticated user gets 401 when accessing user events', function () {
    $user = User::factory()->create();
    
    $response = getJson("/api/v1/users/{$user->id}/events");

    $response->assertStatus(401);
});


test('returns empty data array if the owner has no events yet', function () {
    $user = User::factory()->create();

    /** @var \App\Models\User $user */
    Passport::actingAs($user);

    $response = getJson("/api/v1/users/{$user->id}/events");

    $response->assertStatus(200)
             ->assertJsonCount(0, 'data')
             ->assertExactJson(['data' => []]);
});

test('it returns 404 if the user_id in the URL does not exist at all', function () {
    $user = User::factory()->create();

    /** @var \App\Models\User $user */
    Passport::actingAs($user);

    $response = getJson("/api/v1/users/999/events");

    $response->assertStatus(404);
});

test('clubber cant create more than one unverified event', function() {
    $user = User::factory()->create(['role' => \App\Enums\UserRole::CLUBBER]);
    
    Event::factory()->create([
        'user_id' => $user->id, 
        'is_verified' => false,
        'date' => now()->addDays(1)->toDateString() 
    ]);

    /** @var \App\Models\User $user */
    Passport::actingAs($user);

    $response = postJson('/api/v1/events', [
        'title' => 'Second Event',
        'date' => now()->addDays(2)->toDateString(),
        'price' => 10,
        'lineup' => 'Lolo, pepe, etc',
        'description' => 'Updated Desc',
        'location_name' => 'New Club',
        'neighborhood' => 'Gracia',
        'start_time' => '23:00',
        'end_time' => '06:00',
        'is_18_plus' => true
    ]);

    $response->assertStatus(422)
             ->assertJsonValidationErrors(['limit']);
});

