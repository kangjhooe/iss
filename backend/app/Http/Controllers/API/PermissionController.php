<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class PermissionController extends Controller
{
    /**
     * List available permissions/modules.
     */
    public function index(Request $request)
    {
        $permissions = Permission::query()
            ->orderBy('label')
            ->get(['key', 'label']);

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

            if (!$user->isAdminOrSuperAdmin() && !$user->isInstitutionAdmin()) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            $institutionId = $user->isAdminOrSuperAdmin()
                ? $request->get('institution_id')
                : $user->institution_id;

            $employeesQuery = Employee::where('type', 'Guru')
                ->whereHas('userAccount')
                ->with(['userAccount.permissions']);

            if ($institutionId !== null) {
                $employeesQuery->where('institution_id', $institutionId);
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
            
            if (!$user->isAdminOrSuperAdmin() && !$user->isInstitutionAdmin()) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            $targetUser = User::findOrFail($userId);

            $isEmployeeRole = in_array($targetUser->role, ['teacher', 'staff'], true);
            $isEmployeeByEmail = Employee::where('email', $targetUser->email)
                ->where('institution_id', $targetUser->institution_id)
                ->exists();

            if (!$isEmployeeRole && !$isEmployeeByEmail) {
                return response()->json([
                    'message' => 'Hanya dapat mengatur permissions untuk akun pegawai (guru/staff)',
                ], 422);
            }

            if (!$user->isAdminOrSuperAdmin() && $targetUser->institution_id !== $user->institution_id) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            $permissionKeys = $request->input('permission_keys', []);
            $permissionIds = Permission::whereIn('key', $permissionKeys)->pluck('id')->all();
            $oldKeys = $targetUser->permissions()->pluck('key')->toArray();
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
}
