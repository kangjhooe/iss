<?php

namespace App\Http\Middleware;

use App\Support\InstitutionContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolveActiveInstitution
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if ($user) {
            InstitutionContext::applyToRequest($request, $user);
        }

        return $next($request);
    }
}
