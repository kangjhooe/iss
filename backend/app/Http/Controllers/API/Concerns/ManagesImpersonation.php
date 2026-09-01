<?php

namespace App\Http\Controllers\API\Concerns;

use App\Http\Middleware\AddTokenFromCookie;
use App\Models\User;
use Illuminate\Support\Facades\Cookie;

trait ManagesImpersonation
{
    protected function canActAsImpersonator(?User $user): bool
    {
        if (! $user instanceof User) {
            return false;
        }

        return $user->isSuperAdmin() || $user->isInstitutionAdmin() || $user->isAdmin();
    }

    protected function makeImpersonationCookie(string $name, string $value, int $minutes): \Symfony\Component\HttpFoundation\Cookie
    {
        $secure = request()->secure();

        return Cookie::make($name, $value, $minutes, '/', config('frontend.cookie_domain'), $secure, true, false, 'lax');
    }

    protected function forgetImpersonationCookie(string $name): \Symfony\Component\HttpFoundation\Cookie
    {
        $secure = request()->secure();

        return Cookie::make($name, '', -1, '/', config('frontend.cookie_domain'), $secure, true, false, 'lax');
    }

    protected function impersonationAlreadyActive(\Illuminate\Http\Request $request): bool
    {
        return (bool) $request->cookie(AddTokenFromCookie::COOKIE_IMPERSONATOR);
    }

    protected function impersonationMetaFor(User $admin): array
    {
        return [
            'active' => true,
            'admin_id' => $admin->id,
            'admin_name' => $admin->name,
            'admin_email' => $admin->email,
        ];
    }
}
