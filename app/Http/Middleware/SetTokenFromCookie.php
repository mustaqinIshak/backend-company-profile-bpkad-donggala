<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetTokenFromCookie
{
    /**
     * If no Authorization header is present, read the httpOnly auth_token
     * cookie and inject it as the Bearer token so Sanctum can authenticate
     * the request transparently — without the token ever touching JavaScript.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->bearerToken() && $request->hasCookie('auth_token')) {
            $request->headers->set(
                'Authorization',
                'Bearer ' . $request->cookie('auth_token')
            );
        }

        return $next($request);
    }
}
