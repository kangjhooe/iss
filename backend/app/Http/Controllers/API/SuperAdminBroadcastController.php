<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\User;
use App\Notifications\BroadcastAnnouncementNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

class SuperAdminBroadcastController extends Controller
{
    private function ensureSuperAdmin(Request $request): void
    {
        if (!$request->user()?->isSuperAdmin()) {
            abort(403, 'Unauthorized');
        }
    }

    public function index(Request $request)
    {
        $this->ensureSuperAdmin($request);

        try {
            $perPage = min((int) $request->get('per_page', 15), 50);
            $items = Announcement::with('creator:id,name,email')
                ->orderByDesc('created_at')
                ->paginate($perPage);

            return response()->json([
                'data' => $items->getCollection()->map(fn ($a) => $this->transform($a)),
                'meta' => [
                    'current_page' => $items->currentPage(),
                    'last_page' => $items->lastPage(),
                    'per_page' => $items->perPage(),
                    'total' => $items->total(),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to list announcements', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Terjadi kesalahan saat mengambil pengumuman',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    public function store(Request $request)
    {
        $this->ensureSuperAdmin($request);

        try {
            $validated = $request->validate([
                'title' => 'required|string|max:200',
                'body' => 'required|string|max:5000',
                'target' => 'required|in:all_admins,selected_institutions',
                'institution_ids' => 'required_if:target,selected_institutions|array',
                'institution_ids.*' => 'integer|exists:institution,id',
            ], [
                'title.required' => 'Judul wajib diisi',
                'body.required' => 'Isi pengumuman wajib diisi',
                'institution_ids.required_if' => 'Pilih minimal satu institusi',
            ]);

            $query = User::query()
                ->where('role', 'institution_admin')
                ->where(function ($q) {
                    $q->where('is_active', true)->orWhereNull('is_active');
                });

            if ($validated['target'] === 'selected_institutions') {
                $ids = $validated['institution_ids'] ?? [];
                if (empty($ids)) {
                    throw ValidationException::withMessages([
                        'institution_ids' => ['Pilih minimal satu institusi'],
                    ]);
                }
                $query->whereIn('institution_id', $ids);
            }

            $recipients = $query->get();

            if ($recipients->isEmpty()) {
                return response()->json([
                    'message' => 'Tidak ada admin institusi yang cocok sebagai penerima',
                ], 422);
            }

            DB::beginTransaction();

            $announcement = Announcement::create([
                'title' => $validated['title'],
                'body' => $validated['body'],
                'target' => $validated['target'],
                'institution_ids' => $validated['target'] === 'selected_institutions'
                    ? array_values($validated['institution_ids'])
                    : null,
                'created_by' => $request->user()->id,
                'recipients_count' => $recipients->count(),
                'published_at' => now(),
            ]);

            Notification::send($recipients, new BroadcastAnnouncementNotification($announcement));

            DB::commit();

            Log::info('Broadcast announcement sent', [
                'announcement_id' => $announcement->id,
                'recipients' => $recipients->count(),
                'created_by' => $request->user()->id,
            ]);

            return response()->json([
                'message' => "Pengumuman terkirim ke {$recipients->count()} admin institusi",
                'data' => $this->transform($announcement->load('creator:id,name,email')),
            ], 201);
        } catch (ValidationException $e) {
            DB::rollBack();
            throw $e;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to send broadcast', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Terjadi kesalahan saat mengirim pengumuman',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    public function show(Request $request, $id)
    {
        $this->ensureSuperAdmin($request);

        $announcement = Announcement::with('creator:id,name,email')->findOrFail($id);

        return response()->json([
            'data' => $this->transform($announcement),
        ]);
    }

    private function transform(Announcement $a): array
    {
        return [
            'id' => $a->id,
            'title' => $a->title,
            'body' => $a->body,
            'target' => $a->target,
            'institution_ids' => $a->institution_ids,
            'recipients_count' => $a->recipients_count,
            'published_at' => $a->published_at?->toIso8601String(),
            'created_at' => $a->created_at?->toIso8601String(),
            'creator' => $a->creator ? [
                'id' => $a->creator->id,
                'name' => $a->creator->name,
                'email' => $a->creator->email,
            ] : null,
        ];
    }
}
