<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use function Pest\Laravel\postJson;

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
             ->assertJsonStructure(['access_token', 'token_type', 'user' => ['nickname', 'role']]);
});


test('a user cant login with incorrect credentials', function() {
    User::factory()->create([
        'email' => 'user@underpass.com',
        'password' => Hash::make('password_correcto'),
    ]);

    $response = postJson('/api/v1/login', [
        'email' => 'user@underpass.com',
        'password' => 'password_incorrecto',
    ]);

    $response->assertStatus(401)
             ->assertJson(['message' => 'Credenciales incorrectas']);
});


test('a user cannot login with a non-existent email', function () {
    $response = postJson('/api/v1/login', [
        'email' => 'ghost@underpass.com',
        'password' => 'any_password',
    ]);

    $response->assertStatus(401)
             ->assertJson(['message' => 'Credenciales incorrectas']);
});

test('login requires a valid email and password', function () {
    
    $response = postJson('/api/v1/login', [
        'email' => 'not-an-email',
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

    $response->assertStatus(200)
             ->assertJsonStructure(['access_token']);
});