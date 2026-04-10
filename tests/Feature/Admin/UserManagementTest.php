<?php

use App\Models\User;
use App\Enums\UserRole;
use Laravel\Passport\Passport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use function Pest\Laravel\{getJson, postJson};

uses(RefreshDatabase::class);

test('an admin can get all users list', function () {
    
    $admin = User::factory()->create(['role' => UserRole::ADMIN]);
    User::factory()->count(5)->create(['role' => UserRole::CLUBBER]);

    /**  @var \App\Models\User $admin */
    Passport::actingAs($admin);

    $response = getJson('/api/v1/users');

    $response->assertStatus(200)
             ->assertJsonCount(6, 'data') 
             ->assertJsonStructure([
                 'data' => [
                     '*' => ['id', 'name', 'email', 'role']
                 ]
             ]);
});


test('a non-admin user cannot access users list', function () {
    $organizer = User::factory()->create(['role' => UserRole::ORGANIZER]);

    /**  @var \App\Models\User $organizer */
    Passport::actingAs($organizer);

    $response = getJson('/api/v1/users');

    $response->assertStatus(403);
});


test('an admin can create a new user', function () {
    $admin = User::factory()->create(['role' => UserRole::ADMIN]);

    /**  @var \App\Models\User $admin */
    Passport::actingAs($admin);

    $userData = [
        'name' => 'New User',
        'email' => 'newuser@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'role' => UserRole::CLUBBER->value,
    ];

    $response = postJson('/api/v1/users', $userData);

    $response->assertStatus(201)
             ->assertJsonPath('data.name', 'New User');

    $this->assertDatabaseHas('users', ['email' => 'newuser@example.com']);
});