<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCorrespondenceRequest;
use App\Http\Requests\UpdateCorrespondenceRequest;
use App\Http\Resources\CorrespondenceResource;
use App\Models\Correspondence;
use App\Models\CorrespondenceCategory;
use App\Services\CorrespondenceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf as DomPDF;

class CorrespondenceController extends Controller
{
    public function __construct(
        private CorrespondenceService $service
    ) {}

    /**
     * Display a listing of correspondence.
     */
    public function index(Request $request)
    {
        try {
            $filters = $request->only([
                'type', 'status', 'priority', 'category_id', 'letter_type_code', 'search',
                'date_from', 'date_to'
            ]);
            $filters['with_trashed'] = filter_var($request->get('with_trashed'), FILTER_VALIDATE_BOOLEAN);
            $filters['only_trashed'] = filter_var($request->get('only_trashed'), FILTER_VALIDATE_BOOLEAN);

            $institutionId = null;
            if (!$request->user()->isAdminOrSuperAdmin()) {
                $institutionId = $request->user()->institution_id;
            } elseif ($request->has('institution_id')) {
                $institutionId = $request->institution_id;
            }

            $perPage = min($request->get('per_page', 15), 100);
            $correspondence = $this->service->list($filters, $institutionId, $perPage);

            // Laravel Resource Collection automatically handles pagination
            // It returns: { data: [...], links: {...}, meta: {...} }
            return CorrespondenceResource::collection($correspondence);
        } catch (\Exception $e) {
            Log::error('Failed to list correspondence', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => $request->user()->id ?? null,
                'institution_id' => $institutionId ?? null,
            ]);

            return response()->json([
                'message' => 'Terjadi kesalahan saat mengambil data surat',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Store a newly created correspondence.
     */
    public function store(StoreCorrespondenceRequest $request)
    {
        try {
            $institutionId = $request->user()->isAdminOrSuperAdmin() 
                ? $request->institution_id 
                : $request->user()->institution_id;

            if (!$institutionId) {
                return response()->json(['message' => 'Institusi tidak ditemukan'], 400);
            }

            $data = $request->validated();
            $data['institution_id'] = $institutionId;
            $data['status'] = $data['status'] ?? 'draft';
            
            // Ensure letter_number is null for auto-generate surat keluar
            if ($data['type'] === 'keluar' && empty($data['letter_number'])) {
                $data['letter_number'] = null;
            }
            
            // Ensure letter_type_code is set (should be from validation, but double check)
            if (empty($data['letter_type_code'])) {
                return response()->json([
                    'message' => 'Jenis surat wajib diisi',
                ], 422);
            }

            $file = $request->hasFile('file') ? $request->file('file') : null;

            Log::info('Creating correspondence', [
                'user_id' => $request->user()->id,
                'institution_id' => $institutionId,
                'type' => $data['type'] ?? null,
                'letter_type_code' => $data['letter_type_code'] ?? null,
            ]);

            $correspondence = $this->service->create($data, $request->user()->id, $file);

            return response()->json([
                'message' => 'Surat berhasil dibuat',
                'data' => new CorrespondenceResource($correspondence->load(['category', 'creator', 'institution'])),
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Validasi gagal',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Failed to create correspondence', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => $request->user()->id,
            ]);

            return response()->json([
                'message' => $e->getMessage() ?: 'Terjadi kesalahan saat membuat surat',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Display the specified correspondence.
     */
    public function show(Request $request, $id)
    {
        try {
            $correspondence = $this->service->find($id);

            // Check authorization using Policy
            $this->authorize('view', $correspondence);

            return response()->json([
                'data' => new CorrespondenceResource($correspondence),
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Surat tidak ditemukan'], 404);
        } catch (\Exception $e) {
            Log::error('Failed to show correspondence', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Terjadi kesalahan saat mengambil data surat',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Update the specified correspondence.
     */
    public function update(UpdateCorrespondenceRequest $request, $id)
    {
        try {
            $correspondence = Correspondence::findOrFail($id);

            // Check authorization using Policy
            $this->authorize('update', $correspondence);

            $data = $request->validated();
            $file = $request->hasFile('file') ? $request->file('file') : null;

            $correspondence = $this->service->update($correspondence, $data, $request->user()->id, $file);

            return response()->json([
                'message' => 'Surat berhasil diperbarui',
                'data' => new CorrespondenceResource($correspondence),
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Surat tidak ditemukan'], 404);
        } catch (\Exception $e) {
            Log::error('Failed to update correspondence', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Terjadi kesalahan saat memperbarui surat',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Remove the specified correspondence.
     */
    public function destroy(Request $request, $id)
    {
        try {
            $correspondence = Correspondence::findOrFail($id);

            // Check authorization using Policy
            $this->authorize('delete', $correspondence);

            $this->service->delete($correspondence, $request->user()->id);

            return response()->json([
                'message' => 'Surat berhasil dihapus',
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Surat tidak ditemukan'], 404);
        } catch (\Exception $e) {
            Log::error('Failed to delete correspondence', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Terjadi kesalahan saat menghapus surat',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Restore a soft-deleted correspondence.
     */
    public function restore(Request $request, $id)
    {
        try {
            $correspondence = Correspondence::withTrashed()->findOrFail($id);

            // Check authorization using Policy
            $this->authorize('restore', $correspondence);

            if ($correspondence->trashed()) {
                $correspondence->restore();
            }

            return response()->json([
                'message' => 'Surat berhasil dipulihkan',
                'data' => new CorrespondenceResource($correspondence->fresh(['category', 'creator', 'institution'])),
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Surat tidak ditemukan'], 404);
        } catch (\Exception $e) {
            Log::error('Failed to restore correspondence', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Terjadi kesalahan saat memulihkan surat',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Approve correspondence.
     */
    public function approve(Request $request, $id)
    {
        try {
            $correspondence = Correspondence::findOrFail($id);

            // Check authorization using Policy
            $this->authorize('approve', $correspondence);

            $correspondence = $this->service->approve($correspondence, $request->user()->id);

            return response()->json([
                'message' => 'Surat berhasil disetujui',
                'data' => new CorrespondenceResource($correspondence->load(['approver'])),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Send correspondence.
     */
    public function send(Request $request, $id)
    {
        try {
            $correspondence = Correspondence::findOrFail($id);

            // Check authorization using Policy
            $this->authorize('send', $correspondence);

            $correspondence = $this->service->send($correspondence, $request->user()->id);

            return response()->json([
                'message' => 'Surat berhasil dikirim',
                'data' => new CorrespondenceResource($correspondence),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Archive correspondence.
     */
    public function archive(Request $request, $id)
    {
        try {
            $correspondence = Correspondence::findOrFail($id);

            // Check authorization using Policy
            $this->authorize('archive', $correspondence);

            $correspondence = $this->service->archive($correspondence, $request->user()->id);

            return response()->json([
                'message' => 'Surat berhasil diarsipkan',
                'data' => new CorrespondenceResource($correspondence),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get categories.
     */
    public function categories(Request $request)
    {
        try {
            $institutionId = $request->user()->isAdminOrSuperAdmin() 
                ? ($request->institution_id ?? null)
                : $request->user()->institution_id;

            $query = CorrespondenceCategory::query();
            
            if ($institutionId) {
                $query->where('institution_id', $institutionId);
            }

            if ($request->has('type')) {
                $query->where('type', $request->type);
            }

            $categories = $query->orderBy('name')->get();

            return response()->json([
                'data' => $categories,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to get categories', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Terjadi kesalahan saat mengambil kategori',
            ], 500);
        }
    }

    /**
     * Get users from same institution (for disposition dropdown).
     */
    public function users(Request $request)
    {
        try {
            $institutionId = $request->user()->isAdminOrSuperAdmin() 
                ? ($request->institution_id ?? $request->user()->institution_id)
                : $request->user()->institution_id;

            if (!$institutionId) {
                return response()->json(['message' => 'Institusi tidak ditemukan'], 400);
            }

            $users = \App\Models\User::where('institution_id', $institutionId)
                ->where('id', '!=', $request->user()->id) // Exclude current user
                ->select('id', 'name', 'email', 'role')
                ->orderBy('name')
                ->get();

            return response()->json([
                'data' => $users,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to get users', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Terjadi kesalahan saat mengambil data user',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Get letter types (jenis surat standar).
     */
    public function letterTypes(Request $request)
    {
        try {
            $letterTypes = Correspondence::getLetterTypes();
            
            // Convert to array format for frontend
            $types = [];
            foreach ($letterTypes as $code => $type) {
                $types[] = [
                    'code' => $type['code'],
                    'abbr' => $type['abbr'],
                    'name' => $type['name'],
                ];
            }

            return response()->json([
                'data' => $types,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to get letter types', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Terjadi kesalahan saat mengambil jenis surat',
            ], 500);
        }
    }

    /**
     * Print correspondence as PDF.
     */
    public function print(Request $request, $id)
    {
        try {
            $correspondence = $this->service->find($id);

            // Check authorization using Policy
            $this->authorize('view', $correspondence);

            $institution = $correspondence->institution;
            
            // Prepare data for PDF
            $data = [
                'correspondence' => $correspondence,
                'institution' => $institution,
                'date' => now()->format('d F Y'),
            ];

            // Generate PDF
            $pdf = DomPDF::loadView('correspondence.print', $data);
            
            $filename = 'Surat_' . ($correspondence->letter_number ?? $correspondence->reference_number ?? $correspondence->id) . '.pdf';

            return $pdf->download($filename);
        } catch (\Exception $e) {
            Log::error('Failed to print correspondence', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Terjadi kesalahan saat mencetak surat',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }
}
