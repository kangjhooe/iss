<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDispositionRequest;
use App\Http\Requests\UpdateDispositionRequest;
use App\Http\Resources\DispositionResource;
use App\Models\Correspondence;
use App\Models\CorrespondenceDisposition;
use App\Services\DispositionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DispositionController extends Controller
{
    public function __construct(
        private DispositionService $service
    ) {}

    /**
     * Get dispositions for a correspondence.
     */
    public function index(Request $request, int $correspondenceId)
    {
        try {
            $correspondence = Correspondence::findOrFail($correspondenceId);

            // Check authorization
            if (!$request->user()->isAdminOrSuperAdmin() && 
                $correspondence->institution_id !== $request->user()->institution_id) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            $dispositions = $this->service->getByCorrespondence($correspondenceId);

            return DispositionResource::collection($dispositions);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Surat tidak ditemukan'], 404);
        } catch (\Exception $e) {
            Log::error('Failed to list dispositions', [
                'error' => $e->getMessage(),
                'correspondence_id' => $correspondenceId,
            ]);

            return response()->json([
                'message' => 'Terjadi kesalahan saat mengambil data disposisi',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Get pending dispositions for current user.
     */
    public function pending(Request $request)
    {
        try {
            $dispositions = $this->service->getPendingForUser($request->user()->id);

            return DispositionResource::collection($dispositions);
        } catch (\Exception $e) {
            Log::error('Failed to get pending dispositions', [
                'error' => $e->getMessage(),
                'user_id' => $request->user()->id,
            ]);

            return response()->json([
                'message' => 'Terjadi kesalahan saat mengambil data disposisi',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Create a new disposition.
     */
    public function store(StoreDispositionRequest $request, int $correspondenceId)
    {
        try {
            $correspondence = Correspondence::findOrFail($correspondenceId);

            // Check authorization
            if (!$request->user()->isAdminOrSuperAdmin() && 
                $correspondence->institution_id !== $request->user()->institution_id) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            $data = $request->validated();
            $disposition = $this->service->create($correspondence, $data, $request->user()->id);

            return response()->json([
                'message' => 'Disposisi berhasil dibuat',
                'data' => new DispositionResource($disposition),
            ], 201);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Surat tidak ditemukan'], 404);
        } catch (\Exception $e) {
            Log::error('Failed to create disposition', [
                'error' => $e->getMessage(),
                'correspondence_id' => $correspondenceId,
            ]);

            return response()->json([
                'message' => $e->getMessage() ?: 'Terjadi kesalahan saat membuat disposisi',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Update a disposition.
     */
    public function update(UpdateDispositionRequest $request, int $id)
    {
        try {
            $disposition = CorrespondenceDisposition::findOrFail($id);

            // Check authorization
            $correspondence = $disposition->correspondence;
            if (!$request->user()->isAdminOrSuperAdmin() && 
                $correspondence->institution_id !== $request->user()->institution_id) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            $data = $request->validated();
            $disposition = $this->service->update($disposition, $data, $request->user()->id);

            return response()->json([
                'message' => 'Disposisi berhasil diperbarui',
                'data' => new DispositionResource($disposition),
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Disposisi tidak ditemukan'], 404);
        } catch (\Exception $e) {
            Log::error('Failed to update disposition', [
                'error' => $e->getMessage(),
                'disposition_id' => $id,
            ]);

            return response()->json([
                'message' => $e->getMessage() ?: 'Terjadi kesalahan saat memperbarui disposisi',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Complete a disposition.
     */
    public function complete(Request $request, int $id)
    {
        try {
            $disposition = CorrespondenceDisposition::findOrFail($id);

            // Check authorization
            $correspondence = $disposition->correspondence;
            if (!$request->user()->isAdminOrSuperAdmin() && 
                $correspondence->institution_id !== $request->user()->institution_id) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            $notes = $request->input('notes');
            $disposition = $this->service->complete($disposition, $request->user()->id, $notes);

            return response()->json([
                'message' => 'Disposisi berhasil diselesaikan',
                'data' => new DispositionResource($disposition),
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Disposisi tidak ditemukan'], 404);
        } catch (\Exception $e) {
            Log::error('Failed to complete disposition', [
                'error' => $e->getMessage(),
                'disposition_id' => $id,
            ]);

            return response()->json([
                'message' => $e->getMessage() ?: 'Terjadi kesalahan saat menyelesaikan disposisi',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Delete a disposition.
     */
    public function destroy(Request $request, int $id)
    {
        try {
            $disposition = CorrespondenceDisposition::findOrFail($id);

            // Check authorization
            $correspondence = $disposition->correspondence;
            if (!$request->user()->isAdminOrSuperAdmin() && 
                $correspondence->institution_id !== $request->user()->institution_id) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            $this->service->delete($disposition, $request->user()->id);

            return response()->json([
                'message' => 'Disposisi berhasil dihapus',
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Disposisi tidak ditemukan'], 404);
        } catch (\Exception $e) {
            Log::error('Failed to delete disposition', [
                'error' => $e->getMessage(),
                'disposition_id' => $id,
            ]);

            return response()->json([
                'message' => $e->getMessage() ?: 'Terjadi kesalahan saat menghapus disposisi',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }
}
