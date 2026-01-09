<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class CacheResponse
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, int $ttl = 60): Response
    {
        // Only cache GET requests
        if ($request->method() !== 'GET') {
            return $next($request);
        }

        // Generate cache key based on request
        $cacheKey = $this->getCacheKey($request);

        // Check if cached response exists
        if (Cache::has($cacheKey)) {
            return response()->json(Cache::get($cacheKey));
        }

        // Get response
        $response = $next($request);

        // Cache successful responses only
        if ($response->getStatusCode() === 200 && $response->headers->get('Content-Type') === 'application/json') {
            $content = json_decode($response->getContent(), true);
            if ($content) {
                Cache::put($cacheKey, $content, now()->addSeconds($ttl));
            }
        }

        return $response;
    }

    /**
     * Generate cache key from request.
     */
    protected function getCacheKey(Request $request): string
    {
        $user = $request->user();
        $userId = $user ? $user->id : 'guest';
        
        $key = sprintf(
            'api:%s:%s:%s',
            $request->path(),
            $userId,
            md5($request->getQueryString() ?? '')
        );

        return $key;
    }
}
