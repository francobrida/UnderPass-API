<?php

use function Pest\Laravel\getJson;

$allowedOrigins = [
    'https://underpass.up.railway.app',
    'https://underpass-demo.up.railway.app',
    'http://localhost:5173',
];

test('allowed origins get their exact origin echoed back', function (string $origin) {
    $response = getJson('/api/v1/', ['Origin' => $origin]);

    $response->assertStatus(200)
              ->assertHeader('Access-Control-Allow-Origin', $origin);
})->with($allowedOrigins);

test('a disallowed origin receives no Access-Control-Allow-Origin header', function () {
    $response = getJson('/api/v1/', ['Origin' => 'https://evil.example.com']);

    $response->assertStatus(200)
              ->assertHeaderMissing('Access-Control-Allow-Origin');
});

test('an OPTIONS preflight from a disallowed origin receives no Access-Control-Allow-Origin header', function () {
    $response = $this->call('OPTIONS', '/api/v1/login', server: [
        'HTTP_ORIGIN' => 'https://evil.example.com',
        'HTTP_ACCESS_CONTROL_REQUEST_METHOD' => 'POST',
    ]);

    $response->assertHeaderMissing('Access-Control-Allow-Origin');
});

test('per-request origin isolation: an allowed and a disallowed request each get their own correct decision', function () {
    $allowed = getJson('/api/v1/', ['Origin' => 'https://underpass.up.railway.app']);
    $denied = getJson('/api/v1/', ['Origin' => 'https://evil.example.com']);

    $allowed->assertStatus(200)
            ->assertHeader('Access-Control-Allow-Origin', 'https://underpass.up.railway.app');

    $denied->assertStatus(200)
           ->assertHeaderMissing('Access-Control-Allow-Origin');
});

test('cors config guards against wildcard or pattern-based origin matching', function () {
    expect(config('cors.allowed_origins_patterns'))->toBe([]);
    expect(config('cors.supports_credentials'))->toBeFalse();
});
