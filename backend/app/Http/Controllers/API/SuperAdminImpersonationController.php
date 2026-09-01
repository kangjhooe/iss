<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\API\Concerns\ManagesImpersonation;
use App\Http\Controllers\Controller;
use App\Http\Middleware\AddTokenFromCookie;
use App\Http\Resources\UserResource;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Laravel\Sanctum\PersonalAccessToken;

class SuperAdminImpersonationController extends Controller
{
    use ManagesImpersonation;

    public function start(Request $request, $id)
    {
        $admin = $request->user();
        if (!$admin?->isSuperAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($this->impersonationAlreadyActive($request)) {
            return response()->json([
                'message' => 'Anda sudah dalam mode impersonate. Keluar dulu sebelum menyamar lagi.',
            ], 422);
        }

        try {
            $target = User::with('institution')->where('role', 'institution_admin')->findOrFail($id);

            if ($target->is_active === false) {
                return response()->json([
                    'message' => 'Tidak dapat menyamar sebagai admin yang nonaktif.',
                ], 422);
            }

            if ($target->institution && $target->institution->is_active === false) {
                return response()->json([
                    'message' => 'Institusi admin ini sedang dibekukan.',
                ], 422);
            }

            $currentAuth = $request->cookie(AddTokenFromCookie::COOKIE_AUTH);
            $currentRefresh = $request->cookie(AddTokenFromCookie::COOKIE_REFRESH);

            if (!$currentAuth) {
                return response()->json([
                    'message' => 'Sesi super admin tidak valid. Silakan login ulang.',
                ], 401);
            }

            AuditLog::logManual(
                $request,
                'impersonation.start',
                User::class,
                $target->id,
                null,
                [
                    'impersonator_id' => $admin->id,
                    'impersonator_email' => $admin->email,
                    'target_id' => $target->id,
                    'target_email' => $target->email,
                ],
                $target->institution_id
            );

            $accessToken = $target->createToken('auth_token')->plainTextToken;
            $refreshToken = $target->createToken('refresh_token', ['refresh'])->plainTextToken;

            $loads = ['institution', 'permissions'];
            $userPayload = (new UserResource($target->load($loads)))->resolve();
            $userPayload['impersonation'] = $this->impersonationMetaFor($admin);

            $response = response()->json([
                'message' => 'Berhasil masuk sebagai ' . $target->name,
                'user' => $userPayload,
            ]);

            $response->cookie($this->makeImpersonationCookie(AddTokenFromCookie::COOKIE_AUTH, $accessToken, 24 * 60));
            $response->cookie($this->makeImpersonationCookie(AddTokenFromCookie::COOKIE_REFRESH, $refreshToken, 30 * 24 * 60));
            $response->cookie($this->makeImpersonationCookie(AddTokenFromCookie::COOKIE_IMPERSONATOR, $currentAuth, 24 * 60));
            if ($currentRefresh) {
                $response->cookie($this->makeImpersonationCookie(AddTokenFromCookie::COOKIE_IMPERSONATOR_REFRESH, $currentRefresh, 30 * 24 * 60));
            }

            Log::info('Impersonation started', [
                'impersonator_id' => $admin->id,
                'target_id' => $target->id,
            ]);

            return $response;
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Admin institusi tidak ditemukan'], 404);
        } catch (\Exception $e) {
            Log::error('Impersonation start failed', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Gagal memulai impersonate',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    public function stop(Request $request)
    {
        try {
            $impersonatorToken = $request->cookie(AddTokenFromCookie::COOKIE_IMPERSONATOR);
            if (!$impersonatorToken) {
                return response()->json([
                    'message' => 'Tidak ada sesi impersonate yang aktif.',
                ], 422);
            }

            $tokenModel = PersonalAccessToken::findToken($impersonatorToken);
            $admin = $tokenModel?->tokenable;
            if (! $this->canActAsImpersonator($admin)) {
                return response()->json([
                    'message' => 'Sesi admin tidak valid. Silakan login ulang.',
                ], 401);
            }

            $currentUser = $request->user();
            if ($currentUser) {
                AuditLog::logManual(
                    $request,
                    'impersonation.stop',
                    User::class,
                    $currentUser->id,
                    null,
                    [
                        'impersonator_id' => $admin->id,
                        'target_id' => $currentUser->id,
                        'target_email' => $currentUser->email,
                    ],
                    $currentUser->institution_id
                );

                // Hapus token sesi yang sedang dipakai (user yang disamar)
                $currentUser->currentAccessToken()?->delete();
            }

            $impersonatorRefresh = $request->cookie(AddTokenFromCookie::COOKIE_IMPERSONATOR_REFRESH);

            $loads = ['institution', 'permissions'];
            $userPayload = (new UserResource($admin->load($loads)))->resolve();
            $userPayload['impersonation'] = [
                'active' => false,
                'admin_id' => null,
                'admin_name' => null,
                'admin_email' => null,
            ];

            $response = response()->json([
                'message' => 'Kembali ke akun admin',
                'user' => $userPayload,
            ]);

            $response->cookie($this->makeImpersonationCookie(AddTokenFromCookie::COOKIE_AUTH, $impersonatorToken, 24 * 60));
            if ($impersonatorRefresh) {
                $response->cookie($this->makeImpersonationCookie(AddTokenFromCookie::COOKIE_REFRESH, $impersonatorRefresh, 30 * 24 * 60));
            }
            $response->cookie($this->forgetImpersonationCookie(AddTokenFromCookie::COOKIE_IMPERSONATOR));
            $response->cookie($this->forgetImpersonationCookie(AddTokenFromCookie::COOKIE_IMPERSONATOR_REFRESH));

            Log::info('Impersonation stopped', [
                'impersonator_id' => $admin->id,
                'target_id' => $currentUser?->id,
            ]);

            return $response;
        } catch (\Exception $e) {
            Log::error('Impersonation stop failed', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Gagal keluar dari impersonate',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

}
