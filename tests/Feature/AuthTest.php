<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use function Pest\Laravel\postJson;

uses(RefreshDatabase::class); // refreshes the database for each test

test('a user can login with correct credentials', function () {

    // 1. GENERAR LAS LLAVES (Indispensable para evitar el error 'Invalid key')
    Artisan::call('passport:keys');

    // 2. Crear el cliente personal
    Artisan::call('passport:client', ['--personal' => true, '--no-interaction' => true]);

    $user = User::factory()->create([
        'email' => 'test@underpass.com',
        'password' => bcrypt('password123'),
    ]);

    $response = postJson('/api/v1/login', [
        'email' => 'test@underpass.com',
        'password' => 'password123',
    ]);

    $response->assertStatus(200)->assertJsonStructure(['access_token']);
});