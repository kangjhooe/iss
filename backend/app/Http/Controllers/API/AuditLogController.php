<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AuditLogController extends Controller
{
    /**
     * Only institution/platform admins may read audit logs.
     */
    private function authorizeAuditAccess(Request $request): ?JsonResponse
    {
        $user = $request->user();
        if (
            !$user
            || (
                !$user->isSuperAdmin()
                && !$user->isAdmin()
                && !$user->isInstitutionAdmin()
            )
        ) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return null;
    }

    /**
     * Base query scope: institution for admin, all for super_admin.
     */
    private function baseQuery(Request $request)
    {
        $user = $request->user();
        $query = AuditLog::with('user:id,name')->orderBy('created_at', 'desc');
        if (! $user->isSuperAdmin() && $user->institution_id) {
            $query->where('institution_id', $user->institution_id);
        }
        return $query;
    }

    /**
     * Apply filters from request to query.
     */
    private function applyFilters($query, Request $request): void
    {
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->get('user_id'));
        }
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->get('date_from'));
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->get('date_to'));
        }
        if ($request->filled('module')) {
            $module = $request->get('module');
            $query->where('auditable_type', 'like', '%' . $module);
        }
        if ($request->filled('action')) {
            $query->where('action', $request->get('action'));
        }
    }

    /**
     * List audit logs with filters and pagination.
     */
    public function index(Request $request)
    {
        if ($denied = $this->authorizeAuditAccess($request)) {
            return $denied;
        }

        $perPage = min((int) $request->get('per_page', 15), 100);
        $query = $this->baseQuery($request);
        $this->applyFilters($query, $request);

        $logs = $query->paginate($perPage);
        $items = $logs->map(function ($log) {
            return [
                'id' => $log->id,
                'action' => $log->action,
                'auditable_type' => $log->auditable_type,
                'module' => class_basename($log->auditable_type),
                'auditable_id' => $log->auditable_id,
                'user_id' => $log->user_id,
                'user_name' => $log->user?->name,
                'created_at' => $log->created_at->toIso8601String(),
                'description' => $this->actionDescription($log),
            ];
        });

        return response()->json([
            'data' => $items,
            'meta' => [
                'current_page' => $logs->currentPage(),
                'last_page' => $logs->lastPage(),
                'per_page' => $logs->perPage(),
                'total' => $logs->total(),
            ],
        ]);
    }

    /**
     * Options for filter dropdowns: users, modules, actions (distinct from audit_logs).
     */
    public function filterOptions(Request $request)
    {
        if ($denied = $this->authorizeAuditAccess($request)) {
            return $denied;
        }

        $query = $this->baseQuery($request)->select('user_id', 'auditable_type', 'action');

        $users = (clone $query)
            ->whereNotNull('user_id')
            ->distinct()
            ->pluck('user_id')
            ->filter()
            ->values();
        $userList = User::whereIn('id', $users)->get(['id', 'name'])->map(fn ($u) => ['id' => $u->id, 'name' => $u->name]);

        $modules = (clone $query)->distinct()->pluck('auditable_type')->map(fn ($t) => class_basename($t))->unique()->sort()->values();
        $actions = (clone $query)->distinct()->pluck('action')->unique()->sort()->values();

        return response()->json([
            'users' => $userList,
            'modules' => $modules->values()->all(),
            'actions' => $actions->values()->all(),
        ]);
    }

    /**
     * Export audit logs as CSV (same filters as index).
     */
    public function export(Request $request): StreamedResponse|JsonResponse
    {
        if ($denied = $this->authorizeAuditAccess($request)) {
            return $denied;
        }

        $query = $this->baseQuery($request);
        $this->applyFilters($query, $request);
        $query->with('user:id,name');
        $logs = $query->limit(10000)->get();

        $filename = 'audit-log-' . now()->format('Y-m-d-His') . '.csv';

        return response()->streamDownload(function () use ($logs) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Tanggal', 'User', 'Modul', 'Aksi', 'Deskripsi', 'ID Entitas']);
            foreach ($logs as $log) {
                fputcsv($out, [
                    $log->created_at->format('Y-m-d H:i:s'),
                    $log->user?->name ?? '-',
                    class_basename($log->auditable_type),
                    $log->action,
                    $this->actionDescription($log),
                    $log->auditable_id,
                ]);
            }
            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    private function actionDescription(AuditLog $log): string
    {
        $action = $log->action;
        $type = class_basename($log->auditable_type);
        $maps = [
            'created' => "{$type} ditambahkan",
            'updated' => "{$type} diperbarui",
            'deleted' => "{$type} dihapus",
            'module_access.updated' => 'Akses modul diperbarui',
        ];

        return $maps[$action] ?? $action;
    }
}
