<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreFeedbackTicketRequest;
use App\Http\Requests\UpdateFeedbackTicketRequest;
use App\Models\FeedbackTicket;
use App\Models\User;
use App\Notifications\FeedbackTicketNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class FeedbackTicketController extends Controller
{
    /**
     * List feedback tickets.
     * Super admin: all tickets. School admin: own institution tickets.
     */
    public function index(Request $request)
    {
        try {
            $user = $request->user();

            if (! $this->canAccess($user)) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            $query = FeedbackTicket::with([
                'institution:id,name,npsn',
                'submitter:id,name,email',
                'handler:id,name,email',
            ])->orderByDesc('created_at');

            if ($user->isSuperAdmin()) {
                if ($request->filled('institution_id')) {
                    $query->where('institution_id', (int) $request->institution_id);
                }
            } else {
                $query->where('institution_id', $user->institution_id);
            }

            if ($request->filled('status')) {
                if ($request->status === 'open') {
                    $query->open();
                } else {
                    $query->where('status', $request->status);
                }
            }

            if ($request->filled('type')) {
                $query->where('type', $request->type);
            }

            if ($request->filled('priority')) {
                $query->where('priority', $request->priority);
            }

            if ($request->filled('search')) {
                $search = trim((string) $request->search);
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            }

            $perPage = min((int) $request->get('per_page', 15), 100);
            $tickets = $query->paginate($perPage);

            return response()->json($tickets);
        } catch (\Exception $e) {
            Log::error('Failed to list feedback tickets', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Terjadi kesalahan saat mengambil data tiket',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Store a new feedback ticket (school admin).
     */
    public function store(StoreFeedbackTicketRequest $request)
    {
        try {
            $user = $request->user();
            $institution = $user->institution;

            if (! $institution) {
                return response()->json([
                    'message' => 'Institusi tidak ditemukan',
                ], 404);
            }

            DB::beginTransaction();

            $ticket = FeedbackTicket::create([
                'institution_id' => $institution->id,
                'submitted_by' => $user->id,
                'type' => $request->type,
                'title' => $request->title,
                'description' => $request->description,
                'module' => $request->module,
                'priority' => $request->input('priority', 'medium'),
                'status' => FeedbackTicket::STATUS_OPEN,
            ]);

            DB::commit();

            $ticket->load(['institution:id,name,npsn', 'submitter:id,name,email']);

            $superAdmins = User::where('role', 'super_admin')
                ->where(function ($q) {
                    $q->whereNull('is_active')->orWhere('is_active', true);
                })
                ->get();

            if ($superAdmins->isNotEmpty()) {
                Notification::send(
                    $superAdmins,
                    new FeedbackTicketNotification($ticket, 'submitted')
                );
            }

            Log::info('Feedback ticket created', [
                'ticket_id' => $ticket->id,
                'institution_id' => $institution->id,
                'user_id' => $user->id,
                'type' => $ticket->type,
            ]);

            return response()->json([
                'message' => 'Laporan berhasil dikirim',
                'data' => $ticket,
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create feedback ticket', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Terjadi kesalahan saat mengirim laporan',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Show a single feedback ticket.
     */
    public function show(Request $request, $id)
    {
        try {
            $user = $request->user();

            if (! $this->canAccess($user)) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            $ticket = FeedbackTicket::with([
                'institution:id,name,npsn',
                'submitter:id,name,email',
                'handler:id,name,email',
            ])->findOrFail($id);

            if (! $user->isSuperAdmin() && $ticket->institution_id !== $user->institution_id) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            return response()->json(['data' => $ticket]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Tiket tidak ditemukan',
            ], 404);
        } catch (\Exception $e) {
            Log::error('Failed to get feedback ticket', [
                'ticket_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Terjadi kesalahan saat mengambil data tiket',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Update ticket status/note (super admin).
     */
    public function update(UpdateFeedbackTicketRequest $request, $id)
    {
        try {
            $ticket = FeedbackTicket::with([
                'institution:id,name,npsn',
                'submitter:id,name,email',
            ])->findOrFail($id);

            $oldStatus = $ticket->status;
            $oldNote = $ticket->admin_note;

            DB::beginTransaction();

            $ticket->status = $request->status;

            if ($request->has('admin_note')) {
                $ticket->admin_note = $request->admin_note;
            }

            if ($request->filled('priority')) {
                $ticket->priority = $request->priority;
            }

            $ticket->handled_by = $request->user()->id;
            $ticket->handled_at = now();
            $ticket->save();

            DB::commit();

            $ticket->load(['handler:id,name,email']);

            $shouldNotify = $ticket->submitter
                && ($oldStatus !== $ticket->status || $oldNote !== $ticket->admin_note);

            if ($shouldNotify) {
                $ticket->submitter->notify(
                    new FeedbackTicketNotification($ticket, 'updated')
                );
            }

            Log::info('Feedback ticket updated', [
                'ticket_id' => $ticket->id,
                'old_status' => $oldStatus,
                'new_status' => $ticket->status,
                'handled_by' => $request->user()->id,
            ]);

            return response()->json([
                'message' => 'Tiket berhasil diperbarui',
                'data' => $ticket,
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Tiket tidak ditemukan',
            ], 404);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update feedback ticket', [
                'ticket_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Terjadi kesalahan saat memperbarui tiket',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Open tickets count (for super admin badge/dashboard).
     */
    public function openCount(Request $request)
    {
        try {
            if (! $request->user()?->isSuperAdmin()) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            $count = FeedbackTicket::open()->count();

            return response()->json(['count' => $count]);
        } catch (\Exception $e) {
            Log::error('Failed to get open feedback count', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Terjadi kesalahan',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    private function canAccess(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        return $user->isSuperAdmin()
            || $user->isInstitutionAdmin()
            || $user->isAdmin();
    }
}
