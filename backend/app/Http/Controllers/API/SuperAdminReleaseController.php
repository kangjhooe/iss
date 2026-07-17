<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAppReleaseRequest;
use App\Http\Requests\UpdateAppReleaseRequest;
use App\Models\AppRelease;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SuperAdminReleaseController extends Controller
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
            $perPage = min((int) $request->get('per_page', 30), 100);
            $status = $request->get('status'); // published | draft | all

            $query = AppRelease::with('creator:id,name,email')
                ->orderByDesc('released_at')
                ->orderByDesc('id');

            if ($status === 'published') {
                $query->published();
            } elseif ($status === 'draft') {
                $query->whereNull('published_at');
            }

            $items = $query->paginate($perPage);

            return response()->json([
                'data' => $items->getCollection()->map(fn (AppRelease $r) => $this->transform($r)),
                'meta' => [
                    'current_page' => $items->currentPage(),
                    'last_page' => $items->lastPage(),
                    'per_page' => $items->perPage(),
                    'total' => $items->total(),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to list app releases', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Terjadi kesalahan saat mengambil catatan rilis',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    public function store(StoreAppReleaseRequest $request)
    {
        $this->ensureSuperAdmin($request);

        try {
            $validated = $request->validated();
            $isPublished = (bool) ($validated['is_published'] ?? false);

            $release = AppRelease::create([
                'title' => $validated['title'],
                'version' => $validated['version'] ?? null,
                'released_at' => $validated['released_at'],
                'items' => array_values(array_map('trim', $validated['items'])),
                'published_at' => $isPublished ? now() : null,
                'created_by' => $request->user()->id,
            ]);

            AuditLog::logManual(
                $request,
                'app_release.created',
                AppRelease::class,
                $release->id,
                null,
                $this->transform($release)
            );

            return response()->json([
                'message' => $isPublished ? 'Catatan rilis dipublikasikan' : 'Catatan rilis disimpan sebagai draft',
                'data' => $this->transform($release->load('creator:id,name,email')),
            ], 201);
        } catch (\Exception $e) {
            Log::error('Failed to create app release', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Terjadi kesalahan saat menyimpan catatan rilis',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    public function show(Request $request, int $id)
    {
        $this->ensureSuperAdmin($request);

        $release = AppRelease::with('creator:id,name,email')->findOrFail($id);

        return response()->json([
            'data' => $this->transform($release),
        ]);
    }

    public function update(UpdateAppReleaseRequest $request, int $id)
    {
        $this->ensureSuperAdmin($request);

        try {
            $release = AppRelease::findOrFail($id);
            $old = $this->transform($release);
            $validated = $request->validated();

            $data = [];
            if (array_key_exists('title', $validated)) {
                $data['title'] = $validated['title'];
            }
            if (array_key_exists('version', $validated)) {
                $data['version'] = $validated['version'];
            }
            if (array_key_exists('released_at', $validated)) {
                $data['released_at'] = $validated['released_at'];
            }
            if (array_key_exists('items', $validated)) {
                $data['items'] = array_values(array_map('trim', $validated['items']));
            }
            if (array_key_exists('is_published', $validated)) {
                $wantPublished = (bool) $validated['is_published'];
                if ($wantPublished && !$release->isPublished()) {
                    $data['published_at'] = now();
                } elseif (!$wantPublished) {
                    $data['published_at'] = null;
                }
            }

            $release->update($data);

            AuditLog::logManual(
                $request,
                'app_release.updated',
                AppRelease::class,
                $release->id,
                $old,
                $this->transform($release->fresh())
            );

            return response()->json([
                'message' => 'Catatan rilis diperbarui',
                'data' => $this->transform($release->fresh()->load('creator:id,name,email')),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to update app release', ['error' => $e->getMessage(), 'id' => $id]);

            return response()->json([
                'message' => 'Terjadi kesalahan saat memperbarui catatan rilis',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    public function destroy(Request $request, int $id)
    {
        $this->ensureSuperAdmin($request);

        try {
            $release = AppRelease::findOrFail($id);
            $old = $this->transform($release);
            $release->delete();

            AuditLog::logManual(
                $request,
                'app_release.deleted',
                AppRelease::class,
                $id,
                $old,
                null
            );

            return response()->json([
                'message' => 'Catatan rilis dihapus',
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to delete app release', ['error' => $e->getMessage(), 'id' => $id]);

            return response()->json([
                'message' => 'Terjadi kesalahan saat menghapus catatan rilis',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    private function transform(AppRelease $r): array
    {
        return [
            'id' => $r->id,
            'title' => $r->title,
            'version' => $r->version,
            'released_at' => $r->released_at?->format('Y-m-d'),
            'items' => $r->items ?? [],
            'is_published' => $r->isPublished(),
            'published_at' => $r->published_at?->toIso8601String(),
            'created_by' => $r->created_by,
            'creator' => $r->relationLoaded('creator') && $r->creator
                ? [
                    'id' => $r->creator->id,
                    'name' => $r->creator->name,
                    'email' => $r->creator->email,
                ]
                : null,
            'created_at' => $r->created_at?->toIso8601String(),
            'updated_at' => $r->updated_at?->toIso8601String(),
        ];
    }
}
