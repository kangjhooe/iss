<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Building;
use App\Models\Employee;
use App\Models\Institution;
use App\Models\InventoryItem;
use App\Models\InventoryLoan;
use App\Models\InventoryMaintenance;
use App\Models\LabBooking;
use App\Models\LabUsageJournal;
use App\Models\Land;
use App\Models\LessonSchedule;
use App\Models\Room;
use App\Notifications\LabNotification;
use App\Support\InstitutionContext;
use Barryvdh\DomPDF\Facade\Pdf as DomPDF;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\HttpException;

class FacilityController extends Controller
{
    /**
     * Ensure user can view the room (same institution / admin).
     */
    protected function ensureCanViewRoom(Request $request, Room $room): void
    {
        $user = $request->user();
        if (!$user->canViewLab($room)) {
            throw new HttpException(403, 'Unauthorized');
        }
    }

    /**
     * Ensure user can manage the lab (admin or assigned PJ).
     */
    protected function ensureCanManageLab(Request $request, Room $room): void
    {
        $user = $request->user();
        if (!$user->canManageLab($room)) {
            throw new HttpException(403, 'Anda tidak berwenang mengelola lab ini');
        }
    }

    /**
     * Resolve institution id for the current user (active context / non-induk aware).
     */
    protected function resolveInstitutionId(Request $request): ?int
    {
        return InstitutionContext::resolveForUser(
            $request->user(),
            $request,
            $request->get('institution_id')
        );
    }

    // ========== LAND (TANAH) ==========
    
    public function getLands(Request $request)
    {
        try {
            $query = Land::query();

            $institutionId = $this->resolveInstitutionId($request);
            if (!$institutionId) {
                return response()->json(['data' => []]);
            }
            $query->where('institution_id', $institutionId);

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

            $institutionId = $this->resolveInstitutionId($request);
            if (!$institutionId) {
                return response()->json(['data' => []]);
            }
            $query->where('institution_id', $institutionId);

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
            $institutionId = $this->resolveInstitutionId($request);
            if (!$institutionId) {
                return response()->json(['data' => []]);
            }
            $query->where('institution_id', $institutionId);

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

            // Kepala Lab tanpa modul facility hanya melihat lab yang ditanggungjawabi
            if (
                !$user->isAdminOrSuperAdmin()
                && !$user->isInstitutionAdmin()
                && !$user->hasModuleAccess('facility')
                && $user->isLabResponsible()
            ) {
                $managedIds = $user->managedLabRoomIds();
                $query->whereIn('id', $managedIds ?: [0]);
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

    public function getRoom(Request $request, $id)
    {
        try {
            $room = Room::with(['institution:id,name', 'building:id,name', 'responsibleEmployee:id,name,nip,nuptk'])
                ->findOrFail($id);

            $this->ensureCanViewRoom($request, $room);

            $user = $request->user();
            $damagedCount = InventoryItem::where('room_id', $room->id)
                ->where(function ($q) {
                    $q->whereIn('condition', ['Rusak Ringan', 'Rusak Sedang', 'Rusak Berat'])
                        ->orWhereIn('status', ['Rusak', 'Hilang']);
                })
                ->count();

            $activeLoans = InventoryLoan::whereHas('item', fn ($q) => $q->where('room_id', $room->id))
                ->whereIn('status', ['Dipinjam', 'Terlambat'])
                ->count();

            $pendingBookings = LabBooking::where('room_id', $room->id)
                ->where('status', 'pending')
                ->count();

            $openMaintenance = InventoryMaintenance::whereHas('item', fn ($q) => $q->where('room_id', $room->id))
                ->whereIn('status', ['Terjadwal', 'Dalam Proses'])
                ->count();

            $data = $room->toArray();
            $data['can_manage'] = $user->canManageLab($room);
            $data['is_admin'] = $user->isAdminOrSuperAdmin() || $user->isInstitutionAdmin();
            $data['stats'] = [
                'inventory_count' => InventoryItem::where('room_id', $room->id)->count(),
                'damaged_count' => $damagedCount,
                'active_loans' => $activeLoans,
                'pending_bookings' => $pendingBookings,
                'open_maintenance' => $openMaintenance,
            ];

            return response()->json(['data' => $data]);
        } catch (HttpException $e) {
            return response()->json(['message' => $e->getMessage()], $e->getStatusCode());
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Data ruangan tidak ditemukan'], 404);
        } catch (\Exception $e) {
            Log::error('Failed to get room', ['error' => $e->getMessage()]);
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
            if (($validated['type'] ?? '') === 'Laboratorium'
                && !$user->isAdminOrSuperAdmin()
                && !$user->isInstitutionAdmin()) {
                return response()->json(['message' => 'Hanya admin yang dapat menambah lab'], 403);
            }
            $institutionId = $this->resolveInstitutionId($request);
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

            // Validate responsible_employee_id belongs to same institution (induk + non-induk)
            if (!empty($validated['responsible_employee_id'])) {
                $employee = Employee::findOrFail($validated['responsible_employee_id']);
                if (!InstitutionContext::employeeBelongsToInstitution($employee, (int) $institutionId)) {
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
            $isAdmin = $user->isAdminOrSuperAdmin() || $user->isInstitutionAdmin();

            if ($room->type === 'Laboratorium') {
                if (!$user->canManageLab($room)) {
                    return response()->json(['message' => 'Anda tidak berwenang mengelola lab ini'], 403);
                }
            } elseif (!$isAdmin && !InstitutionContext::canAccessInstitution($user, (int) $room->institution_id)) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            // PJ may only update condition/description on lab rooms
            if ($room->type === 'Laboratorium' && !$isAdmin) {
                $validated = $request->validate([
                    'condition' => 'sometimes|in:Baik,Rusak Ringan,Rusak Sedang,Rusak Berat',
                    'description' => 'nullable|string',
                ]);
                $room->update($validated);
                return response()->json([
                    'message' => 'Data ruangan berhasil diperbarui',
                    'data' => $room->load(['institution', 'building', 'responsibleEmployee'])
                ]);
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

            // Validate responsible_employee_id belongs to same institution (induk + non-induk)
            if ($validated['responsible_employee_id']) {
                $employee = Employee::findOrFail($validated['responsible_employee_id']);
                if (!InstitutionContext::employeeBelongsToInstitution($employee, (int) $room->institution_id)) {
                    return response()->json(['message' => 'Pegawai tidak ditemukan atau tidak sesuai institusi'], 422);
                }
            }

            // Capture previous PJ before update (admin path)
            $previousResponsibleId = $room->responsible_employee_id;

            $room->update($validated);

            // Notify newly assigned lab responsible
            $newResponsibleId = $room->responsible_employee_id;
            if (
                $room->type === 'Laboratorium'
                && $newResponsibleId
                && (int) $newResponsibleId !== (int) $previousResponsibleId
            ) {
                $employee = Employee::find($newResponsibleId);
                $pjUser = $employee?->userAccount;
                if ($pjUser) {
                    $pjUser->notify(new LabNotification(
                        'assigned',
                        "Anda ditetapkan sebagai penanggung jawab lab {$room->name}.",
                        ['room_id' => $room->id, 'room_name' => $room->name]
                    ));
                }
            }

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
            $isAdmin = $user->isAdminOrSuperAdmin() || $user->isInstitutionAdmin();

            // Only admin may delete lab rooms
            if ($room->type === 'Laboratorium' && !$isAdmin) {
                return response()->json(['message' => 'Hanya admin yang dapat menghapus lab'], 403);
            }

            if (!$isAdmin && !InstitutionContext::canAccessInstitution($user, (int) $room->institution_id)) {
                return response()->json(['message' => 'Unauthorized'], 403);
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
            $institutionId = $this->resolveInstitutionId($request);
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

            $damagedCounts = $roomIds ? InventoryItem::whereIn('room_id', $roomIds)
                ->where(function ($q) {
                    $q->whereIn('condition', ['Rusak Ringan', 'Rusak Sedang', 'Rusak Berat'])
                        ->orWhereIn('status', ['Rusak', 'Hilang']);
                })
                ->selectRaw('room_id, COUNT(*) as cnt')
                ->groupBy('room_id')
                ->pluck('cnt', 'room_id') : collect();

            $openMaintenanceCounts = $roomIds ? InventoryMaintenance::whereHas('item', fn ($q) => $q->whereIn('room_id', $roomIds))
                ->whereIn('inventory_maintenance.status', ['Terjadwal', 'Dalam Proses'])
                ->join('inventory_item', 'inventory_maintenance.item_id', '=', 'inventory_item.id')
                ->whereNull('inventory_maintenance.deleted_at')
                ->selectRaw('inventory_item.room_id, COUNT(*) as cnt')
                ->groupBy('inventory_item.room_id')
                ->pluck('cnt', 'room_id') : collect();

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
                    'damaged_count' => $damagedCounts[$room->id] ?? 0,
                    'open_maintenance_count' => $openMaintenanceCounts[$room->id] ?? 0,
                    'schedule_count' => $scheduleCounts[$room->id] ?? 0,
                ];
            }

            return response()->json([
                'data' => [
                    'summary' => [
                        'total_labs' => $rooms->count(),
                        'by_condition' => $byCondition,
                        'by_lab_type' => $byLabType,
                        'total_damaged_items' => $damagedCounts->sum(),
                        'total_open_maintenance' => $openMaintenanceCounts->sum(),
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

            $institutionId = $this->resolveInstitutionId($request) ?: $employee->institution_id;
            $institution = Institution::find($institutionId);
            $activeSemesterId = $institution?->active_semester_id;

            $rooms = Room::with(['building:id,name'])
                ->where('type', 'Laboratorium')
                ->where('responsible_employee_id', $employee->id)
                ->when($institutionId, fn ($q) => $q->where('institution_id', $institutionId))
                ->orderBy('name')
                ->get();

            $roomIds = $rooms->pluck('id')->toArray();
            $inventoryCounts = $roomIds ? InventoryItem::whereIn('room_id', $roomIds)
                ->selectRaw('room_id, COUNT(*) as cnt')
                ->groupBy('room_id')
                ->pluck('cnt', 'room_id') : collect();

            $damagedCounts = $roomIds ? InventoryItem::whereIn('room_id', $roomIds)
                ->where(function ($q) {
                    $q->whereIn('condition', ['Rusak Ringan', 'Rusak Sedang', 'Rusak Berat'])
                        ->orWhereIn('status', ['Rusak', 'Hilang']);
                })
                ->selectRaw('room_id, COUNT(*) as cnt')
                ->groupBy('room_id')
                ->pluck('cnt', 'room_id') : collect();

            $activeLoanCounts = $roomIds ? InventoryLoan::whereHas('item', fn ($q) => $q->whereIn('room_id', $roomIds))
                ->whereIn('inventory_loan.status', ['Dipinjam', 'Terlambat'])
                ->join('inventory_item', 'inventory_loan.item_id', '=', 'inventory_item.id')
                ->whereNull('inventory_loan.deleted_at')
                ->selectRaw('inventory_item.room_id, COUNT(*) as cnt')
                ->groupBy('inventory_item.room_id')
                ->pluck('cnt', 'room_id') : collect();

            $pendingBookingCounts = $roomIds ? LabBooking::whereIn('room_id', $roomIds)
                ->where('status', 'pending')
                ->selectRaw('room_id, COUNT(*) as cnt')
                ->groupBy('room_id')
                ->pluck('cnt', 'room_id') : collect();

            $today = now()->dayOfWeekIso; // 1=Mon .. 7=Sun
            $todayScheduleCounts = [];
            if ($activeSemesterId && $roomIds) {
                $todayScheduleCounts = LessonSchedule::whereIn('room_id', $roomIds)
                    ->where('semester_id', $activeSemesterId)
                    ->where('day_of_week', $today)
                    ->selectRaw('room_id, COUNT(*) as cnt')
                    ->groupBy('room_id')
                    ->pluck('cnt', 'room_id')
                    ->toArray();
            }

            $weekStart = now()->startOfWeek()->toDateString();
            $weekEnd = now()->endOfWeek()->toDateString();
            $weekJournalCounts = $roomIds ? LabUsageJournal::whereIn('room_id', $roomIds)
                ->whereBetween('date', [$weekStart, $weekEnd])
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
                    'damaged_count' => $damagedCounts[$room->id] ?? 0,
                    'active_loans' => $activeLoanCounts[$room->id] ?? 0,
                    'pending_bookings' => $pendingBookingCounts[$room->id] ?? 0,
                    'today_schedule_count' => $todayScheduleCounts[$room->id] ?? 0,
                    'week_usage_count' => $weekJournalCounts[$room->id] ?? 0,
                    'schedule_count' => $scheduleCounts[$room->id] ?? 0,
                ];
            }

            return response()->json([
                'data' => [
                    'summary' => [
                        'total' => $rooms->count(),
                        'by_condition' => $byCondition,
                        'total_damaged' => $damagedCounts->sum(),
                        'total_active_loans' => $activeLoanCounts->sum(),
                        'total_pending_bookings' => $pendingBookingCounts->sum(),
                    ],
                    'labs' => $labs,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to get my labs', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Gagal mengambil data lab Anda'], 500);
        }
    }

    /**
     * Export laporan lab (PDF).
     */
    public function exportLabReport(Request $request, $id = null)
    {
        try {
            $user = $request->user();
            $from = $request->get('from', now()->startOfMonth()->toDateString());
            $to = $request->get('to', now()->toDateString());

            if ($id) {
                $room = Room::with(['building', 'responsibleEmployee', 'institution'])->findOrFail($id);
                if ($room->type !== 'Laboratorium') {
                    return response()->json(['message' => 'Ruangan bukan laboratorium'], 422);
                }
                if (!$user->canViewLab($room)) {
                    return response()->json(['message' => 'Unauthorized'], 403);
                }

                $items = InventoryItem::with('category')->where('room_id', $room->id)->orderBy('name')->get();
                $loans = InventoryLoan::with('item')
                    ->whereHas('item', fn ($q) => $q->where('room_id', $room->id))
                    ->whereBetween('loan_date', [$from, $to])
                    ->orderByDesc('loan_date')
                    ->get();
                $journals = LabUsageJournal::with(['schoolClass', 'subject', 'recorder'])
                    ->where('room_id', $room->id)
                    ->whereBetween('date', [$from, $to])
                    ->orderByDesc('date')
                    ->get();
                $maintenances = InventoryMaintenance::with('item')
                    ->whereHas('item', fn ($q) => $q->where('room_id', $room->id))
                    ->whereBetween('scheduled_date', [$from, $to])
                    ->orderByDesc('scheduled_date')
                    ->get();

                $pdf = DomPDF::loadView('lab.export', [
                    'mode' => 'single',
                    'room' => $room,
                    'institution' => $room->institution,
                    'items' => $items,
                    'loans' => $loans,
                    'journals' => $journals,
                    'maintenances' => $maintenances,
                    'from' => $from,
                    'to' => $to,
                    'printedBy' => $user->name,
                ])->setPaper('a4', 'portrait');

                $filename = 'laporan_lab_' . ($room->code ?: $room->id) . '_' . now()->format('Ymd') . '.pdf';
                return $pdf->stream($filename, ['Attachment' => false]);
            }

            $institutionId = $this->resolveInstitutionId($request);
            if (!$institutionId) {
                return response()->json(['message' => 'Institusi tidak ditemukan'], 403);
            }

            $rooms = Room::with(['building', 'responsibleEmployee'])
                ->where('institution_id', $institutionId)
                ->where('type', 'Laboratorium')
                ->orderBy('name')
                ->get();

            $roomIds = $rooms->pluck('id')->toArray();
            $inventoryCounts = $roomIds
                ? InventoryItem::whereIn('room_id', $roomIds)->selectRaw('room_id, COUNT(*) as cnt')->groupBy('room_id')->pluck('cnt', 'room_id')
                : collect();
            $damagedCounts = $roomIds
                ? InventoryItem::whereIn('room_id', $roomIds)
                    ->where(function ($q) {
                        $q->whereIn('condition', ['Rusak Ringan', 'Rusak Sedang', 'Rusak Berat'])
                            ->orWhereIn('status', ['Rusak', 'Hilang']);
                    })
                    ->selectRaw('room_id, COUNT(*) as cnt')->groupBy('room_id')->pluck('cnt', 'room_id')
                : collect();

            $pdf = DomPDF::loadView('lab.export', [
                'mode' => 'all',
                'rooms' => $rooms,
                'inventoryCounts' => $inventoryCounts,
                'damagedCounts' => $damagedCounts,
                'from' => $from,
                'to' => $to,
                'institution' => Institution::find($institutionId),
                'printedBy' => $user->name,
            ])->setPaper('a4', 'landscape');

            return $pdf->stream('laporan_lab_' . now()->format('Ymd') . '.pdf', ['Attachment' => false]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Lab tidak ditemukan'], 404);
        } catch (\Exception $e) {
            Log::error('Failed to export lab report', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Gagal mengekspor laporan lab'], 500);
        }
    }
}
