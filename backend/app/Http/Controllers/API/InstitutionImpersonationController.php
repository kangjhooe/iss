<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\API\Concerns\ManagesImpersonation;
use App\Http\Controllers\Controller;
use App\Http\Middleware\AddTokenFromCookie;
use App\Http\Resources\UserResource;
use App\Models\AuditLog;
use App\Models\Employee;
use App\Models\User;
use App\Support\InstitutionContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class InstitutionImpersonationController extends Controller
{
    use ManagesImpersonation;

    /**
     * Admin institusi masuk sebagai akun guru/staff pegawai.
     */
    public function start(Request $request, $id)
    {
        $admin = $request->user();
        if (! $this->canActAsImpersonator($admin)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($this->impersonationAlreadyActive($request)) {
            return response()->json([
                'message' => 'Anda sudah dalam mode impersonate. Keluar dulu sebelum menyamar lagi.',
            ], 422);
        }

        try {
            $employee = Employee::findOrFail($id);

            if (! $admin->isAdminOrSuperAdmin()) {
                $institutionId = InstitutionContext::resolveForUser($admin, $request, $request->get('institution_id'));
                if (! $institutionId || (int) $employee->institution_id !== (int) $institutionId) {
                    return response()->json(['message' => 'Unauthorized'], 403);
                }
            }

            if (! $employee->email || ! $employee->hasUserAccount()) {
                return response()->json([
                    'message' => 'Pegawai ini belum memiliki akun login.',
                ], 422);
            }

            $target = User::with(['institution', 'permissions'])
                ->where('email', $employee->email)
                ->first();

            if (! $target) {
                return response()->json([
                    'message' => 'Akun login tidak ditemukan.',
                ], 404);
            }

            if (! in_array($target->role, ['teacher', 'staff'], true)) {
                return response()->json([
                    'message' => 'Hanya dapat masuk sebagai akun pegawai (guru/staff).',
                ], 422);
            }

            if ($target->is_active === false) {
                return response()->json([
                    'message' => 'Tidak dapat masuk sebagai akun yang nonaktif.',
                ], 422);
            }

            $currentAuth = $request->cookie(AddTokenFromCookie::COOKIE_AUTH);
            $currentRefresh = $request->cookie(AddTokenFromCookie::COOKIE_REFRESH);

            if (! $currentAuth) {
                return response()->json([
                    'message' => 'Sesi admin tidak valid. Silakan login ulang.',
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
                    'employee_id' => $employee->id,
                ],
                $employee->institution_id
            );

            $accessToken = $target->createToken('auth_token')->plainTextToken;
            $refreshToken = $target->createToken('refresh_token', ['refresh'])->plainTextToken;

            $userPayload = (new UserResource($target))->resolve();
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

            Log::info('Institution impersonation started', [
                'impersonator_id' => $admin->id,
                'target_id' => $target->id,
                'employee_id' => $employee->id,
            ]);

            return $response;
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Pegawai tidak ditemukan'], 404);
        } catch (\Exception $e) {
            Log::error('Institution impersonation start failed', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Gagal memulai impersonate',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }
}
