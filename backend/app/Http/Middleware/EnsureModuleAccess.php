<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureModuleAccess
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $moduleKey)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        // Support multiple modules separated by | (user needs access to any one)
        $keys = array_map('trim', explode('|', $moduleKey));
        $hasAccess = false;
        foreach ($keys as $key) {
            if ($key && $user->hasModuleAccess($key)) {
                $hasAccess = true;
                break;
            }
        }
        if (!$hasAccess) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        return $next($request);
    }
}
