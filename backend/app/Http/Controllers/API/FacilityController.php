<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Building;
use App\Models\Employee;
use App\Models\Institution;
use App\Models\InventoryItem;
use App\Models\Land;
use App\Models\LessonSchedule;
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

            // Only log in debug mode
            if (config('app.debug')) {
                $user = $request->user();
                Log::debug('Get lands', [
                    'user_id' => $user->id,
                    'lands_count' => $lands->count(),
                    'filters' => $request->only(['search', 'status'])
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
                    return response()->json(['message' => 'Super admin harus menyertakan institution_id'], 400);
                }
            } elseif ($user->isAdmin()) {
                $institutionId = $request->institution_id ?? $user->institution_id;
            } else {
                $institutionId = $user->institution_id;
            }

            if (!$institutionId) {
                return response()->json(['message' => 'Institusi tidak ditemukan'], 400);
            }

            $validated['institution_id'] = $institutionId;
            
            $land = Land::create($validated);

            if (config('app.debug')) {
                Log::debug('Land created', [
                    'land_id' => $land->id,
                    'name' => $land->name,
                    'institution_id' => $institutionId
                ]);
            }

            return response()->json([
                'message' => 'Data tanah berhasil ditambahkan',
                'data' => $land->load('institution')
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
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
                    return response()->json(['message' => 'Super admin harus menyertakan institution_id'], 400);
                }
            } elseif ($user->isAdmin()) {
                $institutionId = $request->institution_id ?? $user->institution_id;
            } else {
                $institutionId = $user->institution_id;
            }

            if (!$institutionId) {
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

            if ($request->has('lab_type') && $request->lab_type !== '') {
                $query->where('lab_type', $request->lab_type);
            }

            $rooms = $query->with(['institution:id,name', 'building:id,name', 'responsibleEmployee:id,name,nip,nuptk'])
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
                'lab_type' => 'nullable|in:IPA,Komputer,Bahasa,Lainnya',
                'floor' => 'required|integer|min:1',
                'area' => 'nullable|numeric|min:0',
                'capacity' => 'nullable|integer|min:0',
                'condition' => 'required|in:Baik,Rusak Ringan,Rusak Sedang,Rusak Berat',
                'description' => 'nullable|string',
                'responsible_employee_id' => 'nullable|exists:employee,id',
            ]);

            $user = $request->user();
            if ($user->isSuperAdmin()) {
                // Super admin must provide institution_id
                $institutionId = $request->institution_id;
                if (!$institutionId) {
                    return response()->json(['message' => 'Super admin harus menyertakan institution_id'], 400);
                }
            } elseif ($user->isAdmin()) {
                $institutionId = $request->institution_id ?? $user->institution_id;
            } else {
                $institutionId = $user->institution_id;
            }

            if (!$institutionId) {
                return response()->json(['message' => 'Institusi tidak ditemukan'], 400);
            }

            // Normalize lab_type: only for Laboratorium
            $validated['lab_type'] = ($validated['type'] ?? '') === 'Laboratorium' ? ($validated['lab_type'] ?? null) : null;
            if ($validated['lab_type'] === '') {
                $validated['lab_type'] = null;
            }
            // Normalize empty responsible_employee_id to null
            $validated['responsible_employee_id'] = !empty($validated['responsible_employee_id']) ? $validated['responsible_employee_id'] : null;

            // Validate building_id belongs to same institution
            if ($validated['building_id']) {
                $building = Building::findOrFail($validated['building_id']);
                if ($building->institution_id != $institutionId) {
                    return response()->json(['message' => 'Gedung tidak ditemukan atau tidak sesuai institusi'], 422);
                }
            }

            // Validate responsible_employee_id belongs to same institution
            if (!empty($validated['responsible_employee_id'])) {
                $employee = Employee::findOrFail($validated['responsible_employee_id']);
                if ($employee->institution_id != $institutionId) {
                    return response()->json(['message' => 'Pegawai tidak ditemukan atau tidak sesuai institusi'], 422);
                }
            }

            $validated['institution_id'] = $institutionId;
            $room = Room::create($validated);

            return response()->json([
                'message' => 'Data ruangan berhasil ditambahkan',
                'data' => $room->load(['institution', 'building', 'responsibleEmployee'])
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
                'lab_type' => 'nullable|in:IPA,Komputer,Bahasa,Lainnya',
                'floor' => 'required|integer|min:1',
                'area' => 'nullable|numeric|min:0',
                'capacity' => 'nullable|integer|min:0',
                'condition' => 'required|in:Baik,Rusak Ringan,Rusak Sedang,Rusak Berat',
                'description' => 'nullable|string',
                'responsible_employee_id' => 'nullable|exists:employee,id',
            ]);

            // Normalize lab_type: only for Laboratorium
            $validated['lab_type'] = ($validated['type'] ?? '') === 'Laboratorium' ? ($validated['lab_type'] ?? null) : null;
            if (($validated['lab_type'] ?? '') === '') {
                $validated['lab_type'] = null;
            }

            // Validate building_id belongs to same institution
            if ($validated['building_id']) {
                $building = Building::findOrFail($validated['building_id']);
                if ($building->institution_id != $room->institution_id) {
                    return response()->json(['message' => 'Gedung tidak ditemukan atau tidak sesuai institusi'], 422);
                }
            }

            // Normalize empty responsible_employee_id to null
            $validated['responsible_employee_id'] = $validated['responsible_employee_id'] ?? null;
            if ($validated['responsible_employee_id'] === '') {
                $validated['responsible_employee_id'] = null;
            }

            // Validate responsible_employee_id belongs to same institution
            if ($validated['responsible_employee_id']) {
                $employee = Employee::findOrFail($validated['responsible_employee_id']);
                if ($employee->institution_id != $room->institution_id) {
                    return response()->json(['message' => 'Pegawai tidak ditemukan atau tidak sesuai institusi'], 422);
                }
            }

            $room->update($validated);

            return response()->json([
                'message' => 'Data ruangan berhasil diperbarui',
                'data' => $room->load(['institution', 'building', 'responsibleEmployee'])
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

    /**
     * Laporan khusus lab: ringkasan dan daftar lab dengan jumlah inventaris & jadwal.
     */
    public function getLabReport(Request $request)
    {
        try {
            $user = $request->user();
            $institutionId = $user->institution_id;
            if ($user->isSuperAdmin() || $user->isAdmin()) {
                $institutionId = $request->get('institution_id', $institutionId);
            }
            if (!$institutionId) {
                return response()->json(['message' => 'Institusi tidak ditemukan'], 403);
            }

            $institution = Institution::find($institutionId);
            $activeSemesterId = $institution?->active_semester_id;

            $rooms = Room::with(['building:id,name', 'responsibleEmployee:id,name,nip'])
                ->where('institution_id', $institutionId)
                ->where('type', 'Laboratorium')
                ->orderBy('name')
                ->get();

            $roomIds = $rooms->pluck('id')->toArray();
            $inventoryCounts = InventoryItem::whereIn('room_id', $roomIds)
                ->selectRaw('room_id, COUNT(*) as cnt')
                ->groupBy('room_id')
                ->pluck('cnt', 'room_id');

            $scheduleCounts = [];
            if ($activeSemesterId) {
                $scheduleCounts = LessonSchedule::whereIn('room_id', $roomIds)
                    ->where('semester_id', $activeSemesterId)
                    ->selectRaw('room_id, COUNT(*) as cnt')
                    ->groupBy('room_id')
                    ->pluck('cnt', 'room_id')
                    ->toArray();
            }

            $byCondition = [];
            $byLabType = [];
            $labs = [];
            foreach ($rooms as $room) {
                $byCondition[$room->condition] = ($byCondition[$room->condition] ?? 0) + 1;
                $lt = $room->lab_type ?? 'Lainnya';
                $byLabType[$lt] = ($byLabType[$lt] ?? 0) + 1;
                $labs[] = [
                    'id' => $room->id,
                    'name' => $room->name,
                    'code' => $room->code,
                    'lab_type' => $room->lab_type,
                    'building' => $room->building ? ['id' => $room->building->id, 'name' => $room->building->name] : null,
                    'condition' => $room->condition,
                    'responsible_employee' => $room->responsibleEmployee ? [
                        'id' => $room->responsibleEmployee->id,
                        'name' => $room->responsibleEmployee->name,
                        'nip' => $room->responsibleEmployee->nip,
                    ] : null,
                    'inventory_count' => $inventoryCounts[$room->id] ?? 0,
                    'schedule_count' => $scheduleCounts[$room->id] ?? 0,
                ];
            }

            return response()->json([
                'data' => [
                    'summary' => [
                        'total_labs' => $rooms->count(),
                        'by_condition' => $byCondition,
                        'by_lab_type' => $byLabType,
                    ],
                    'labs' => $labs,
                    'active_semester_id' => $activeSemesterId,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to get lab report', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Gagal mengambil laporan lab'], 500);
        }
    }

    /**
     * Lab yang menjadi tanggung jawab user saat ini (Kepala Lab).
     */
    public function getMyLabs(Request $request)
    {
        try {
            $user = $request->user();
            $employee = $user->employeeProfile()->first();
            if (!$employee) {
                return response()->json(['data' => ['labs' => [], 'summary' => ['total' => 0]]]);
            }

            $institutionId = $employee->institution_id;
            $institution = Institution::find($institutionId);
            $activeSemesterId = $institution?->active_semester_id;

            $rooms = Room::with(['building:id,name'])
                ->where('institution_id', $institutionId)
                ->where('type', 'Laboratorium')
                ->where('responsible_employee_id', $employee->id)
                ->orderBy('name')
                ->get();

            $roomIds = $rooms->pluck('id')->toArray();
            $inventoryCounts = $roomIds ? InventoryItem::whereIn('room_id', $roomIds)
                ->selectRaw('room_id, COUNT(*) as cnt')
                ->groupBy('room_id')
                ->pluck('cnt', 'room_id') : collect();
            $scheduleCounts = [];
            if ($activeSemesterId && $roomIds) {
                $scheduleCounts = LessonSchedule::whereIn('room_id', $roomIds)
                    ->where('semester_id', $activeSemesterId)
                    ->selectRaw('room_id, COUNT(*) as cnt')
                    ->groupBy('room_id')
                    ->pluck('cnt', 'room_id')
                    ->toArray();
            }

            $byCondition = [];
            $labs = [];
            foreach ($rooms as $room) {
                $byCondition[$room->condition] = ($byCondition[$room->condition] ?? 0) + 1;
                $labs[] = [
                    'id' => $room->id,
                    'name' => $room->name,
                    'code' => $room->code,
                    'lab_type' => $room->lab_type,
                    'building' => $room->building ? ['id' => $room->building->id, 'name' => $room->building->name] : null,
                    'condition' => $room->condition,
                    'inventory_count' => $inventoryCounts[$room->id] ?? 0,
                    'schedule_count' => $scheduleCounts[$room->id] ?? 0,
                ];
            }

            return response()->json([
                'data' => [
                    'summary' => [
                        'total' => $rooms->count(),
                        'by_condition' => $byCondition,
                    ],
                    'labs' => $labs,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to get my labs', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Gagal mengambil data lab Anda'], 500);
        }
    }
}
