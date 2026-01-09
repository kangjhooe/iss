<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\ApproveInstitutionChangeRequestRequest;
use App\Http\Requests\StoreInstitutionChangeRequestRequest;
use App\Models\InstitutionChangeRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InstitutionChangeRequestController extends Controller
{
    /**
     * Display a listing of change requests.
     */
    public function index(Request $request)
    {
        try {
            $user = $request->user();
            
            if ($user->isSuperAdmin()) {
                // Super admin can see all requests
                $query = InstitutionChangeRequest::with(['institution', 'requester', 'approver'])
                    ->orderBy('created_at', 'desc');
                
                if ($request->has('status')) {
                    $query->where('status', $request->status);
                }
                
                $perPage = min($request->get('per_page', 15), 100);
                $requests = $query->paginate($perPage);
            } else {
                // Institution admin can only see their own requests
                $query = InstitutionChangeRequest::with(['institution', 'requester', 'approver'])
                    ->where('requested_by', $user->id)
                    ->orderBy('created_at', 'desc');
                
                if ($request->has('status')) {
                    $query->where('status', $request->status);
                }
                
                $perPage = min($request->get('per_page', 15), 100);
                $requests = $query->paginate($perPage);
            }
            
            return response()->json($requests);
        } catch (\Exception $e) {
            Log::error('Failed to list change requests', [
                'error' => $e->getMessage(),
            ]);
            
            return response()->json([
                'message' => 'Terjadi kesalahan saat mengambil data request',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Store a newly created change request.
     */
    public function store(StoreInstitutionChangeRequestRequest $request)
    {
        try {
            $user = $request->user();
            $institution = $user->institution;
            
            if (!$institution) {
                return response()->json([
                    'message' => 'Institusi tidak ditemukan',
                ], 404);
            }
            
            // Check if there's already a pending request for this field
            $existingRequest = InstitutionChangeRequest::where('institution_id', $institution->id)
                ->where('field_name', $request->field_name)
                ->where('status', 'pending')
                ->first();
            
            if ($existingRequest) {
                return response()->json([
                    'message' => 'Sudah ada request pending untuk field ini',
                ], 422);
            }
            
            DB::beginTransaction();
            
            $changeRequest = InstitutionChangeRequest::create([
                'institution_id' => $institution->id,
                'requested_by' => $user->id,
                'field_name' => $request->field_name,
                'old_value' => $institution->{$request->field_name},
                'new_value' => $request->new_value,
                'status' => 'pending',
            ]);
            
            DB::commit();
            
            Log::info('Change request created', [
                'request_id' => $changeRequest->id,
                'institution_id' => $institution->id,
                'user_id' => $user->id,
                'field_name' => $request->field_name,
            ]);
            
            return response()->json([
                'message' => 'Request perubahan berhasil dibuat',
                'data' => $changeRequest->load(['institution', 'requester']),
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create change request', [
                'error' => $e->getMessage(),
            ]);
            
            return response()->json([
                'message' => 'Terjadi kesalahan saat membuat request',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Display the specified change request.
     */
    public function show(Request $request, $id)
    {
        try {
            $changeRequest = InstitutionChangeRequest::with(['institution', 'requester', 'approver'])
                ->findOrFail($id);
            
            $user = $request->user();
            
            // Check authorization
            if (!$user->isSuperAdmin() && $changeRequest->requested_by !== $user->id) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }
            
            return response()->json($changeRequest);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Request tidak ditemukan',
            ], 404);
        } catch (\Exception $e) {
            Log::error('Failed to get change request', [
                'request_id' => $id,
                'error' => $e->getMessage(),
            ]);
            
            return response()->json([
                'message' => 'Terjadi kesalahan saat mengambil data request',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Approve or reject a change request.
     */
    public function approve(ApproveInstitutionChangeRequestRequest $request, $id)
    {
        try {
            $changeRequest = InstitutionChangeRequest::with('institution')->findOrFail($id);
            
            if ($changeRequest->status !== 'pending') {
                return response()->json([
                    'message' => 'Request sudah diproses',
                ], 422);
            }
            
            DB::beginTransaction();
            
            if ($request->action === 'approve') {
                // Update institution field
                $institution = $changeRequest->institution;
                $institution->{$changeRequest->field_name} = $changeRequest->new_value;
                $institution->save();
                
                // Update request status
                $changeRequest->status = 'approved';
                $changeRequest->approved_by = $request->user()->id;
                $changeRequest->approved_at = now();
                $changeRequest->save();
                
                Log::info('Change request approved', [
                    'request_id' => $changeRequest->id,
                    'institution_id' => $institution->id,
                    'approved_by' => $request->user()->id,
                ]);
                
                $message = 'Request berhasil disetujui';
            } else {
                // Reject request
                $changeRequest->status = 'rejected';
                $changeRequest->approved_by = $request->user()->id;
                $changeRequest->rejection_reason = $request->rejection_reason;
                $changeRequest->approved_at = now();
                $changeRequest->save();
                
                Log::info('Change request rejected', [
                    'request_id' => $changeRequest->id,
                    'rejected_by' => $request->user()->id,
                ]);
                
                $message = 'Request berhasil ditolak';
            }
            
            DB::commit();
            
            return response()->json([
                'message' => $message,
                'data' => $changeRequest->load(['institution', 'requester', 'approver']),
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Request tidak ditemukan',
            ], 404);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to approve/reject change request', [
                'request_id' => $id,
                'error' => $e->getMessage(),
            ]);
            
            return response()->json([
                'message' => 'Terjadi kesalahan saat memproses request',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Get pending requests count (for super admin).
     */
    public function pendingCount(Request $request)
    {
        try {
            if (!$request->user()->isSuperAdmin()) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }
            
            $count = InstitutionChangeRequest::where('status', 'pending')->count();
            
            return response()->json(['count' => $count]);
        } catch (\Exception $e) {
            Log::error('Failed to get pending count', [
                'error' => $e->getMessage(),
            ]);
            
            return response()->json([
                'message' => 'Terjadi kesalahan',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }
}
