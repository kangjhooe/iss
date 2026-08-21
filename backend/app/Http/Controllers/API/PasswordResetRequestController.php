<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePasswordResetRequestRequest;
use App\Models\PasswordResetRequest;
use App\Models\User;
use App\Notifications\PasswordResetRequestNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PasswordResetRequestController extends Controller
{
    private const ACCEPTED_MESSAGE = 'Jika data cocok dengan akun admin sekolah, permintaan telah dikirim. Admin akan menghubungi Anda melalui email.';

    /**
     * Public: school admin requests a password reset via super admin.
     */
    public function store(StorePasswordResetRequestRequest $request)
    {
        try {
            if (! Schema::hasTable('password_reset_requests')) {
                return response()->json(['message' => self::ACCEPTED_MESSAGE]);
            }

            $validated = $request->validated();
            $email = strtolower(trim($validated['email']));
            $npsn = trim($validated['npsn']);

            $user = User::with('institution:id,name,npsn')
                ->where('role', 'institution_admin')
                ->whereRaw('LOWER(email) = ?', [$email])
                ->first();

            $institution = $user?->institution;
            if (! $user || ! $institution || (string) $institution->npsn !== $npsn) {
                return response()->json(['message' => self::ACCEPTED_MESSAGE]);
            }

            $existing = PasswordResetRequest::query()
                ->where('user_id', $user->id)
                ->pending()
                ->first();

            if ($existing) {
                $existing->update([
                    'email' => $user->email,
                    'npsn' => $npsn,
                    'contact_phone' => $validated['contact_phone'] ?? $existing->contact_phone,
                    'note' => $validated['note'] ?? $existing->note,
                    'ip_address' => $request->ip(),
                    'institution_id' => $institution->id,
                ]);

                return response()->json(['message' => self::ACCEPTED_MESSAGE]);
            }

            $resetRequest = PasswordResetRequest::create([
                'user_id' => $user->id,
                'institution_id' => $institution->id,
                'email' => $user->email,
                'npsn' => $npsn,
                'contact_phone' => $validated['contact_phone'] ?? null,
                'note' => $validated['note'] ?? null,
                'ip_address' => $request->ip(),
                'status' => PasswordResetRequest::STATUS_PENDING,
            ]);

            $this->notifySuperAdmins($resetRequest);

            Log::info('Password reset requested via admin', [
                'request_id' => $resetRequest->id,
                'user_id' => $user->id,
                'institution_id' => $institution->id,
            ]);

            return response()->json(['message' => self::ACCEPTED_MESSAGE]);
        } catch (\Exception $e) {
            Log::error('Failed to store password reset request', [
                'error' => $e->getMessage(),
            ]);

            return response()->json(['message' => self::ACCEPTED_MESSAGE]);
        }
    }

    /**
     * Super admin: list reset requests.
     */
    public function index(Request $request)
    {
        $this->ensureSuperAdmin($request);

        try {
            if (! Schema::hasTable('password_reset_requests')) {
                return response()->json(['data' => [], 'meta' => ['total' => 0]]);
            }

            $query = PasswordResetRequest::with([
                'user:id,name,email,is_active',
                'institution:id,name,npsn,level,is_active',
                'processor:id,name,email',
            ])->orderByDesc('created_at');

            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            $perPage = min((int) $request->get('per_page', 20), 100);

            return response()->json($query->paginate($perPage));
        } catch (\Exception $e) {
            Log::error('Failed to list password reset requests', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Terjadi kesalahan saat mengambil permintaan reset sandi',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Super admin: pending count for badge/dashboard.
     */
    public function pendingCount(Request $request)
    {
        $this->ensureSuperAdmin($request);

        try {
            if (! Schema::hasTable('password_reset_requests')) {
                return response()->json(['count' => 0]);
            }

            return response()->json([
                'count' => PasswordResetRequest::pending()->count(),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to get pending password reset count', [
                'error' => $e->getMessage(),
            ]);

            return response()->json(['count' => 0]);
        }
    }

    /**
     * Super admin: generate a new password and mark the request processed.
     */
    public function process(Request $request, $id)
    {
        $this->ensureSuperAdmin($request);

        try {
            $resetRequest = PasswordResetRequest::with(['user', 'institution'])->findOrFail($id);

            if (! $resetRequest->canBeProcessed()) {
                throw ValidationException::withMessages([
                    'status' => ['Permintaan ini tidak dapat diproses.'],
                ]);
            }

            $user = $resetRequest->user;
            if (! $user || $user->role !== 'institution_admin') {
                return response()->json(['message' => 'Admin institusi tidak ditemukan'], 404);
            }

            $plainPassword = $this->generatePassword();
            $user->update([
                'password' => Hash::make($plainPassword),
            ]);
            $user->resetFailedLoginAttempts();

            PasswordResetRequest::markPendingProcessedForUser($user->id, $request->user()->id);

            Log::info('Password reset request processed', [
                'request_id' => $resetRequest->id,
                'user_id' => $user->id,
                'processed_by' => $request->user()->id,
            ]);

            return response()->json([
                'message' => 'Sandi berhasil direset. Kirim sandi baru ke email sekolah secara manual.',
                'temporary_password' => $plainPassword,
                'data' => $resetRequest->fresh(['user:id,name,email', 'institution:id,name,npsn']),
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Permintaan tidak ditemukan'], 404);
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('Failed to process password reset request', [
                'request_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Terjadi kesalahan saat memproses permintaan',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Super admin: reject a pending request.
     */
    public function reject(Request $request, $id)
    {
        $this->ensureSuperAdmin($request);

        try {
            $validated = $request->validate([
                'rejection_reason' => ['nullable', 'string', 'max:1000'],
            ]);

            $resetRequest = PasswordResetRequest::findOrFail($id);

            if (! $resetRequest->canBeRejected()) {
                throw ValidationException::withMessages([
                    'status' => ['Permintaan ini tidak dapat ditolak.'],
                ]);
            }

            $resetRequest->update([
                'status' => PasswordResetRequest::STATUS_REJECTED,
                'processed_by' => $request->user()->id,
                'processed_at' => now(),
                'rejection_reason' => $validated['rejection_reason'] ?? null,
            ]);

            Log::info('Password reset request rejected', [
                'request_id' => $resetRequest->id,
                'processed_by' => $request->user()->id,
            ]);

            return response()->json([
                'message' => 'Permintaan reset sandi ditolak',
                'data' => $resetRequest->fresh(['user:id,name,email', 'institution:id,name,npsn']),
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Permintaan tidak ditemukan'], 404);
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('Failed to reject password reset request', [
                'request_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Terjadi kesalahan saat menolak permintaan',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    private function ensureSuperAdmin(Request $request): void
    {
        if (! $request->user()?->isSuperAdmin()) {
            abort(403, 'Unauthorized');
        }
    }

    private function notifySuperAdmins(PasswordResetRequest $resetRequest): void
    {
        try {
            $superAdmins = User::query()
                ->where('role', 'super_admin')
                ->where(function ($q) {
                    $q->whereNull('is_active')->orWhere('is_active', true);
                })
                ->get();

            if ($superAdmins->isNotEmpty()) {
                Notification::send(
                    $superAdmins,
                    new PasswordResetRequestNotification($resetRequest)
                );
            }
        } catch (\Exception $notifyError) {
            Log::warning('Password reset request created but notification failed', [
                'request_id' => $resetRequest->id,
                'error' => $notifyError->getMessage(),
            ]);
        }
    }

    private function generatePassword(): string
    {
        return 'Adm!'.Str::upper(Str::random(3)).Str::lower(Str::random(3)).random_int(10, 99);
    }
}
