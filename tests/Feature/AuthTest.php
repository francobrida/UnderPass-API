<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use function Pest\Laravel\postJson;
use function Pest\Laravel\assertDatabaseHas;
use Laravel\Passport\Passport;

uses(RefreshDatabase::class);

beforeEach(function () {
  
    Artisan::call('passport:keys');
    Artisan::call('passport:client', ['--personal' => true, '--no-interaction' => true]);
});


test('a user can login with correct credentials', function () {
    $user = User::factory()->create([
        'email' => 'test@underpass.com',
        'password' => Hash::make('password123'),
    ]);

    $response = postJson('/api/v1/login', [
        'email' => 'test@underpass.com',
        'password' => 'password123',
    ]);

    $response->assertStatus(200)
             ->assertJsonStructure(['message', 'user' => ['name', 'role']]);
    $response->assertCookie('access_token');
});


test('a user cant login with incorrect credentials', function() {
    User::factory()->create([
        'email' => 'user@underpass.com',
        'password' => Hash::make('correct_password'),
    ]);

    $response = postJson('/api/v1/login', [
        'email' => 'user@underpass.com',
        'password' => 'wrong_password',
    ]);

    $response->assertStatus(401)
             ->assertJson(['message' => 'Invalid credentials']);
});


test('a user cannot login with a non-existent email', function () {
    $response = postJson('/api/v1/login', [
        'email' => 'ghost@underpass.com',
        'password' => 'any_password',
    ]);

    $response->assertStatus(401)
             ->assertJson(['message' => 'Invalid credentials']);
});

test('login requires a valid email and password', function () {
    
    $response = postJson('/api/v1/login', [
        'email' => 'not-email',
        'password' => '',
    ]);

    $response->assertStatus(422)
             ->assertJsonValidationErrors(['email', 'password']);
});

test('login is case-insensitive for the email address', function () {
    User::factory()->create([
        'email' => 'test@underpass.com',
        'password' => Hash::make('password123'),
    ]);

    $response = postJson('/api/v1/login', [
        'email' => 'TEST@UNDERPASS.COM',
        'password' => 'password123',
    ]);

    $response->assertStatus(200);
    $response->assertCookie('access_token');
});

// Registration

test('a user can register successfully', function () {
    $response = postJson('/api/v1/register', [
        'name'              => 'NewClubber',
        'email'                 => 'new@underpass.com',
        'password'              => 'password123',
        'password_confirmation' => 'password123',
        'role'                  => 'clubber'
    ]);

    $response->assertStatus(201)
             ->assertJsonStructure(['user' => ['name', 'email', 'role']]);
    $response->assertCookie('access_token');


    assertDatabaseHas('users', [
        'email'    => 'new@underpass.com',
        'name' => 'NewClubber'
    ]);

});

test('a user cannot register with an existing email', function () {
    
    User::factory()->create(['email' => 'existing@test.com']);

    $response = postJson('/api/v1/register', [
        'name'              => 'Other',
        'email'                 => 'existing@test.com',
        'password'              => 'password123',
        'password_confirmation' => 'password123',
    ]);

    $response->assertStatus(422)
             ->assertJsonValidationErrors(['email']);
});

test('registration requires name, email and password', function () {
    $response = postJson('/api/v1/register', []);

    $response->assertStatus(422)
             ->assertJsonValidationErrors(['name', 'email', 'password']);
});

test('an authenticated user can logout', function () {

    /** @var \App\Models\User $user */
    $user = User::factory()->create();

    Passport::actingAs($user);

    $response = postJson('/api/v1/logout');

    $response->assertStatus(200)
             ->assertJson(['message' => 'Session successfully logged out']);
});

test('a not logued in user cannot logout', function () {
    
    $response = postJson('/api/v1/logout');

    $response->assertStatus(401);
});

