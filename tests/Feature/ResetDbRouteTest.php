<?php

use Illuminate\Support\Facades\Artisan;

$routes = ['/reset-db-demo', '/reset-db-prod'];

test('no token param at all returns 404 and never invokes Artisan', function (string $route) {
    Artisan::shouldReceive('call')->never();

    config()->set('app.reset_db_token', 'test-guard-token-value');

    $this->get($route)->assertNotFound();
})->with($routes);

test('a wrong token param returns 404 and never invokes Artisan', function (string $route) {
    Artisan::shouldReceive('call')->never();

    config()->set('app.reset_db_token', 'test-guard-token-value');

    $this->get($route . '?token=wrong-token')->assertNotFound();
})->with($routes);

test('token config null with the would-be-correct token supplied returns 404', function (string $route) {
    Artisan::shouldReceive('call')->never();

    config()->set('app.reset_db_token', null);

    $this->get($route . '?token=test-guard-token-value')->assertNotFound();
})->with($routes);

test('token config null with an empty token param returns 404', function (string $route) {
    Artisan::shouldReceive('call')->never();

    config()->set('app.reset_db_token', null);

    $this->get($route . '?token=')->assertNotFound();
})->with($routes);

test('token config empty string with an empty token param returns 404', function (string $route) {
    Artisan::shouldReceive('call')->never();

    config()->set('app.reset_db_token', '');

    $this->get($route . '?token=')->assertNotFound();
})->with($routes);

test('correct token on /reset-db-prod with the prod flag disabled returns 404', function () {
    Artisan::shouldReceive('call')->never();

    config()->set('app.reset_db_token', 'test-guard-token-value');
    config()->set('app.allow_prod_db_reset', false);

    $this->get('/reset-db-prod?token=test-guard-token-value')->assertNotFound();
});

test('correct token on /reset-db-demo passes the guard without wiping anything', function () {
    config()->set('app.reset_db_token', 'test-guard-token-value');
    config()->set('app.demo', true);

    Artisan::shouldReceive('call')->once()->andThrow(new \RuntimeException('RESET_GUARD_PASSED'));

    $response = $this->get('/reset-db-demo?token=test-guard-token-value');

    $response->assertOk();
    $response->assertSee('RESET_GUARD_PASSED');
    $response->assertDontSee('Proceso DEMO completado con éxito');
});

test('correct token on /reset-db-demo with the demo flag off returns 404', function () {
    Artisan::shouldReceive('call')->never();

    config()->set('app.reset_db_token', 'test-guard-token-value');
    config()->set('app.demo', false);

    $this->get('/reset-db-demo?token=test-guard-token-value')->assertNotFound();
});

test('correct token on /reset-db-prod with the flag enabled passes the guard without wiping anything', function () {
    config()->set('app.reset_db_token', 'test-guard-token-value');
    config()->set('app.allow_prod_db_reset', true);

    Artisan::shouldReceive('call')->once()->andThrow(new \RuntimeException('RESET_GUARD_PASSED'));

    $response = $this->get('/reset-db-prod?token=test-guard-token-value');

    $response->assertOk();
    $response->assertSee('RESET_GUARD_PASSED');
    $response->assertDontSee('Proceso PRODUCCIÓN completado con éxito');
});
