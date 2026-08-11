<?php

namespace App\Http\Middleware;

use App\Support\AuthCookie;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Relocates the access-token cookie onto the Authorization header so
 * Passport's TokenGuard (which only reads the header) can authenticate
 * the request. Takes no trust decision: signature verification and the
 * revoked/expires lookup stay entirely inside Passport's TokenGuard.
 *
 * Must run only inside the auth:api group, before auth:api itself.
 */
class AuthenticateWithCookie
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->headers->has('Authorization')) {
            return $next($request);
        }

        $cookie = $request->cookie(AuthCookie::NAME);

        if (! is_string($cookie) || trim($cookie) === '') {
            return $next($request);
        }

        $request->headers->set('Authorization', 'Bearer '.$cookie);

        return $next($request);
    }
}
