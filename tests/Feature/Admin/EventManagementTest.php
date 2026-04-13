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
    $admin = User::factory()->create()->assignRole(UserRole::ADMIN);
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