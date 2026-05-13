<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        // Force HTTPS in production (defense-in-depth; Nginx should also handle this)
        if (app()->environment('production') && ! $request->isSecure()) {
            return redirect()->secure($request->getRequestUri(), 301);
        }

        $response = $next($request);

        // HSTS: instruct browsers to only connect via HTTPS for 1 year
        if (app()->environment('production')) {
            $response->headers->set(
                'Strict-Transport-Security',
                'max-age=31536000; includeSubDomains; preload'
            );
        }

        // Prevent clickjacking
        $response->headers->set('X-Frame-Options', 'DENY');

        // Prevent MIME-type sniffing attacks
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        // Legacy XSS filter (IE / old Edge)
        $response->headers->set('X-XSS-Protection', '1; mode=block');

        // Limit referrer information leakage
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        // Restrict browser feature APIs
        $response->headers->set(
            'Permissions-Policy',
            'camera=(), microphone=(), geolocation=(), payment=()'
        );

        // CSP: this is a pure JSON REST API — no HTML/script rendering needed
        $response->headers->set(
            'Content-Security-Policy',
            "default-src 'none'; frame-ancestors 'none'"
        );

        // Remove server fingerprinting headers
        $response->headers->remove('X-Powered-By');
        $response->headers->remove('Server');

        return $response;
    }
}
