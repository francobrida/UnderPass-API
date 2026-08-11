<?php

use App\Models\User;
use App\Support\AuthCookie;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;

uses(RefreshDatabase::class);

beforeEach(function () {
    Artisan::call('passport:keys');
    Artisan::call('passport:client', ['--personal' => true, '--no-interaction' => true]);
});

test('login returns 200, no access_token in body, and sets the access_token cookie', function () {
    User::factory()->create([
        'email' => 'test@underpass.com',
        'password' => Hash::make('password123'),
    ]);

    $response = postJson('/api/v1/login', [
        'email' => 'test@underpass.com',
        'password' => 'password123',
    ]);

    $response->assertStatus(200)
        ->assertJsonStructure(['user'])
        ->assertJsonMissingPath('access_token');

    $cookie = $response->getCookie(AuthCookie::NAME, false);

    expect($cookie)->not->toBeNull();
    expect($cookie->getValue())->toBeString()->not->toBeEmpty();
});

test('login cookie authenticates a protected route with no Authorization header', function () {
    User::factory()->create([
        'email' => 'test@underpass.com',
        'password' => Hash::make('password123'),
    ]);

    $login = postJson('/api/v1/login', [
        'email' => 'test@underpass.com',
        'password' => 'password123',
    ]);

    $cookie = $login->getCookie(AuthCookie::NAME, false);

    $response = $this->withCredentials()
        ->withUnencryptedCookie(AuthCookie::NAME, $cookie->getValue())
        ->getJson('/api/v1/genres');

    $response->assertStatus(200);
});

test('the access_token cookie carries the hardened attributes in the testing environment', function () {
    User::factory()->create([
        'email' => 'test@underpass.com',
        'password' => Hash::make('password123'),
    ]);

    $response = postJson('/api/v1/login', [
        'email' => 'test@underpass.com',
        'password' => 'password123',
    ]);

    $cookie = $response->getCookie(AuthCookie::NAME, false);

    expect($cookie->isHttpOnly())->toBeTrue();
    expect($cookie->isSecure())->toBeTrue();
    expect($cookie->getSameSite())->toBe('none');
    expect($cookie->getPath())->toBe('/');
    expect($cookie->getDomain())->toBeNull();
});

test('a garbage access_token cookie on a protected route returns 401', function () {
    $response = $this->withCredentials()
        ->withUnencryptedCookie(AuthCookie::NAME, 'garbage-not-a-real-token')
        ->getJson('/api/v1/genres');

    $response->assertStatus(401);
});

test('no cookie and no Authorization header on a protected route returns 401', function () {
    $response = getJson('/api/v1/genres');

    $response->assertStatus(401);
});
