<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\Institution;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class InstitutionAdminController extends Controller
{
    private function ensureSuperAdmin(Request $request): void
    {
        if (!$request->user()?->isSuperAdmin()) {
            abort(403, 'Unauthorized');
        }
    }

    /**
     * List institution admins (optionally filter by institution_id).
     */
    public function index(Request $request)
    {
        $this->ensureSuperAdmin($request);

        try {
            $query = User::with('institution:id,name,npsn,level,is_active')
                ->where('role', 'institution_admin')
                ->orderBy('name');

            if ($request->filled('institution_id')) {
                $query->where('institution_id', $request->institution_id);
            }

            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', '%' . $search . '%')
                        ->orWhere('email', 'like', '%' . $search . '%');
                });
            }

            if ($request->has('is_active')) {
                $query->where('is_active', filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN));
            }

            $perPage = min((int) $request->get('per_page', 20), 100);
            $admins = $query->paginate($perPage);

            return UserResource::collection($admins);
        } catch (\Exception $e) {
            Log::error('Failed to list institution admins', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Terjadi kesalahan saat mengambil data admin institusi',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Create institution admin for an existing institution.
     */
    public function store(Request $request)
    {
        $this->ensureSuperAdmin($request);

        try {
            $validated = $request->validate([
                'institution_id' => 'required|integer|exists:institution,id',
                'name' => 'required|string|max:255',
                'email' => 'required|email|max:255|unique:user,email',
                'password' => [
                    'nullable',
                    'confirmed',
                    Password::min(8)->letters()->mixedCase()->numbers()->symbols(),
                ],
            ], [
                'institution_id.required' => 'Institusi wajib dipilih',
                'institution_id.exists' => 'Institusi tidak ditemukan',
                'name.required' => 'Nama admin wajib diisi',
                'email.required' => 'Email wajib diisi',
                'email.unique' => 'Email sudah terdaftar',
            ]);

            $plainPassword = $validated['password'] ?? $this->generatePassword();

            $user = User::create([
                'institution_id' => $validated['institution_id'],
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($plainPassword),
                'role' => 'institution_admin',
                'is_active' => true,
                'email_verified_at' => now(),
            ]);

            Log::info('Institution admin created by super admin', [
                'user_id' => $user->id,
                'institution_id' => $user->institution_id,
                'created_by' => $request->user()->id,
            ]);

            return response()->json([
                'message' => 'Admin institusi berhasil dibuat',
                'data' => new UserResource($user->load('institution:id,name,npsn,level,is_active')),
                'temporary_password' => empty($validated['password']) ? $plainPassword : null,
            ], 201);
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('Failed to create institution admin', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Terjadi kesalahan saat membuat admin institusi',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Reset password for an institution admin.
     */
    public function resetPassword(Request $request, $id)
    {
        $this->ensureSuperAdmin($request);

        try {
            $user = User::where('role', 'institution_admin')->findOrFail($id);

            $validated = $request->validate([
                'password' => [
                    'nullable',
                    'confirmed',
                    Password::min(8)->letters()->mixedCase()->numbers()->symbols(),
                ],
            ]);

            $plainPassword = $validated['password'] ?? $this->generatePassword();

            $user->update([
                'password' => Hash::make($plainPassword),
            ]);
            $user->resetFailedLoginAttempts();

            Log::info('Institution admin password reset by super admin', [
                'user_id' => $user->id,
                'reset_by' => $request->user()->id,
            ]);

            return response()->json([
                'message' => 'Sandi berhasil direset. Berikan sandi baru kepada admin secara aman.',
                'temporary_password' => $plainPassword,
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Admin institusi tidak ditemukan'], 404);
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('Failed to reset institution admin password', [
                'user_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Terjadi kesalahan saat reset sandi',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Activate / deactivate institution admin account.
     */
    public function updateStatus(Request $request, $id)
    {
        $this->ensureSuperAdmin($request);

        try {
            $user = User::where('role', 'institution_admin')->findOrFail($id);

            $validated = $request->validate([
                'is_active' => 'required|boolean',
            ]);

            $user->is_active = $validated['is_active'];
            $user->save();

            if ($user->is_active) {
                $user->resetFailedLoginAttempts();
            } else {
                $user->tokens()->delete();
            }

            $label = $user->is_active ? 'diaktifkan' : 'dinonaktifkan';

            Log::info('Institution admin status updated', [
                'user_id' => $user->id,
                'is_active' => $user->is_active,
                'updated_by' => $request->user()->id,
            ]);

            return response()->json([
                'message' => "Admin institusi berhasil {$label}",
                'data' => new UserResource($user->load('institution:id,name,npsn,level,is_active')),
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Admin institusi tidak ditemukan'], 404);
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('Failed to update institution admin status', [
                'user_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Terjadi kesalahan saat memperbarui status admin',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Onboarding: create institution + first admin in one transaction.
     */
    public function onboard(Request $request)
    {
        $this->ensureSuperAdmin($request);

        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'foundation_name' => 'nullable|string|max:255',
                'npsn' => 'nullable|string|size:8|regex:/^[0-9]{8}$/|unique:institution,npsn',
                'level' => 'nullable|in:TK,SD,SMP,SMA,SMK,MA,MAK,MTs,MI,PAUD',
                'type' => 'required|in:Negeri,Swasta',
                'phone' => 'nullable|string|max:20',
                'email' => 'nullable|email|max:255',
                'address' => 'nullable|string',
                'is_active' => 'sometimes|boolean',
                'admin_name' => 'required|string|max:255',
                'admin_email' => 'required|email|max:255|unique:user,email',
                'admin_password' => [
                    'nullable',
                    'confirmed',
                    Password::min(8)->letters()->mixedCase()->numbers()->symbols(),
                ],
            ], [
                'name.required' => 'Nama institusi wajib diisi',
                'type.required' => 'Jenis institusi wajib diisi',
                'admin_name.required' => 'Nama admin wajib diisi',
                'admin_email.required' => 'Email admin wajib diisi',
                'admin_email.unique' => 'Email admin sudah terdaftar',
                'npsn.unique' => 'NPSN sudah terdaftar',
            ]);

            $plainPassword = $validated['admin_password'] ?? $this->generatePassword();

            DB::beginTransaction();

            $institution = Institution::create([
                'name' => $validated['name'],
                'foundation_name' => $validated['foundation_name'] ?? null,
                'npsn' => $validated['npsn'] ?? null,
                'level' => $validated['level'] ?? null,
                'type' => $validated['type'],
                'phone' => $validated['phone'] ?? null,
                'email' => $validated['email'] ?? $validated['admin_email'],
                'address' => $validated['address'] ?? null,
                'is_active' => $validated['is_active'] ?? true,
            ]);

            $admin = User::create([
                'institution_id' => $institution->id,
                'name' => $validated['admin_name'],
                'email' => $validated['admin_email'],
                'password' => Hash::make($plainPassword),
                'role' => 'institution_admin',
                'is_active' => true,
                'email_verified_at' => now(),
            ]);

            DB::commit();

            Log::info('Institution onboarded by super admin', [
                'institution_id' => $institution->id,
                'admin_id' => $admin->id,
                'created_by' => $request->user()->id,
            ]);

            return response()->json([
                'message' => 'Institusi dan admin berhasil dibuat',
                'data' => [
                    'institution' => [
                        'id' => $institution->id,
                        'name' => $institution->name,
                        'npsn' => $institution->npsn,
                        'level' => $institution->level,
                        'type' => $institution->type,
                        'is_active' => (bool) $institution->is_active,
                    ],
                    'admin' => new UserResource($admin),
                ],
                'temporary_password' => empty($validated['admin_password']) ? $plainPassword : null,
                'checklist' => [
                    ['key' => 'institution', 'label' => 'Institusi dibuat', 'done' => true],
                    ['key' => 'admin', 'label' => 'Admin pertama dibuat', 'done' => true],
                    ['key' => 'academic_year', 'label' => 'Atur tahun ajaran aktif', 'done' => false, 'to' => '/academic-year'],
                    ['key' => 'profile', 'label' => 'Lengkapi profil institusi', 'done' => false, 'to' => '/institution'],
                ],
            ], 201);
        } catch (ValidationException $e) {
            DB::rollBack();
            throw $e;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to onboard institution', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Terjadi kesalahan saat onboarding institusi',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    private function generatePassword(): string
    {
        // Meets Password::min(8)->letters()->mixedCase()->numbers()->symbols()
        return 'Adm!' . Str::upper(Str::random(3)) . Str::lower(Str::random(3)) . random_int(10, 99);
    }
}
