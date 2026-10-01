<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

// Kompatibilitas: paket lama (mis. l5-swagger versi lama) memakai Application::share() yang sudah dihapus di Laravel 5.4+.
// share($closure) dulu dipakai: $app['key'] = $app->share(function($app) { ... }); kita emulasikan dengan closure yang singleton.
Application::macro('share', function (\Closure $closure) {
    $cached = null;
    return function ($app) use ($closure, &$cached) {
        if ($cached === null) {
            $cached = $closure($app);
        }
        return $cached;
    };
});

return Application::configure(basePath: dirname(__DIR__))
    ->withProviders([
        \App\Providers\AppServiceProvider::class,
        \App\Providers\OptionalPurifierServiceProvider::class,
        \L5Swagger\L5SwaggerServiceProvider::class,
    ])
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Di balik reverse proxy / Cloudflare, IP klien harus dari X-Forwarded-For
        // agar throttle login tidak menumpuk semua user ke satu bucket IP proxy.
        $middleware->trustProxies(at: '*');

        // Baca token dari httpOnly cookie ke Authorization header (sebelum auth:sanctum)
        $middleware->api(prepend: [
            \App\Http\Middleware\AddTokenFromCookie::class,
        ]);

        $middleware->api(append: [
            \App\Http\Middleware\EnsureNotInMaintenance::class,
        ]);

        // CSRF protection tidak diperlukan untuk API routes yang menggunakan Bearer token
        $middleware->validateCsrfTokens(except: [
            'api/*',
        ]);

        $middleware->alias([
            'verified' => \App\Http\Middleware\EnsureEmailIsVerified::class,
            'super_admin' => \App\Http\Middleware\EnsureSuperAdmin::class,
            'cache' => \App\Http\Middleware\CacheResponse::class,
            'module' => \App\Http\Middleware\EnsureModuleAccess::class,
            'maintenance' => \App\Http\Middleware\EnsureNotInMaintenance::class,
            'institution.context' => \App\Http\Middleware\ResolveActiveInstitution::class,
            'monetization.launched' => \App\Http\Middleware\EnsureMonetizationLaunched::class,
            'online_exam.entitled' => \App\Http\Middleware\EnsureOnlineExamEntitled::class,
            'storage.quota' => \App\Http\Middleware\EnsureStorageQuota::class,
            'vocational' => \App\Http\Middleware\EnsureVocationalInstitution::class,
            'swagger.enabled' => \App\Http\Middleware\EnsureSwaggerDocumentationEnabled::class,
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
