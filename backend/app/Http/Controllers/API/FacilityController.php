<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Building;
use App\Models\Land;
use App\Models\Room;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class FacilityController extends Controller
{
    // ========== LAND (TANAH) ==========
    
    public function getLands(Request $request)
    {
        try {
            $query = Land::query();

            $user = $request->user();
            
            // Super admin can see all, admin can see all or filter by institution_id
            if ($user->isSuperAdmin() || $user->isAdmin()) {
                if ($request->has('institution_id')) {
                    $query->where('institution_id', $request->institution_id);
                }
                // If no institution_id filter, show all (for super admin/admin)
            } else {
                // Regular users only see their institution's data
                if ($user->institution_id) {
                    $query->where('institution_id', $user->institution_id);
                } else {
                    // User has no institution, return empty
                    return response()->json([
                        'data' => []
                    ]);
                }
            }

            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('name', 'like', '%' . $search . '%')
                      ->orWhere('certificate_number', 'like', '%' . $search . '%')
                      ->orWhere('location', 'like', '%' . $search . '%');
                });
            }

            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            $lands = $query->with('institution:id,name')
                ->orderBy('created_at', 'desc')
                ->get();

            $user = $request->user();
            Log::info('Get lands', [
                'user_id' => $user->id,
                'user_name' => $user->name,
                'user_email' => $user->email,
                'user_role' => $user->role,
                'user_is_admin' => $user->isAdmin(),
                'user_is_super_admin' => $user->isSuperAdmin(),
                'user_institution_id' => $user->institution_id,
                'lands_count' => $lands->count(),
                'filters' => $request->only(['search', 'status']),
                'sql_query' => $query->toSql(),
                'sql_bindings' => $query->getBindings(),
                'request_has_institution_id' => $request->has('institution_id'),
                'request_institution_id' => $request->institution_id
            ]);

            // Debug: Log actual data
            if ($lands->count() > 0) {
                Log::info('Get lands - Data found', [
                    'first_land' => $lands->first()->toArray()
                ]);
            } else {
                Log::warning('Get lands - No data found', [
                    'user_role' => $user->role,
                    'user_is_super_admin' => $user->isSuperAdmin(),
                    'user_is_admin' => $user->isAdmin(),
                    'user_institution_id' => $user->institution_id,
                    'query_conditions' => [
                        'institution_id_filter' => $user->isSuperAdmin() || $user->isAdmin() ? 'all' : $user->institution_id,
                        'search' => $request->has('search') ? $request->search : null,
                        'status' => $request->has('status') ? $request->status : null
                    ],
                    'total_lands_in_db' => Land::count()
                ]);
            }

            return response()->json([
                'data' => $lands
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to get lands', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['message' => 'Gagal mengambil data tanah'], 500);
        }
    }

    public function createLand(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'certificate_number' => 'nullable|string|max:255',
                'certificate_type' => 'nullable|in:SHM,SHGB,HGB,Hak Pakai,Tanpa Sertifikat',
                'area' => 'required|numeric|min:0',
                'location' => 'nullable|string|max:255',
                'status' => 'required|in:Milik Sendiri,Sewa,Pinjam,Hak Pakai',
                'acquisition_date' => 'nullable|date',
                'description' => 'nullable|string',
            ]);

            $user = $request->user();
            if ($user->isSuperAdmin()) {
                // Super admin must provide institution_id
                $institutionId = $request->institution_id;
                if (!$institutionId) {
                    Log::warning('Create land failed: super admin must provide institution_id', [
                        'user_id' => $user->id
                    ]);
                    return response()->json(['message' => 'Super admin harus menyertakan institution_id'], 400);
                }
            } elseif ($user->isAdmin()) {
                $institutionId = $request->institution_id ?? $user->institution_id;
            } else {
                $institutionId = $user->institution_id;
            }

            if (!$institutionId) {
                Log::warning('Create land failed: institution_id not found', [
                    'user_id' => $user->id,
                    'is_admin' => $user->isAdmin(),
                    'is_super_admin' => $user->isSuperAdmin()
                ]);
                return response()->json(['message' => 'Institusi tidak ditemukan'], 400);
            }

            $validated['institution_id'] = $institutionId;
            
            Log::info('Creating land', [
                'validated' => $validated,
                'institution_id' => $institutionId
            ]);
            
            $land = Land::create($validated);
            
            Log::info('Land created successfully', [
                'land_id' => $land->id,
                'name' => $land->name
            ]);

            return response()->json([
                'message' => 'Data tanah berhasil ditambahkan',
                'data' => $land->load('institution')
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::warning('Land validation failed', ['errors' => $e->errors()]);
            return response()->json([
                'message' => 'Validasi gagal',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Failed to create land', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['message' => 'Gagal menambahkan data tanah'], 500);
        }
    }

    public function updateLand(Request $request, $id)
    {
        try {
            $land = Land::findOrFail($id);

            $user = $request->user();
            if (!$user->isSuperAdmin() && !$user->isAdmin()) {
                if ($user->institution_id != $land->institution_id) {
                    return response()->json(['message' => 'Unauthorized'], 403);
                }
            }

            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'certificate_number' => 'nullable|string|max:255',
                'certificate_type' => 'nullable|in:SHM,SHGB,HGB,Hak Pakai,Tanpa Sertifikat',
                'area' => 'required|numeric|min:0',
                'location' => 'nullable|string|max:255',
                'status' => 'required|in:Milik Sendiri,Sewa,Pinjam,Hak Pakai',
                'acquisition_date' => 'nullable|date',
                'description' => 'nullable|string',
            ]);

            $land->update($validated);

            return response()->json([
                'message' => 'Data tanah berhasil diperbarui',
                'data' => $land->load('institution')
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Data tanah tidak ditemukan'], 404);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Validasi gagal',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Failed to update land', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Gagal memperbarui data tanah'], 500);
        }
    }

    public function deleteLand(Request $request, $id)
    {
        try {
            $land = Land::findOrFail($id);

            $user = $request->user();
            if (!$user->isSuperAdmin() && !$user->isAdmin()) {
                if ($user->institution_id != $land->institution_id) {
                    return response()->json(['message' => 'Unauthorized'], 403);
                }
            }

            $land->delete();

            return response()->json(['message' => 'Data tanah berhasil dihapus']);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Data tanah tidak ditemukan'], 404);
        } catch (\Exception $e) {
            Log::error('Failed to delete land', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Gagal menghapus data tanah'], 500);
        }
    }

    // ========== BUILDING (GEDUNG) ==========

    public function getBuildings(Request $request)
    {
        try {
            $query = Building::query();

            $user = $request->user();
            
            // Super admin can see all, admin can see all or filter by institution_id
            if ($user->isSuperAdmin() || $user->isAdmin()) {
                if ($request->has('institution_id')) {
                    $query->where('institution_id', $request->institution_id);
                }
                // If no institution_id filter, show all (for super admin/admin)
            } else {
                // Regular users only see their institution's data
                if ($user->institution_id) {
                    $query->where('institution_id', $user->institution_id);
                } else {
                    // User has no institution, return empty
                    return response()->json([
                        'data' => []
                    ]);
                }
            }

            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('name', 'like', '%' . $search . '%')
                      ->orWhere('code', 'like', '%' . $search . '%');
                });
            }

            if ($request->has('condition')) {
                $query->where('condition', $request->condition);
            }

            if ($request->has('land_id')) {
                $query->where('land_id', $request->land_id);
            }

            $buildings = $query->with(['institution:id,name', 'land:id,name'])
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'data' => $buildings
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to get buildings', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Gagal mengambil data gedung'], 500);
        }
    }

    public function createBuilding(Request $request)
    {
        try {
            $validated = $request->validate([
                'land_id' => 'nullable|exists:land,id',
                'name' => 'required|string|max:255',
                'code' => 'nullable|string|max:255',
                'floor_count' => 'required|integer|min:1',
                'building_area' => 'nullable|numeric|min:0',
                'condition' => 'required|in:Baik,Rusak Ringan,Rusak Sedang,Rusak Berat',
                'construction_year' => 'nullable|integer|min:1900|max:' . date('Y'),
                'description' => 'nullable|string',
            ]);

            $user = $request->user();
            if ($user->isSuperAdmin()) {
                // Super admin must provide institution_id
                $institutionId = $request->institution_id;
                if (!$institutionId) {
                    Log::warning('Create building failed: super admin must provide institution_id', [
                        'user_id' => $user->id
                    ]);
                    return response()->json(['message' => 'Super admin harus menyertakan institution_id'], 400);
                }
            } elseif ($user->isAdmin()) {
                $institutionId = $request->institution_id ?? $user->institution_id;
            } else {
                $institutionId = $user->institution_id;
            }

            if (!$institutionId) {
                Log::warning('Create building failed: institution_id not found', [
                    'user_id' => $user->id,
                    'is_admin' => $user->isAdmin(),
                    'is_super_admin' => $user->isSuperAdmin()
                ]);
                return response()->json(['message' => 'Institusi tidak ditemukan'], 400);
            }

            // Validate land_id belongs to same institution
            if ($validated['land_id']) {
                $land = Land::findOrFail($validated['land_id']);
                if ($land->institution_id != $institutionId) {
                    return response()->json(['message' => 'Tanah tidak ditemukan atau tidak sesuai institusi'], 422);
                }
            }

            $validated['institution_id'] = $institutionId;
            $building = Building::create($validated);

            return response()->json([
                'message' => 'Data gedung berhasil ditambahkan',
                'data' => $building->load(['institution', 'land'])
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Validasi gagal',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Failed to create building', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Gagal menambahkan data gedung'], 500);
        }
    }

    public function updateBuilding(Request $request, $id)
    {
        try {
            $building = Building::findOrFail($id);

            $user = $request->user();
            if (!$user->isSuperAdmin() && !$user->isAdmin()) {
                if ($user->institution_id != $building->institution_id) {
                    return response()->json(['message' => 'Unauthorized'], 403);
                }
            }

            $validated = $request->validate([
                'land_id' => 'nullable|exists:land,id',
                'name' => 'required|string|max:255',
                'code' => 'nullable|string|max:255',
                'floor_count' => 'required|integer|min:1',
                'building_area' => 'nullable|numeric|min:0',
                'condition' => 'required|in:Baik,Rusak Ringan,Rusak Sedang,Rusak Berat',
                'construction_year' => 'nullable|integer|min:1900|max:' . date('Y'),
                'description' => 'nullable|string',
            ]);

            // Validate land_id belongs to same institution
            if ($validated['land_id']) {
                $land = Land::findOrFail($validated['land_id']);
                if ($land->institution_id != $building->institution_id) {
                    return response()->json(['message' => 'Tanah tidak ditemukan atau tidak sesuai institusi'], 422);
                }
            }

            $building->update($validated);

            return response()->json([
                'message' => 'Data gedung berhasil diperbarui',
                'data' => $building->load(['institution', 'land'])
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Data gedung tidak ditemukan'], 404);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Validasi gagal',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Failed to update building', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Gagal memperbarui data gedung'], 500);
        }
    }

    public function deleteBuilding(Request $request, $id)
    {
        try {
            $building = Building::findOrFail($id);

            $user = $request->user();
            if (!$user->isSuperAdmin() && !$user->isAdmin()) {
                if ($user->institution_id != $building->institution_id) {
                    return response()->json(['message' => 'Unauthorized'], 403);
                }
            }

            $building->delete();

            return response()->json(['message' => 'Data gedung berhasil dihapus']);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Data gedung tidak ditemukan'], 404);
        } catch (\Exception $e) {
            Log::error('Failed to delete building', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Gagal menghapus data gedung'], 500);
        }
    }

    // ========== ROOM (RUANGAN) ==========

    public function getRooms(Request $request)
    {
        try {
            $query = Room::query();

            $user = $request->user();
            
            // Super admin can see all, admin can see all or filter by institution_id
            if ($user->isSuperAdmin() || $user->isAdmin()) {
                if ($request->has('institution_id')) {
                    $query->where('institution_id', $request->institution_id);
                }
                // If no institution_id filter, show all (for super admin/admin)
            } else {
                // Regular users only see their institution's data
                if ($user->institution_id) {
                    $query->where('institution_id', $user->institution_id);
                } else {
                    // User has no institution, return empty
                    return response()->json([
                        'data' => []
                    ]);
                }
            }

            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('name', 'like', '%' . $search . '%')
                      ->orWhere('code', 'like', '%' . $search . '%');
                });
            }

            if ($request->has('type')) {
                $query->where('type', $request->type);
            }

            if ($request->has('condition')) {
                $query->where('condition', $request->condition);
            }

            if ($request->has('building_id')) {
                $query->where('building_id', $request->building_id);
            }

            $rooms = $query->with(['institution:id,name', 'building:id,name'])
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'data' => $rooms
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to get rooms', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Gagal mengambil data ruangan'], 500);
        }
    }

    public function createRoom(Request $request)
    {
        try {
            $validated = $request->validate([
                'building_id' => 'nullable|exists:building,id',
                'name' => 'required|string|max:255',
                'code' => 'nullable|string|max:255',
                'type' => 'required|in:Kelas,Laboratorium,Perpustakaan,Kantor,Aula,Musholla,Kantin,Toilet,Gudang,Lainnya',
                'floor' => 'required|integer|min:1',
                'area' => 'nullable|numeric|min:0',
                'capacity' => 'nullable|integer|min:0',
                'condition' => 'required|in:Baik,Rusak Ringan,Rusak Sedang,Rusak Berat',
                'description' => 'nullable|string',
            ]);

            $user = $request->user();
            if ($user->isSuperAdmin()) {
                // Super admin must provide institution_id
                $institutionId = $request->institution_id;
                if (!$institutionId) {
                    Log::warning('Create room failed: super admin must provide institution_id', [
                        'user_id' => $user->id
                    ]);
                    return response()->json(['message' => 'Super admin harus menyertakan institution_id'], 400);
                }
            } elseif ($user->isAdmin()) {
                $institutionId = $request->institution_id ?? $user->institution_id;
            } else {
                $institutionId = $user->institution_id;
            }

            if (!$institutionId) {
                Log::warning('Create room failed: institution_id not found', [
                    'user_id' => $user->id,
                    'is_admin' => $user->isAdmin(),
                    'is_super_admin' => $user->isSuperAdmin()
                ]);
                return response()->json(['message' => 'Institusi tidak ditemukan'], 400);
            }

            // Validate building_id belongs to same institution
            if ($validated['building_id']) {
                $building = Building::findOrFail($validated['building_id']);
                if ($building->institution_id != $institutionId) {
                    return response()->json(['message' => 'Gedung tidak ditemukan atau tidak sesuai institusi'], 422);
                }
            }

            $validated['institution_id'] = $institutionId;
            $room = Room::create($validated);

            return response()->json([
                'message' => 'Data ruangan berhasil ditambahkan',
                'data' => $room->load(['institution', 'building'])
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Validasi gagal',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Failed to create room', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Gagal menambahkan data ruangan'], 500);
        }
    }

    public function updateRoom(Request $request, $id)
    {
        try {
            $room = Room::findOrFail($id);

            $user = $request->user();
            if (!$user->isSuperAdmin() && !$user->isAdmin()) {
                if ($user->institution_id != $room->institution_id) {
                    return response()->json(['message' => 'Unauthorized'], 403);
                }
            }

            $validated = $request->validate([
                'building_id' => 'nullable|exists:building,id',
                'name' => 'required|string|max:255',
                'code' => 'nullable|string|max:255',
                'type' => 'required|in:Kelas,Laboratorium,Perpustakaan,Kantor,Aula,Musholla,Kantin,Toilet,Gudang,Lainnya',
                'floor' => 'required|integer|min:1',
                'area' => 'nullable|numeric|min:0',
                'capacity' => 'nullable|integer|min:0',
                'condition' => 'required|in:Baik,Rusak Ringan,Rusak Sedang,Rusak Berat',
                'description' => 'nullable|string',
            ]);

            // Validate building_id belongs to same institution
            if ($validated['building_id']) {
                $building = Building::findOrFail($validated['building_id']);
                if ($building->institution_id != $room->institution_id) {
                    return response()->json(['message' => 'Gedung tidak ditemukan atau tidak sesuai institusi'], 422);
                }
            }

            $room->update($validated);

            return response()->json([
                'message' => 'Data ruangan berhasil diperbarui',
                'data' => $room->load(['institution', 'building'])
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Data ruangan tidak ditemukan'], 404);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Validasi gagal',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Failed to update room', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Gagal memperbarui data ruangan'], 500);
        }
    }

    public function deleteRoom(Request $request, $id)
    {
        try {
            $room = Room::findOrFail($id);

            $user = $request->user();
            if (!$user->isSuperAdmin() && !$user->isAdmin()) {
                if ($user->institution_id != $room->institution_id) {
                    return response()->json(['message' => 'Unauthorized'], 403);
                }
            }

            $room->delete();

            return response()->json(['message' => 'Data ruangan berhasil dihapus']);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Data ruangan tidak ditemukan'], 404);
        } catch (\Exception $e) {
            Log::error('Failed to delete room', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Gagal menghapus data ruangan'], 500);
        }
    }
}
