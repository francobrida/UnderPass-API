<?php

use App\Models\User;
use App\Enums\UserRole;
use Laravel\Passport\Passport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use function Pest\Laravel\getJson;

uses(RefreshDatabase::class);

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

test('a clubber cannot view another clubber profile', function () {
    $clubber1 = User::factory()->create(['role' => UserRole::CLUBBER]);
    $clubber2 = User::factory()->create(['role' => UserRole::CLUBBER]);
    
    /** @var \App\Models\User $clubber1 */
    Passport::actingAs($clubber1);

    $response = getJson("/api/v1/users/{$clubber2->id}");

    $response->assertStatus(403)
             ->assertJson(['message' => 'No tienes permiso para ver este perfil privado']);
});

test('returns 404 when user does not exist', function () {
    $admin = User::factory()->create(['role' => UserRole::ADMIN]);
    
    /** @var \App\Models\User $admin */
    Passport::actingAs($admin);

    $response = getJson("/api/v1/users/999999");

    $response->assertStatus(404)
             ->assertJson(['message' => 'Usuario no encontrado']);
});

test('unauthenticated users are rejected', function () {
    $user = User::factory()->create();
    
    $response = getJson("/api/v1/users/{$user->id}");

    $response->assertStatus(401);
});

test('an organizer cannot view another organizers profile', function () {
    $organizer1 = User::factory()->create(['role' => UserRole::ORGANIZER]);
    $organizer2 = User::factory()->create(['role' => UserRole::ORGANIZER]);
    
    /** @var \App\Models\User $organizer1 */
    Passport::actingAs($organizer1);

    $response = getJson("/api/v1/users/{$organizer2->id}");

    $response->assertStatus(403);
});

