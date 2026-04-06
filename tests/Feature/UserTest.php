<?php

use App\Models\User;
use App\Enums\UserRole;
use Laravel\Passport\Passport;
use function Pest\Laravel\getJson;

test('a clubber can view their own profile', function () {
    $clubber = User::factory()->create([
        'name' => 'Mike Raver', 
        'role' => UserRole::CLUBBER
    ]);

    /** @var \App\Models\User $clubber */
    Passport::actingAs($clubber);

    $response = getJson("/api/v1/users/{$clubber->id}");

    $response->assertStatus(200)
             ->assertJsonPath('data.name', 'Mike Raver')
             ->assertJsonPath('data.role', 'clubber');
});

test('an admin can view any user profile (clubber or organizer)', function () {
    $admin = User::factory()->create(['role' => UserRole::ADMIN]);
    $user = User::factory()->create(['name' => 'Any User']);
    
    /** @var \App\Models\User $admin */
    Passport::actingAs($admin);

    $response = getJson("/api/v1/users/{$user->id}");

    $response->assertStatus(200)
             ->assertJsonPath('data.name', 'Any User');
});
