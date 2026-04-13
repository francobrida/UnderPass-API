<?php

use App\Models\Event;
use App\Models\User;
use App\Enums\UserRole;
use Laravel\Passport\Passport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use function Pest\Laravel\{assertDatabaseHas, assertDatabaseMissing, getJson, postJson,
putJson, deleteJson};

uses(RefreshDatabase::class);

test('admin can update any event', function () {
    $admin = User::factory()->create([
        'role' => UserRole::ADMIN
    ]);
    
    $anyUser = User::factory()->create();

    /**  @var \App\Models\User $admin */
    Passport::actingAs($admin);

    $event = Event::factory()->create([
        'user_id' => $anyUser->id,
        'title' => 'Old Title',
        'is_verified' => true 
    ]);

    $updateData = [
        'title' => 'New Title',
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
        'title' => 'New Title',
        'is_verified' => true
    ]);
    
});


test('returns 404 if event does not exist', function () {
    $admin = User::factory()->create(['role' => UserRole::ADMIN]);

    /**  @var \App\Models\User $admin */
    Passport::actingAs($admin);

    $response = putJson("/api/v1/events/999999", ['title' => 'New Title']);

    $response->assertStatus(404);
});


test('update fails with invalid data', function () {
    $user = User::factory()->create();
    $event = Event::factory()->create(['user_id' => $user->id]);

    /**  @var \App\Models\User $user */
    Passport::actingAs($user);

    $response = putJson("/api/v1/events/{$event->id}", [
        'title' => '', 
        'price' => 'not-a-number',
        'date' => now()->subDays(1)->toDateString(), 
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['title', 'price', 'date']);
});

test('user cannot verify their own event via update', function () {
    $user = User::factory()->create(['role' => UserRole::CLUBBER]);
    $event = Event::factory()->create(['user_id' => $user->id, 'is_verified' => false]);

    /**  @var \App\Models\User $user */
    Passport::actingAs($user);

    $response = putJson("/api/v1/events/{$event->id}", [
        'title' => 'Updated Title',
        'is_verified' => true, 
        'lineup' => 'Updated Lineup',
        'description' => 'Updated Desc',
        'location_name' => 'New Club',
        'neighborhood' => 'Gracia',
        'date' => now()->addDays(10)->toDateString(),
        'start_time' => '23:00',
        'end_time' => '06:00',
        'price' => 20,
        'is_18_plus' => true
    ]);

    $response->assertStatus(200);
    assertDatabaseHas('events', [
        'id' => $event->id,
        'is_verified' => false
    ]);
});
