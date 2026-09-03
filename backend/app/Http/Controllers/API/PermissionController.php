<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\Permission;
use App\Models\User;
use App\Support\InstitutionContext;
use App\Support\InstitutionModuleVisibility;
use App\Support\ReportAccess;
use App\Support\VocationalAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PermissionController extends Controller
{
    /**
     * List available permissions/modules.
     */
    public function index(Request $request)
    {
        $query = Permission::query()->orderBy('label');

        $institutionId = InstitutionContext::resolveForUser(
            $request->user(),
            $request,
            $request->get('institution_id')
        );
        if ($institutionId && ! VocationalAccess::isVocationalInstitution($institutionId)) {
            $query->whereNotIn('key', VocationalAccess::PERMISSION_KEYS);
        }

        $permissions = $query->get(['key', 'label']);

        return response()->json([
            'data' => $permissions,
        ]);
    }

    /**
     * Get teachers with their permissions.
     * Sources from Employee (type Guru) that have a User account (by email match).
     */
    public function getTeachers(Request $request)
    {
        try {
            $user = $request->user();

            if (! $user->isAdminOrSuperAdmin() && ! $user->isInstitutionAdmin()) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            $institutionId = InstitutionContext::resolveForUser(
                $user,
                $request,
                $request->get('institution_id')
            );

            $employeesQuery = Employee::where('type', 'Guru')
                ->whereHas('userAccount')
                ->with(['userAccount.permissions']);

            if ($institutionId !== null) {
                $employeesQuery->where('institution_id', $institutionId);
            } elseif (! $user->isAdminOrSuperAdmin()) {
                return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
            }

            $teachers = $employeesQuery->get()
                ->map(function (Employee $employee) {
                    $account = $employee->userAccount;

                    return [
                        'id' => $account->id,
                        'name' => $account->name,
                        'email' => $account->email,
                        'teacher_profile' => [
                            'id' => $employee->id,
                            'nip' => $employee->nip,
                            'nuptk' => $employee->nuptk,
                        ],
                        'permissions' => $account->permissions->pluck('key')->toArray(),
                    ];
                })
                ->values()
                ->all();

            return response()->json([
                'data' => $teachers,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to get teachers with permissions', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Terjadi kesalahan saat mengambil data guru',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Update user permissions.
     */
    public function updateUserPermissions(Request $request, $userId)
    {
        try {
            $request->validate([
                'permission_keys' => 'required|array',
                'permission_keys.*' => 'string|exists:permissions,key',
            ]);

            $user = $request->user();

            if (! $user->isAdminOrSuperAdmin() && ! $user->isInstitutionAdmin()) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            $targetUser = User::findOrFail($userId);

            $isEmployeeRole = in_array($targetUser->role, ['teacher', 'staff'], true);
            $isEmployeeByEmail = Employee::where('email', $targetUser->email)
                ->where('institution_id', $targetUser->institution_id)
                ->exists();

            if (! $isEmployeeRole && ! $isEmployeeByEmail) {
                return response()->json([
                    'message' => 'Hanya dapat mengatur permissions untuk akun pegawai (guru/staff)',
                ], 422);
            }

            if (! $user->isAdminOrSuperAdmin()
                && ! \App\Support\InstitutionContext::canAccessInstitution($user, (int) $targetUser->institution_id)) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            $permissionKeys = VocationalAccess::filterPermissionKeysForInstitution(
                $targetUser->institution_id,
                $request->input('permission_keys', [])
            );
            $oldKeys = $targetUser->permissions()->pluck('key')->toArray();
            $hiddenKeys = InstitutionModuleVisibility::hiddenKeys($targetUser->institution_id);
            $visibleRequested = array_values(array_filter(
                $permissionKeys,
                fn (string $key) => ! in_array($key, $hiddenKeys, true)
            ));
            $keptHidden = array_values(array_intersect($oldKeys, $hiddenKeys));
            $permissionKeys = array_values(array_unique(array_merge($visibleRequested, $keptHidden)));
            $permissionKeys = ReportAccess::sanitizeKeysForUser($targetUser, $permissionKeys);
            $permissionIds = Permission::whereIn('key', $permissionKeys)->pluck('id')->all();
            $targetUser->permissions()->sync($permissionIds);

            \App\Models\AuditLog::logManual(
                $request,
                'module_access.updated',
                \App\Models\User::class,
                $targetUser->id,
                ['permission_keys' => $oldKeys],
                ['permission_keys' => $permissionKeys],
                $targetUser->institution_id
            );

            Log::info('User permissions updated', [
                'user_id' => $targetUser->id,
                'updated_by' => $user->id,
                'permissions' => $permissionKeys,
            ]);

            return response()->json([
                'message' => 'Akses modul berhasil diperbarui',
                'data' => [
                    'id' => $targetUser->id,
                    'name' => $targetUser->name,
                    'email' => $targetUser->email,
                    'permissions' => $targetUser->permissions()->pluck('key')->toArray(),
                ],
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'User tidak ditemukan',
            ], 404);
        } catch (\Exception $e) {
            Log::error('Failed to update user permissions', [
                'error' => $e->getMessage(),
                'user_id' => $userId,
            ]);

            return response()->json([
                'message' => 'Terjadi kesalahan saat memperbarui akses modul',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Modul yang disembunyikan untuk institusi aktif.
     */
    public function getInstitutionVisibility(Request $request)
    {
        $user = $request->user();
        if (! $user->isAdminOrSuperAdmin() && ! $user->isInstitutionAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $institutionId = $this->resolveManagedInstitutionId($request, $user);
        if (! $institutionId) {
            return response()->json(['message' => 'Institusi tidak ditemukan'], 422);
        }

        return response()->json([
            'data' => [
                'hidden_keys' => InstitutionModuleVisibility::hiddenKeys($institutionId),
            ],
        ]);
    }

    /**
     * Simpan modul yang disembunyikan admin sekolah.
     */
    public function updateInstitutionVisibility(Request $request)
    {
        $user = $request->user();
        if (! $user->isAdminOrSuperAdmin() && ! $user->isInstitutionAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'hidden_keys' => 'present|array',
            'hidden_keys.*' => 'string|exists:permissions,key',
        ]);

        $institutionId = $this->resolveManagedInstitutionId($request, $user);
        if (! $institutionId) {
            return response()->json(['message' => 'Institusi tidak ditemukan'], 422);
        }

        $institution = \App\Models\Institution::query()->findOrFail($institutionId);
        $oldKeys = InstitutionModuleVisibility::normalize($institution->hidden_module_keys);
        $hiddenKeys = InstitutionModuleVisibility::sanitizeHiddenKeys(
            $institutionId,
            $request->input('hidden_keys', [])
        );

        $institution->update(['hidden_module_keys' => $hiddenKeys === [] ? null : $hiddenKeys]);
        $request->attributes->remove('hidden_module_keys_'.$institutionId);

        \App\Models\AuditLog::logManual(
            $request,
            'institution_modules.updated',
            \App\Models\Institution::class,
            $institution->id,
            ['hidden_keys' => $oldKeys],
            ['hidden_keys' => $hiddenKeys],
            $institution->id
        );

        return response()->json([
            'message' => 'Modul sekolah berhasil diperbarui',
            'data' => [
                'hidden_keys' => $hiddenKeys,
            ],
        ]);
    }

    private function resolveManagedInstitutionId(Request $request, User $user): ?int
    {
        return InstitutionContext::resolveForUser(
            $user,
            $request,
            $request->get('institution_id')
        );
    }
}
