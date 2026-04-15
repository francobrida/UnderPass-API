<?php

use App\Models\Event;
use App\Models\User;
use App\Models\Stamp;
use App\Enums\UserRole;
use Laravel\Passport\Passport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use function Pest\Laravel\postJson;
use function Pest\Laravel\getJson;

uses(RefreshDatabase::class);

test('a user can collect a stamp and see event details in the collection', function () {
    $user = User::factory()->create();
    
    $event = Event::factory()->create([
        'title' => 'Underpass Techno Night',
        'date' => now()->toDateString(),
        'stamp_token' => 'valid-token-123'
    ]);

    /** @var \App\Models\User $user */
    Passport::actingAs($user);

    $response = postJson('/api/v1/stamps', ['token' => $event->stamp_token]);
    
    $response->assertStatus(201);

    $listResponse = getJson('/api/v1/stamps');
    $listResponse->assertStatus(200)
                 ->assertJsonPath('data.0.event.title', 'Underpass Techno Night');
});

test('a user cannot collect a stamp for an event that has not started yet', function () {
    $user = User::factory()->create();
    $futureEvent = Event::factory()->create([
        'date' => now()->addDays(5)->toDateString(),
        'stamp_token' => 'future-token'
    ]);

    /** @var \App\Models\User $user */
    Passport::actingAs($user);

    $response = postJson('/api/v1/stamps', ['token' => $futureEvent->stamp_token]);

    $response->assertStatus(422);
    
    $response->assertJsonFragment(['token' => ['QR code is not active or has expired.']]);
});

test('a user cannot collect a stamp if the event ended more than 24h ago', function () {
    $user = User::factory()->create();
    $oldEvent = Event::factory()->create([
        'date' => now()->subDays(10)->toDateString(),
        'stamp_token' => 'expired-token'
    ]);

    /** @var \App\Models\User $user */
    Passport::actingAs($user);

    $response = postJson('/api/v1/stamps', ['token' => $oldEvent->stamp_token]);

    $response->assertStatus(422);
    $response->assertJsonFragment(['token' => ['QR code is not active or has expired.']]);
});

test('admin can view all the stamps of a user', function () {
    $user = User::factory()->create(['name' => 'Stamp Collector']);
    $event = Event::factory()->create(['title' => 'Techno Party']);
    
    Stamp::create([
        'user_id' => $user->id,
        'event_id' => $event->id,
        'scanned_at' => now(), 
    ]);

    $admin = User::factory()->create(['name' => 'Admin User', 'role' => UserRole::ADMIN]);
    /** @var \App\Models\User $admin */
    Passport::actingAs($admin);

    $response = getJson("/api/v1/users/{$user->id}/stamps");

    $response->assertStatus(200)
             ->assertJsonPath('data.0.event.title', 'Techno Party');
});

test('a normal user cannot view the stamps of another user', function () {
    $user1 = User::factory()->create(['name' => 'User One']);
    $user2 = User::factory()->create(['name' => 'User Two']);
    $event = Event::factory()->create(['title' => 'Techno Party']);
    
    Stamp::create([
        'user_id' => $user1->id,
        'event_id' => $event->id,
        'scanned_at' => now(), 
    ]);

    /** @var \App\Models\User $user2 */
    Passport::actingAs($user2);

    $response = getJson("/api/v1/users/{$user1->id}/stamps");

    $response->assertStatus(403);
});

test('admin can delete any user stamp', function () {
    $user = User::factory()->create(['name' => 'Stamp Collector']);
    $event = Event::factory()->create(['title' => 'Techno Party']);
    
    $stamp = Stamp::create([
        'user_id' => $user->id,
        'event_id' => $event->id,
        'scanned_at' => now(), 
    ]);

    $admin = User::factory()->create(['name' => 'Admin User', 'role' => UserRole::ADMIN]);
    /** @var \App\Models\User $admin */
    Passport::actingAs($admin);

    $response = $this->deleteJson("/api/v1/stamps/{$stamp->id}");

    $response->assertStatus(204);
});