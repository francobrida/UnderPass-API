<?php

use App\Models\User;
use App\Models\Stamp;
use App\Models\Event;
use Laravel\Passport\Passport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use function Pest\Laravel\getJson;

uses(RefreshDatabase::class);


test('it returns users ordered by most stamps first', function () {
    
    $userBronce = User::factory()->create(['name' => 'Bronce']);
    Stamp::factory()->create(['user_id' => $userBronce->id]);

    
    $userOro = User::factory()->create(['name' => 'Oro']);
    Stamp::factory()->count(3)->create([
        'user_id' => $userOro->id,
        'event_id' => fn() => Event::factory()->create()->id, 
        'scanned_at' => now()
    ]);

    /**  @var \App\Models\User $userBronce */
    Passport::actingAs($userBronce);

    $response = getJson('/api/v1/ranking');

    $response->assertStatus(200)
             ->assertJsonPath('data.0.name', 'Oro')     
             ->assertJsonPath('data.1.name', 'Bronce'); 
});


test('unauthenticated users cannot view the ranking', function () {

    $response = getJson('/api/v1/ranking');

    $response->assertStatus(401);
});


test('only returns the top 20 users even if there are more', function () {
    
    User::factory()->count(25)->create()->each(function ($user) {
        Stamp::factory()->create(['user_id' => $user->id]);
    });

    Passport::actingAs(User::first());

    $response = getJson('/api/v1/ranking');

    $response->assertStatus(200)
             ->assertJsonCount(20, 'data'); 
});

test('users with zero stamps are still included in the ranking', function () {
    $userWithStamps = User::factory()->create();
    Stamp::factory()->create(['user_id' => $userWithStamps->id]);
    
    $userEmpty = User::factory()->create(); 

    /**  @var \App\Models\User $userEmpty */
    Passport::actingAs($userEmpty);

    $response = getJson('/api/v1/ranking');

    $response->assertStatus(200)
             ->assertJsonFragment(['name' => $userEmpty->name, 'stamps_count' => 0]);
});

test('ranking returns expected json structure', function () {
    $user = User::factory()->create();

    /**  @var \App\Models\User $user */
    Passport::actingAs($user);

    $response = getJson('/api/v1/ranking');

    $response->assertJsonStructure([
        'message',
        'data' => [
            '*' => ['id', 'name', 'stamps_count']
        ]
    ]);
});