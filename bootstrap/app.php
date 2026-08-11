<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Railway terminates TLS/proxies at its edge before the container reaches
        // this app, so trusting all proxies is the standard safe posture for this
        // PaaS (edge IPs aren't published/stable, so CIDR-restricting isn't viable).
        // Without this, Request::ip() resolves to Railway's internal proxy address
        // for every request, collapsing the login/register rate limiters into a
        // single shared bucket (see 01-REVIEW.md WR-01).
        $middleware->trustProxies(at: '*', headers: Request::HEADER_X_FORWARDED_FOR | Request::HEADER_X_FORWARDED_HOST | Request::HEADER_X_FORWARDED_PORT | Request::HEADER_X_FORWARDED_PROTO);

        $middleware->alias([
            'admin' => \App\Http\Middleware\AdminMiddleware::class,
            'auth.cookie' => \App\Http\Middleware\AuthenticateWithCookie::class,
        ]);

        // The 'api' group's SubstituteBindings sits in Laravel's default
        // middlewarePriority list, and Authenticate (auth:api) does too
        // (via the AuthenticatesRequests interface it implements — that
        // interface, not the concrete class, is what's actually in the
        // priority array). Without this, the priority sorter reorders
        // Authenticate ahead of SubstituteBindings whenever both are
        // present alongside our route-order-only bridge, dragging
        // Authenticate past auth.cookie as a side effect and making the
        // guard resolve before the cookie is ever bridged onto the
        // Authorization header.
        $middleware->prependToPriorityList(
            before: \Illuminate\Contracts\Auth\Middleware\AuthenticatesRequests::class,
            prepend: \App\Http\Middleware\AuthenticateWithCookie::class,
        );
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
