<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class); // refreshes the database for each test

test('a user can login with correct credentials', function () {
    
    $user = User::factory()->create([
        'email' => 'test@underpass.com',
        'password' => bcrypt('password123'),
    ]);

    $response = $this->postJson('/api/v1/login', [
        'email' => 'test@underpass.com',
        'password' => 'password123',
    ]);

    $response->assertStatus(200)->assertJsonStructure(['access_token']);
});