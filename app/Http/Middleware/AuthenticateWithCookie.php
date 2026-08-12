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
        $authHeader = $request->headers->get('Authorization');

        if (is_string($authHeader) && trim($authHeader) !== '') {
            return $next($request);
        }

        $cookie = $request->cookie(AuthCookie::NAME);

        if (! is_string($cookie) || trim($cookie) === '') {
            return $next($request);
        }

        // The browser auto-attaches this cookie to ANY cross-site request
        // (SameSite=None widens who can *send* it), and CORS's allowlist
        // only gates whether the *response* is readable by JS -- it does
        // nothing to stop the request from executing server-side. Simple
        // requests (e.g. a plain cross-site <form method="POST">) never
        // trigger a preflight, so state-changing ("unsafe") methods only
        // trust the cookie once the request also proves it came from an
        // allowlisted frontend. Safe methods (GET/HEAD/OPTIONS) are left
        // alone since they don't mutate state.
        if (! $request->isMethodSafe()) {
            $origin = $request->headers->get('Origin');

            if (! is_string($origin) || ! in_array($origin, config('cors.allowed_origins'), true)) {
                return response()->json(['message' => 'Unauthenticated.'], 401);
            }
        }

        $request->headers->set('Authorization', 'Bearer '.$cookie);

        return $next($request);
    }
}
