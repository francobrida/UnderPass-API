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

test('unauthenticated users cannot access admin endpoints', function () {

    $response = getJson('/api/v1/users');

    $response->assertStatus(401); 
});

test('it fails if an invalid role is provided', function () {
    
    $admin = User::factory()->create(['role' => UserRole::ADMIN]);

    /**  @var \App\Models\User $admin */
    Passport::actingAs($admin);

    $userData = [
        'name' => 'Hacker',
        'email' => 'hacker@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'role' => 'SUPER_GOD_MODE',
    ];

    $response = postJson('/api/v1/users', $userData);

    $response->assertStatus(422);
});

test('an admin can delete a user', function () {
    $admin = User::factory()->create(['role' => UserRole::ADMIN]);
    $userToDelete = User::factory()->create(['role' => UserRole::CLUBBER]);

    /**  @var \App\Models\User $admin */
    Passport::actingAs($admin);

    $response = $this->deleteJson("/api/v1/users/{$userToDelete->id}");

    $response->assertStatus(200)->assertJson(['message' => 'User deleted successfully']);

    $this->assertDatabaseMissing('users', ['id' => $userToDelete->id]);
});

test('a non-admin user cannot delete another user', function () {
    $user = User::factory()->create(['role' => UserRole::CLUBBER]);
    $victim = User::factory()->create(['role' => UserRole::CLUBBER]);

    /**  @var \App\Models\User $user */
    Passport::actingAs($user);

    $response = $this->deleteJson("/api/v1/users/{$victim->id}");

    $response->assertStatus(403);
    $this->assertDatabaseHas('users', ['id' => $victim->id]);
});

test('an admin can update a user role and info', function () {
    $admin = User::factory()->create(['role' => UserRole::ADMIN]);
    $user = User::factory()->create(['role' => UserRole::CLUBBER]);

    /**  @var \App\Models\User $admin */
    Passport::actingAs($admin);

    $response = $this->putJson("/api/v1/users/{$user->id}", [
        'name' => 'Updated Name',
        'email' => 'updated@example.com',
        'role' => UserRole::ORGANIZER->value,
    ]);

    $response->assertStatus(200)
             ->assertJsonPath('data.role', UserRole::ORGANIZER->value);

    $this->assertDatabaseHas('users', [
        'id' => $user->id,
        'role' => UserRole::ORGANIZER->value,
        'name' => 'Updated Name'
    ]);
});

test('a non-admin cannot update a user', function () {
    $user = User::factory()->create(['role' => UserRole::CLUBBER]);
    $anotherUser = User::factory()->create();

    /**  @var \App\Models\User $user */
    Passport::actingAs($user);

    $response = $this->putJson("/api/v1/users/{$anotherUser->id}", [
        'name' => 'Hacker'
    ]);

    $response->assertStatus(403);
});

