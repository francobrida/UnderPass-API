<?php

namespace App\Support;

use Illuminate\Support\Facades\Cookie;
use Symfony\Component\HttpFoundation\Cookie as SymfonyCookie;

/**
 * Single source of truth for the auth access-token cookie's name and
 * attributes. Every call site that issues or clears this cookie (login,
 * register, logout) and the bridge middleware that reads it must go
 * through here so the name/path/domain never drift apart.
 */
class AuthCookie
{
    public const NAME = 'access_token';

    /**
     * The `__Host-` prefix is a browser-enforced guarantee (the cookie is
     * rejected outright unless Domain is unset and Secure is true) against
     * subdomain cookie-fixation/injection. Every other attribute this class
     * already sets (domain: null, path: '/', secure: true) satisfies its
     * requirements in every non-local environment, so it's used everywhere
     * except 'local' (where secure is false and the prefix would be
     * rejected by the browser).
     */
    public const NAME_HOST_PREFIXED = '__Host-access_token';

    public static function cookieName(): string
    {
        return app()->environment('local') ? self::NAME : self::NAME_HOST_PREFIXED;
    }

    /**
     * Build the Set-Cookie for a freshly minted Passport token.
     */
    public static function make(string $token, int $minutes): SymfonyCookie
    {
        $secure = ! app()->environment('local');

        return Cookie::make(
            name: self::cookieName(),
            value: $token,
            minutes: $minutes,
            path: '/',
            domain: null,
            secure: $secure,
            httpOnly: true,
            raw: false,
            sameSite: $secure ? 'none' : 'lax',
        );
    }

    /**
     * Build the Set-Cookie that clears the auth cookie on logout. Must
     * match make()'s name/path/domain exactly or the browser will treat
     * it as a different cookie and leave the real one in place.
     */
    public static function forget(): SymfonyCookie
    {
        $secure = ! app()->environment('local');

        return Cookie::make(
            name: self::cookieName(),
            value: '',
            minutes: -1,
            path: '/',
            domain: null,
            secure: $secure,
            httpOnly: true,
            raw: false,
            sameSite: $secure ? 'none' : 'lax',
        );
    }
}
