<?php
use App\Models\Event;
use App\Models\User;
use App\Models\Stamp;
use Laravel\Passport\Passport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use function Pest\Laravel\postJson;
use function Pest\Laravel\getJson;
use function Pest\Laravel\assertDatabaseHas;

uses(RefreshDatabase::class);

test('a user can collect a stamp and see event details in the collection', function () {
    $user = User::factory()->create();
    $event = Event::factory()->create(['title' => 'Underpass Techno Night']);

    /** @var \App\Models\User $user */
    Passport::actingAs($user);

    $response = postJson('/api/v1/stamps', ['event_id' => $event->id]);
    $response->assertStatus(201);

    // Verificamos que al listar, el stamp incluya info del evento
    $listResponse = getJson('/api/v1/stamps');
    $listResponse->assertStatus(200)
                 ->assertJsonPath('data.0.event.title', 'Underpass Techno Night');
});

test('cannot collect a stamp for a non-existent event', function () {
    $user = User::factory()->create();

    /** @var \App\Models\User $user */
    Passport::actingAs($user);

    $response = postJson('/api/v1/stamps', ['event_id' => 9999]); // ID que no existe

    $response->assertStatus(404);
});

test('unauthenticated user cannot collect or view stamps', function () {
    // No usamos Passport::actingAs
    postJson('/api/v1/stamps', ['event_id' => 1])->assertStatus(401);
    getJson('/api/v1/stamps')->assertStatus(401);
});

test('fails if event_id is missing in the request', function () {
    $user = User::factory()->create();

    /** @var \App\Models\User $user */
    Passport::actingAs($user);

    $response = postJson('/api/v1/stamps', []); // Payload vacío

    $response->assertStatus(422)
             ->assertJsonValidationErrors(['event_id']);
});

test('a user cannot collect a stamp for an event that has not started yet', function () {
    $user = User::factory()->create();
    
    $futureEvent = Event::factory()->create(['date' => now()->addDay()->toDateString()]);

    /** @var \App\Models\User $user */
    Passport::actingAs($user);

    $response = postJson('/api/v1/stamps', ['event_id' => $futureEvent->id]);

    
    $response->assertStatus(422);
    $response->assertJsonPath('message', 'This event has not started yet.');
});

test('a user cannot collect a stamp if the event ended more than 24h ago', function () {
    $user = User::factory()->create();
    
    $oldEvent = Event::factory()->create(['date' => now()->subDays(7)->toDateString()]);

    /** @var \App\Models\User $user */
    Passport::actingAs($user);

    $response = postJson('/api/v1/stamps', ['event_id' => $oldEvent->id]);


    $response->assertStatus(422);
    $response->assertJsonPath('message', 'This QR code has expired.');
});

test('database integrity: unique constraint prevents double stamps at DB level', function () {
    $user = User::factory()->create();
    $event = Event::factory()->create();

    Stamp::create(['user_id' => $user->id, 'event_id' => $event->id]);
    
    $this->expectException(\Illuminate\Database\QueryException::class);
    
    Stamp::create(['user_id' => $user->id, 'event_id' => $event->id]);
});