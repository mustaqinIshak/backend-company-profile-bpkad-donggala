<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Trust local reverse proxy (Nginx) so $request->isSecure() works correctly
        // behind SSL termination
        $middleware->trustProxies(at: '127.0.0.1');

        $middleware->api(prepend: [
            // 1. Decrypt incoming cookies before our middleware reads them
            \Illuminate\Cookie\Middleware\EncryptCookies::class,
            // 2. Inject auth_token cookie as Authorization header (httpOnly cookie auth)
            \App\Http\Middleware\SetTokenFromCookie::class,
            // 3. Sanctum stateful SPA support
            \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
        ]);

        // Security headers on every API response (CSP, HSTS, X-Frame-Options, etc.)
        $middleware->appendToGroup('api', \App\Http\Middleware\SecurityHeaders::class);

        $middleware->alias([
            'verified'   => \Illuminate\Auth\Middleware\EnsureEmailIsVerified::class,
            'role'       => \App\Http\Middleware\CheckRole::class,
            'permission' => \App\Http\Middleware\CheckPermission::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (\Illuminate\Auth\AuthenticationException $e, $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated. Please login first.',
                ], 401);
            }
        });

        $exceptions->render(function (\Illuminate\Validation\ValidationException $e, $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed.',
                    'errors'  => $e->errors(),
                ], 422);
            }
        });

        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e, $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Resource not found.',
                ], 404);
            }
        });
    })->create();
