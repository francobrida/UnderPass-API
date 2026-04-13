<?php

use App\Models\Event;
use App\Models\User;
use App\Models\Vouch;
use Laravel\Passport\Passport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use function Pest\Laravel\{getJson};

uses(RefreshDatabase::class);

test('user can see list of vouches for a verified event', function () {
    $event = Event::factory()->create(['is_verified' => true]);
    $users = User::factory()->count(3)->create();
    
    foreach ($users as $user) {
        $event->vouches()->create(['user_id' => $user->id]);
    }

    $user = User::factory()->create();

    /**  @var \App\Models\User $user */
    Passport::actingAs($user);

    $response = getJson("/api/v1/events/{$event->id}/vouches");

    $response->assertStatus(200)
             ->assertJsonCount(3, 'data');
});

test('returns 404 if event for vouches does not exist', function () {

    $user = User::factory()->create();
    
    /**  @var \App\Models\User $user */
    Passport::actingAs($user);
    
    $response = getJson("/api/v1/events/9999/vouches");
    $response->assertStatus(404);
});