<?php

use App\Models\Event;
use App\Models\User;
use Laravel\Passport\Passport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use function Pest\Laravel\getJson;


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
