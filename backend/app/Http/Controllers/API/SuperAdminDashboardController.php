<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Employee;
use App\Models\FeedbackTicket;
use App\Models\Institution;
use App\Models\InstitutionChangeRequest;
use App\Models\PasswordResetRequest;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SuperAdminDashboardController extends Controller
{
    /**
     * Aggregated dashboard data for super admin.
     */
    public function index(Request $request)
    {
        try {
            if (! $request->user()?->isSuperAdmin()) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            $institutionCount = Institution::count();
            $activeInstitutionCount = Institution::where('is_active', true)->count();
            $inactiveInstitutionCount = Institution::where('is_active', false)->count();
            $studentCount = Student::where('status', 'Aktif')->count();
            $teacherCount = Employee::where('type', 'Guru')->where('status', 'Aktif')->count();
            $pendingRequestsCount = InstitutionChangeRequest::where('status', 'pending')->count();
            $pendingPasswordResetCount = 0;
            $pendingPasswordResets = collect();
            $openFeedbackCount = 0;
            $openFeedbackTickets = collect();

            try {
                $openFeedbackCount = FeedbackTicket::open()->count();
                $openFeedbackTickets = FeedbackTicket::with([
                    'institution:id,name,npsn',
                    'submitter:id,name,email',
                ])
                    ->open()
                    ->orderByDesc('created_at')
                    ->limit(5)
                    ->get()
                    ->map(fn ($t) => [
                        'id' => $t->id,
                        'type' => $t->type,
                        'title' => $t->title,
                        'priority' => $t->priority,
                        'status' => $t->status,
                        'created_at' => $t->created_at?->toIso8601String(),
                        'institution' => $t->institution ? [
                            'id' => $t->institution->id,
                            'name' => $t->institution->name,
                            'npsn' => $t->institution->npsn,
                        ] : null,
                        'submitter' => $t->submitter ? [
                            'id' => $t->submitter->id,
                            'name' => $t->submitter->name,
                        ] : null,
                    ]);
            } catch (\Exception $feedbackError) {
                Log::warning('Failed to load feedback tickets for dashboard', [
                    'error' => $feedbackError->getMessage(),
                ]);
            }

            try {
                $pendingPasswordResetCount = PasswordResetRequest::pending()->count();
                $pendingPasswordResets = PasswordResetRequest::with([
                    'institution:id,name,npsn',
                    'user:id,name,email',
                ])
                    ->pending()
                    ->orderByDesc('created_at')
                    ->limit(5)
                    ->get()
                    ->map(fn ($r) => [
                        'id' => $r->id,
                        'email' => $r->email,
                        'npsn' => $r->npsn,
                        'created_at' => $r->created_at?->toIso8601String(),
                        'institution' => $r->institution ? [
                            'id' => $r->institution->id,
                            'name' => $r->institution->name,
                            'npsn' => $r->institution->npsn,
                        ] : null,
                        'user' => $r->user ? [
                            'id' => $r->user->id,
                            'name' => $r->user->name,
                            'email' => $r->user->email,
                        ] : null,
                    ]);
            } catch (\Exception $resetError) {
                Log::warning('Failed to load password reset requests for dashboard', [
                    'error' => $resetError->getMessage(),
                ]);
            }

            $pendingRequests = InstitutionChangeRequest::with([
                'institution:id,name,npsn',
                'requester:id,name,email',
            ])
                ->where('status', 'pending')
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get()
                ->map(fn ($r) => [
                    'id' => $r->id,
                    'field_name' => $r->field_name,
                    'old_value' => $r->old_value,
                    'new_value' => $r->new_value,
                    'created_at' => $r->created_at?->toIso8601String(),
                    'institution' => $r->institution ? [
                        'id' => $r->institution->id,
                        'name' => $r->institution->name,
                        'npsn' => $r->institution->npsn,
                    ] : null,
                    'requester' => $r->requester ? [
                        'id' => $r->requester->id,
                        'name' => $r->requester->name,
                    ] : null,
                ]);

            $recentInstitutions = Institution::select(['id', 'name', 'npsn', 'level', 'type', 'is_active', 'created_at'])
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get()
                ->map(fn ($i) => [
                    'id' => $i->id,
                    'name' => $i->name,
                    'npsn' => $i->npsn,
                    'level' => $i->level,
                    'type' => $i->type,
                    'is_active' => (bool) $i->is_active,
                    'created_at' => $i->created_at?->toIso8601String(),
                ]);

            $inactiveInstitutions = Institution::select(['id', 'name', 'npsn', 'level', 'type', 'is_active', 'updated_at'])
                ->where('is_active', false)
                ->orderBy('updated_at', 'desc')
                ->limit(5)
                ->get()
                ->map(fn ($i) => [
                    'id' => $i->id,
                    'name' => $i->name,
                    'npsn' => $i->npsn,
                    'level' => $i->level,
                    'type' => $i->type,
                    'is_active' => false,
                    'updated_at' => $i->updated_at?->toIso8601String(),
                ]);

            $recentAuditLogs = AuditLog::with('user:id,name')
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get()
                ->map(fn ($log) => [
                    'id' => $log->id,
                    'action' => $log->action,
                    'module' => class_basename($log->auditable_type),
                    'user_name' => $log->user?->name,
                    'created_at' => $log->created_at?->toIso8601String(),
                    'description' => $this->auditDescription($log),
                ]);

            return response()->json([
                'data' => [
                    'counts' => [
                        'institutions' => $institutionCount,
                        'active_institutions' => $activeInstitutionCount,
                        'inactive_institutions' => $inactiveInstitutionCount,
                        'students' => $studentCount,
                        'teachers' => $teacherCount,
                        'pending_requests' => $pendingRequestsCount,
                        'pending_password_resets' => $pendingPasswordResetCount,
                        'open_feedback' => $openFeedbackCount,
                    ],
                    'pending_requests' => $pendingRequests,
                    'pending_password_resets' => $pendingPasswordResets,
                    'open_feedback_tickets' => $openFeedbackTickets,
                    'recent_institutions' => $recentInstitutions,
                    'inactive_institutions' => $inactiveInstitutions,
                    'recent_audit_logs' => $recentAuditLogs,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to load super admin dashboard', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Terjadi kesalahan saat memuat dashboard',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    private function auditDescription(AuditLog $log): string
    {
        $module = class_basename($log->auditable_type);
        $action = $log->action;

        return match ($action) {
            'created' => "{$module} dibuat",
            'updated' => "{$module} diperbarui",
            'deleted' => "{$module} dihapus",
            default => "{$module}: {$action}",
        };
    }
}
