<?php

use function Pest\Laravel\getJson;

$allowedOrigins = [
    'https://underpass.up.railway.app',
    'https://underpass-demo.up.railway.app',
    'http://localhost:5173',
];

test('allowed origins get their exact origin echoed back with credentials allowed', function (string $origin) {
    $response = getJson('/api/v1/', ['Origin' => $origin]);

    $response->assertStatus(200)
              ->assertHeader('Access-Control-Allow-Origin', $origin)
              ->assertHeader('Access-Control-Allow-Credentials', 'true');
})->with($allowedOrigins);

test('a disallowed origin receives no Access-Control-Allow-Origin or Access-Control-Allow-Credentials header', function () {
    $response = getJson('/api/v1/', ['Origin' => 'https://evil.example.com']);

    $response->assertStatus(200)
              ->assertHeaderMissing('Access-Control-Allow-Origin')
              ->assertHeaderMissing('Access-Control-Allow-Credentials');
});

test('an OPTIONS preflight from a disallowed origin receives no Access-Control-Allow-Origin or Access-Control-Allow-Credentials header', function () {
    $response = $this->call('OPTIONS', '/api/v1/login', server: [
        'HTTP_ORIGIN' => 'https://evil.example.com',
        'HTTP_ACCESS_CONTROL_REQUEST_METHOD' => 'POST',
    ]);

    $response->assertHeaderMissing('Access-Control-Allow-Origin');
    $response->assertHeaderMissing('Access-Control-Allow-Credentials');
});

test('an OPTIONS preflight from an allowed origin receives Access-Control-Allow-Origin and Access-Control-Allow-Credentials', function () {
    $response = $this->call('OPTIONS', '/api/v1/login', server: [
        'HTTP_ORIGIN' => 'https://underpass.up.railway.app',
        'HTTP_ACCESS_CONTROL_REQUEST_METHOD' => 'POST',
        'HTTP_ACCESS_CONTROL_REQUEST_HEADERS' => 'authorization, content-type',
    ]);

    $response->assertHeader('Access-Control-Allow-Origin', 'https://underpass.up.railway.app');
    $response->assertHeader('Access-Control-Allow-Credentials', 'true');
});

test('per-request origin isolation: an allowed and a disallowed request each get their own correct decision', function () {
    $allowed = getJson('/api/v1/', ['Origin' => 'https://underpass.up.railway.app']);
    $denied = getJson('/api/v1/', ['Origin' => 'https://evil.example.com']);

    $allowed->assertStatus(200)
            ->assertHeader('Access-Control-Allow-Origin', 'https://underpass.up.railway.app')
            ->assertHeader('Access-Control-Allow-Credentials', 'true');

    $denied->assertStatus(200)
           ->assertHeaderMissing('Access-Control-Allow-Origin')
           ->assertHeaderMissing('Access-Control-Allow-Credentials');
});

test('cors config enables credentials without widening the origin policy', function () {
    expect(config('cors.allowed_origins_patterns'))->toBe([]);
    expect(config('cors.supports_credentials'))->toBeTrue();
    expect(config('cors.allowed_origins'))->toHaveCount(3);
    expect(config('cors.allowed_origins'))->toBe([
        'https://underpass.up.railway.app',
        'https://underpass-demo.up.railway.app',
        'http://localhost:5173',
    ]);
});
