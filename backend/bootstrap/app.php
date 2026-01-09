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
        // CSRF protection tidak diperlukan untuk API routes yang menggunakan Bearer token
        $middleware->validateCsrfTokens(except: [
            'api/*',
        ]);

        $middleware->alias([
            'verified' => \App\Http\Middleware\EnsureEmailIsVerified::class,
            'super_admin' => \App\Http\Middleware\EnsureSuperAdmin::class,
        ]);

        // Force JSON response for API routes FIRST (before authentication)
        // This prevents redirect to login route on authentication failure
        $middleware->prepend(\App\Http\Middleware\ForceJsonResponse::class);
        
        // Add security headers middleware globally
        $middleware->append(\App\Http\Middleware\SecurityHeaders::class);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Use custom handler for all exceptions
        $exceptions->render(function (\Throwable $e, \Illuminate\Http\Request $request) {
            $handler = new \App\Exceptions\Handler(app());
            return $handler->render($request, $e);
        });
    })->create();
