<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use function Pest\Laravel\postJson;

uses(RefreshDatabase::class);

test('the 6th login attempt within a minute for the same email and IP is throttled', function () {
    $credentials = [
        'email' => 'throttle-login@underpass.com',
        'password' => 'wrong-password',
    ];

    for ($i = 1; $i <= 5; $i++) {
        $response = postJson('/api/v1/login', $credentials);
        $response->assertStatus(401);
    }

    $response = postJson('/api/v1/login', $credentials);
    $response->assertStatus(429);
});

test('the 429 login response carries a sane Retry-After header', function () {
    $credentials = [
        'email' => 'throttle-retry-after@underpass.com',
        'password' => 'wrong-password',
    ];

    for ($i = 1; $i <= 5; $i++) {
        postJson('/api/v1/login', $credentials);
    }

    $response = postJson('/api/v1/login', $credentials);
    $response->assertStatus(429);

    $retryAfter = $response->headers->get('Retry-After');

    expect($retryAfter)->not->toBeNull();
    expect(is_numeric($retryAfter))->toBeTrue();
    expect((int) $retryAfter)->toBeGreaterThan(0)->toBeLessThanOrEqual(60);
});

test('login throttle bucket is shared across email capitalization variants', function () {
    $upper = [
        'email' => 'TEST@UNDERPASS.COM',
        'password' => 'wrong-password',
    ];

    for ($i = 1; $i <= 5; $i++) {
        postJson('/api/v1/login', $upper);
    }

    $lower = [
        'email' => 'test@underpass.com',
        'password' => 'wrong-password',
    ];

    $response = postJson('/api/v1/login', $lower);
    $response->assertStatus(429);
});
