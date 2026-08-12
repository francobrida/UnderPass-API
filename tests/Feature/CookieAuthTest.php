<?php

use App\Models\User;
use App\Support\AuthCookie;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
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

    $cookie = $response->getCookie(AuthCookie::cookieName(), false);

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

    $cookie = $login->getCookie(AuthCookie::cookieName(), false);

    $response = $this->withCredentials()
        ->withUnencryptedCookie(AuthCookie::cookieName(), $cookie->getValue())
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

    $cookie = $response->getCookie(AuthCookie::cookieName(), false);

    expect($cookie->isHttpOnly())->toBeTrue();
    expect($cookie->isSecure())->toBeTrue();
    expect($cookie->getSameSite())->toBe('none');
    expect($cookie->getPath())->toBe('/');
    expect($cookie->getDomain())->toBeNull();
    // Pest runs under APP_ENV=testing (non-local), so the cookie should
    // carry the browser-enforced __Host- prefix here.
    expect($cookie->getName())->toBe(AuthCookie::NAME_HOST_PREFIXED);
});

test('a garbage access_token cookie on a protected route returns 401', function () {
    $response = $this->withCredentials()
        ->withUnencryptedCookie(AuthCookie::cookieName(), 'garbage-not-a-real-token')
        ->getJson('/api/v1/genres');

    $response->assertStatus(401);
});

test('no cookie and no Authorization header on a protected route returns 401', function () {
    $response = getJson('/api/v1/genres');

    $response->assertStatus(401);
});

test('register returns 201, no access_token in body, and sets the access_token cookie', function () {
    $response = postJson('/api/v1/register', [
        'name' => 'NewClubber',
        'email' => 'new@underpass.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'role' => 'clubber',
    ]);

    $response->assertStatus(201)
        ->assertJsonStructure(['user'])
        ->assertJsonMissingPath('access_token');

    $cookie = $response->getCookie(AuthCookie::cookieName(), false);

    expect($cookie)->not->toBeNull();
    expect($cookie->getValue())->toBeString()->not->toBeEmpty();
});

test('logout revokes the token and clears the cookie so the pre-logout cookie is dead afterwards', function () {
    User::factory()->create([
        'email' => 'test@underpass.com',
        'password' => Hash::make('password123'),
    ]);

    $login = postJson('/api/v1/login', [
        'email' => 'test@underpass.com',
        'password' => 'password123',
    ]);

    $preLogoutCookie = $login->getCookie(AuthCookie::cookieName(), false)->getValue();

    $logout = $this->withCredentials()
        ->withHeader('Origin', 'https://underpass.up.railway.app')
        ->withUnencryptedCookie(AuthCookie::cookieName(), $preLogoutCookie)
        ->postJson('/api/v1/logout');

    $logout->assertStatus(200);

    $clearedCookie = $logout->getCookie(AuthCookie::cookieName(), false);

    expect($clearedCookie->getValue())->toBe('');
    expect($clearedCookie->getExpiresTime())->toBeLessThan(now()->getTimestamp());

    // Laravel's AuthManager caches a resolved guard (and TokenGuard caches
    // its resolved user) for the lifetime of the container. Within a real
    // browser flow each request is a fresh process so this never applies,
    // but this test simulates several requests in one process and would
    // otherwise silently authenticate off the guard's stale cached user
    // instead of re-validating the now-revoked token against the DB.
    Auth::forgetGuards();

    $replay = $this->withCredentials()
        ->withUnencryptedCookie(AuthCookie::cookieName(), $preLogoutCookie)
        ->getJson('/api/v1/genres');

    $replay->assertStatus(401);

    $secondLogout = $this->withCredentials()
        ->withUnencryptedCookie(AuthCookie::cookieName(), $clearedCookie->getValue())
        ->postJson('/api/v1/logout');

    $secondLogout->assertStatus(401);
});

test('a valid cookie on a state-changing route with no Origin header is rejected (CSRF gap fix)', function () {
    User::factory()->create([
        'email' => 'test@underpass.com',
        'password' => Hash::make('password123'),
    ]);

    $login = postJson('/api/v1/login', [
        'email' => 'test@underpass.com',
        'password' => 'password123',
    ]);

    $cookie = $login->getCookie(AuthCookie::cookieName(), false)->getValue();

    // No Origin header at all -- simulates a plain cross-site
    // <form method="POST"> submission, which never sets Origin.
    $response = $this->withCredentials()
        ->withUnencryptedCookie(AuthCookie::cookieName(), $cookie)
        ->postJson('/api/v1/logout');

    $response->assertStatus(401);
});

test('a valid cookie on a state-changing route with a disallowed Origin header is rejected (CSRF gap fix)', function () {
    User::factory()->create([
        'email' => 'test@underpass.com',
        'password' => Hash::make('password123'),
    ]);

    $login = postJson('/api/v1/login', [
        'email' => 'test@underpass.com',
        'password' => 'password123',
    ]);

    $cookie = $login->getCookie(AuthCookie::cookieName(), false)->getValue();

    $response = $this->withCredentials()
        ->withHeader('Origin', 'https://evil.example.com')
        ->withUnencryptedCookie(AuthCookie::cookieName(), $cookie)
        ->postJson('/api/v1/logout');

    $response->assertStatus(401);
});

test('a valid cookie on a state-changing route with an allowed Origin header still authenticates', function () {
    User::factory()->create([
        'email' => 'test@underpass.com',
        'password' => Hash::make('password123'),
    ]);

    $login = postJson('/api/v1/login', [
        'email' => 'test@underpass.com',
        'password' => 'password123',
    ]);

    $cookie = $login->getCookie(AuthCookie::cookieName(), false)->getValue();

    $response = $this->withCredentials()
        ->withHeader('Origin', 'https://underpass.up.railway.app')
        ->withUnencryptedCookie(AuthCookie::cookieName(), $cookie)
        ->postJson('/api/v1/logout');

    $response->assertStatus(200);
});

test('a request with both an Authorization header and a different cookie authenticates as the header owner', function () {
    $userA = User::factory()->create([
        'email' => 'usera@underpass.com',
        'password' => Hash::make('password123'),
    ]);
    $userB = User::factory()->create([
        'email' => 'userb@underpass.com',
        'password' => Hash::make('password123'),
    ]);

    $loginA = postJson('/api/v1/login', [
        'email' => 'usera@underpass.com',
        'password' => 'password123',
    ]);
    $loginB = postJson('/api/v1/login', [
        'email' => 'userb@underpass.com',
        'password' => 'password123',
    ]);

    $tokenA = $loginA->getCookie(AuthCookie::cookieName(), false)->getValue();
    $tokenB = $loginB->getCookie(AuthCookie::cookieName(), false)->getValue();

    // GET /users/{id} 200s only for the owner (or an admin). If the cookie
    // (user B) ever won over the header (user A), this would 403 instead,
    // since user B does not own user A's profile.
    $response = $this->withHeader('Authorization', 'Bearer '.$tokenA)
        ->withCredentials()
        ->withUnencryptedCookie(AuthCookie::cookieName(), $tokenB)
        ->getJson('/api/v1/users/'.$userA->id);

    $response->assertStatus(200);
});

test('a blank Authorization header falls back to a valid access_token cookie', function () {
    User::factory()->create([
        'email' => 'test@underpass.com',
        'password' => Hash::make('password123'),
    ]);

    $login = postJson('/api/v1/login', [
        'email' => 'test@underpass.com',
        'password' => 'password123',
    ]);

    $cookie = $login->getCookie(AuthCookie::cookieName(), false)->getValue();

    $response = $this->withHeader('Authorization', '')
        ->withCredentials()
        ->withUnencryptedCookie(AuthCookie::cookieName(), $cookie)
        ->getJson('/api/v1/genres');

    $response->assertStatus(200);
});

test('an empty-string access_token cookie on a protected route returns 401', function () {
    $response = $this->withCredentials()
        ->withUnencryptedCookie(AuthCookie::cookieName(), '')
        ->getJson('/api/v1/genres');

    $response->assertStatus(401);
});

test('the public welcome route answers with no credential of any kind', function () {
    $response = getJson('/api/v1/');

    $response->assertStatus(200);
});

test('logging in twice each returns exactly one non-empty access_token cookie', function () {
    User::factory()->create([
        'email' => 'test@underpass.com',
        'password' => Hash::make('password123'),
    ]);

    foreach (range(1, 2) as $_) {
        $response = postJson('/api/v1/login', [
            'email' => 'test@underpass.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(200);

        $cookies = collect($response->headers->getCookies())
            ->filter(fn ($cookie) => $cookie->getName() === AuthCookie::cookieName());

        expect($cookies)->toHaveCount(1);
        expect($cookies->first()->getValue())->not->toBeEmpty();
    }
});
