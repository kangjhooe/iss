<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePpdbChannelRequest;
use App\Http\Requests\UpdatePpdbChannelRequest;
use App\Http\Resources\PpdbChannelResource;
use App\Models\PpdbChannel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Log;

class PpdbChannelController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection|JsonResponse
    {
        try {
            $user = $request->user();
            $institutionId = $user->institution_id;
            if (! $institutionId && ! $user->isSuperAdmin()) {
                return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
            }
            if ($user->isSuperAdmin() && $request->has('institution_id')) {
                $institutionId = $request->institution_id;
            }
            if (! $institutionId) {
                return response()->json(['message' => 'Pilih institusi.'], 403);
            }

            $activeOnly = filter_var($request->get('active_only', false), FILTER_VALIDATE_BOOLEAN);
            $channels = PpdbChannel::forInstitution($institutionId)
                ->when($activeOnly, fn ($q) => $q->active())
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get();

            return PpdbChannelResource::collection($channels);
        } catch (\Exception $e) {
            Log::error('PpdbChannel index failed', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Gagal mengambil data jalur PPDB.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    public function store(StorePpdbChannelRequest $request): JsonResponse
    {
        try {
            $user = $request->user();
            $institutionId = $user->institution_id;
            if (! $institutionId) {
                return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
            }

            $data = $request->validated();
            $data['institution_id'] = $institutionId;
            $data['is_active'] = $data['is_active'] ?? true;
            $data['sort_order'] = $data['sort_order'] ?? 0;
            $data['required_documents'] = \App\Support\PpdbDocumentChecklist::normalize($data['required_documents'] ?? []);

            // Unique code per institution
            if (PpdbChannel::where('institution_id', $institutionId)->where('code', $data['code'])->exists()) {
                return response()->json([
                    'message' => 'Kode jalur sudah digunakan di institusi ini.',
                ], 422);
            }

            $channel = PpdbChannel::create($data);

            return (new PpdbChannelResource($channel))
                ->response()
                ->setStatusCode(201);
        } catch (\Exception $e) {
            Log::error('PpdbChannel store failed', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Gagal menambahkan jalur PPDB.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    public function show(Request $request, PpdbChannel $ppdb_channel): PpdbChannelResource|JsonResponse
    {
        $user = $request->user();
        if ($user->institution_id !== $ppdb_channel->institution_id && ! $user->isSuperAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return new PpdbChannelResource($ppdb_channel);
    }

    public function update(UpdatePpdbChannelRequest $request, PpdbChannel $ppdb_channel): PpdbChannelResource|JsonResponse
    {
        try {
            $user = $request->user();
            if ($user->institution_id !== $ppdb_channel->institution_id && ! $user->isSuperAdmin()) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            $data = $request->validated();
            if (array_key_exists('required_documents', $data)) {
                $data['required_documents'] = \App\Support\PpdbDocumentChecklist::normalize($data['required_documents']);
            }
            if (isset($data['code']) && $data['code'] !== $ppdb_channel->code) {
                if (PpdbChannel::where('institution_id', $ppdb_channel->institution_id)->where('code', $data['code'])->exists()) {
                    return response()->json([
                        'message' => 'Kode jalur sudah digunakan di institusi ini.',
                    ], 422);
                }
            }

            $ppdb_channel->update($data);

            return new PpdbChannelResource($ppdb_channel->fresh());
        } catch (\Exception $e) {
            Log::error('PpdbChannel update failed', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Gagal memperbarui jalur PPDB.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    public function destroy(Request $request, PpdbChannel $ppdb_channel): JsonResponse
    {
        $user = $request->user();
        if ($user->institution_id !== $ppdb_channel->institution_id && ! $user->isSuperAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($ppdb_channel->applicants()->exists()) {
            return response()->json([
                'message' => 'Jalur tidak dapat dihapus karena sudah digunakan oleh calon peserta didik.',
            ], 422);
        }

        $ppdb_channel->delete();

        return response()->json(['message' => 'Jalur PPDB berhasil dihapus.']);
    }
}
