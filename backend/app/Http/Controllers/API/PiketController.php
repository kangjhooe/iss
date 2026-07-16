<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePiketIncidentRequest;
use App\Http\Requests\StorePiketLogRequest;
use App\Http\Requests\StorePiketScheduleRequest;
use App\Http\Resources\PiketIncidentResource;
use App\Http\Resources\PiketLogResource;
use App\Http\Resources\PiketScheduleResource;
use App\Http\Resources\TeacherViolationResource;
use App\Http\Resources\TeacherViolationTypeResource;
use App\Models\Employee;
use App\Models\Institution;
use App\Http\Resources\ViolationResource;
use App\Http\Resources\ViolationTypeResource;
use App\Models\PiketIncident;
use App\Models\PiketLog;
use App\Models\PiketSchedule;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\TeacherViolationType;
use App\Models\ViolationType;
use App\Services\PiketService;
use App\Services\TeacherPointService;
use App\Services\ViolationService;
use App\Support\PiketAccess;
use Barryvdh\DomPDF\Facade\Pdf as DomPDF;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class PiketController extends Controller
{
    public function __construct(
        private PiketService $service,
        private ViolationService $violationService,
        private TeacherPointService $teacherPointService
    ) {}

    protected function institutionId(Request $request): ?int
    {
        return $this->service->resolveInstitutionId($request->user(), $request->get('institution_id'));
    }

    /**
     * @return int|JsonResponse
     */
    protected function requireInstitution(Request $request)
    {
        $id = $this->institutionId($request);
        if (!$id) {
            return response()->json(['message' => 'Institusi tidak ditemukan'], 403);
        }

        return (int) $id;
    }

    protected function failIfNoInstitution(int|JsonResponse $institutionId): ?JsonResponse
    {
        return $institutionId instanceof JsonResponse ? $institutionId : null;
    }

    public function employeesLite(Request $request)
    {
        $institutionId = $this->requireInstitution($request);
        if ($fail = $this->failIfNoInstitution($institutionId)) {
            return $fail;
        }

        $q = trim((string) $request->get('q', ''));
        $query = Employee::query()
            ->where(function ($w) use ($institutionId) {
                $w->where('institution_id', $institutionId)
                    ->orWhereHas('assignments', function ($assignmentQuery) use ($institutionId) {
                        $assignmentQuery->where('institution_id', $institutionId)
                            ->where('status', 'approved');
                    });
            })
            ->where(function ($w) {
                $w->where('status', 'Aktif')->orWhereNull('status');
            })
            ->orderBy('name')
            ->limit(300);

        if ($q !== '') {
            $query->where(function ($w) use ($q) {
                $w->where('name', 'like', "%{$q}%")
                    ->orWhere('nip', 'like', "%{$q}%");
            });
        }

        return response()->json([
            'data' => $query->get(['id', 'name', 'nip']),
        ]);
    }

    public function studentsLite(Request $request)
    {
        $institutionId = $this->requireInstitution($request);
        if ($fail = $this->failIfNoInstitution($institutionId)) {
            return $fail;
        }

        $q = trim((string) $request->get('q', ''));
        $classId = $request->get('class_id');

        $query = Student::query()
            ->leftJoin('class', 'student.class_id', '=', 'class.id')
            ->where('student.institution_id', $institutionId)
            ->where(function ($w) {
                $w->where('student.status', 'Aktif')->orWhereNull('student.status');
            })
            ->orderBy('student.name')
            ->limit(100)
            ->select([
                'student.id',
                'student.name',
                'student.nis',
                'student.nisn',
                'student.class_id',
                'class.name as class_name',
            ]);

        if ($classId) {
            $query->where('student.class_id', (int) $classId);
        }

        if ($q !== '') {
            $query->where(function ($w) use ($q) {
                $w->where('student.name', 'like', "%{$q}%")
                    ->orWhere('student.nis', 'like', "%{$q}%")
                    ->orWhere('student.nisn', 'like', "%{$q}%");
            });
        }

        return response()->json([
            'data' => $query->get(),
        ]);
    }

    public function classesLite(Request $request)
    {
        $institutionId = $this->requireInstitution($request);
        if ($fail = $this->failIfNoInstitution($institutionId)) {
            return $fail;
        }

        $institution = Institution::find($institutionId);
        $query = SchoolClass::query()
            ->where('institution_id', $institutionId)
            ->orderBy('grade')
            ->orderBy('name');

        if ($institution?->active_academic_year_id) {
            $query->where('academic_year_id', $institution->active_academic_year_id);
        }

        return response()->json([
            'data' => $query->get(['id', 'name', 'grade']),
        ]);
    }

    // ── Dashboard / Today ──────────────────────────────────────────

    public function dashboard(Request $request)
    {
        try {
            $institutionId = $this->requireInstitution($request);
            if ($fail = $this->failIfNoInstitution($institutionId)) {
                return $fail;
            }

            return response()->json([
                'data' => $this->service->dashboard($institutionId, $request->get('date')),
                'meta' => [
                    'can_manage' => PiketAccess::canManage($request->user()),
                    'days' => PiketSchedule::DAYS,
                    'shifts' => PiketSchedule::SHIFTS,
                    'incident_types' => PiketIncident::TYPES,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Piket dashboard failed', ['error' => $e->getMessage()]);

            return response()->json(['message' => 'Gagal memuat dashboard piket'], 500);
        }
    }

    public function today(Request $request)
    {
        try {
            $institutionId = $this->requireInstitution($request);
            if ($fail = $this->failIfNoInstitution($institutionId)) {
                return $fail;
            }

            $roster = $this->service->todayRoster($institutionId, $request->get('date'));
            $employee = PiketAccess::employeeFor($request->user());
            $mySchedule = null;
            if ($employee) {
                $mySchedule = collect($roster['schedules'])->firstWhere('employee_id', $employee->id);
            }

            return response()->json([
                'data' => array_merge($roster, [
                    'is_on_duty' => (bool) $mySchedule,
                    'my_schedule' => $mySchedule,
                ]),
            ]);
        } catch (\Exception $e) {
            Log::error('Piket today failed', ['error' => $e->getMessage()]);

            return response()->json(['message' => 'Gagal memuat roster piket'], 500);
        }
    }

    // ── Settings ───────────────────────────────────────────────────

    public function settings(Request $request)
    {
        $institutionId = $this->requireInstitution($request);
        if ($fail = $this->failIfNoInstitution($institutionId)) {
            return $fail;
        }

        $settings = $this->service->getSettings($institutionId);

        return response()->json([
            'data' => [
                'institution_id' => $settings->institution_id,
                'teacher_late_threshold' => Carbon::parse($settings->teacher_late_threshold)->format('H:i'),
                'include_saturday' => $settings->include_saturday,
                'empty_class_grace_minutes' => $settings->empty_class_grace_minutes,
                'notes' => $settings->notes,
            ],
        ]);
    }

    public function updateSettings(Request $request)
    {
        if (!PiketAccess::canManage($request->user())) {
            return response()->json(['message' => 'Tidak memiliki akses mengelola pengaturan piket'], 403);
        }

        $institutionId = $this->requireInstitution($request);
        if ($fail = $this->failIfNoInstitution($institutionId)) {
            return $fail;
        }

        $data = $request->validate([
            'teacher_late_threshold' => 'required|date_format:H:i',
            'include_saturday' => 'boolean',
            'empty_class_grace_minutes' => 'integer|min:0|max:120',
            'notes' => 'nullable|string|max:2000',
        ]);

        $settings = $this->service->updateSettings($institutionId, $data);

        return response()->json([
            'message' => 'Pengaturan piket disimpan',
            'data' => [
                'institution_id' => $settings->institution_id,
                'teacher_late_threshold' => Carbon::parse($settings->teacher_late_threshold)->format('H:i'),
                'include_saturday' => $settings->include_saturday,
                'empty_class_grace_minutes' => $settings->empty_class_grace_minutes,
                'notes' => $settings->notes,
            ],
        ]);
    }

    // ── Schedules ──────────────────────────────────────────────────

    public function schedulesIndex(Request $request)
    {
        try {
            $institutionId = $this->requireInstitution($request);
            if ($fail = $this->failIfNoInstitution($institutionId)) {
                return $fail;
            }

            $query = PiketSchedule::with('employee:id,name,nip')
                ->forInstitution($institutionId);

            if ($request->filled('day_of_week')) {
                $query->where('day_of_week', $request->day_of_week);
            }
            if ($request->filled('employee_id')) {
                $query->where('employee_id', $request->employee_id);
            }

            $items = $query->orderBy('day_of_week')->orderBy('shift')->get();

            return response()->json([
                'data' => PiketScheduleResource::collection($items),
                'meta' => [
                    'days' => PiketSchedule::DAYS,
                    'shifts' => PiketSchedule::SHIFTS,
                    'can_manage' => PiketAccess::canManage($request->user()),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Piket schedules index failed', ['error' => $e->getMessage()]);

            return response()->json(['message' => 'Gagal mengambil jadwal piket'], 500);
        }
    }

    public function schedulesStore(StorePiketScheduleRequest $request)
    {
        if (!PiketAccess::canManage($request->user())) {
            return response()->json(['message' => 'Tidak memiliki akses mengatur jadwal piket'], 403);
        }

        try {
            $institutionId = $this->requireInstitution($request);
            if ($fail = $this->failIfNoInstitution($institutionId)) {
                return $fail;
            }

            $data = $request->validated();
            $employee = Employee::find($data['employee_id']);
            if (!$employee || !\App\Support\InstitutionContext::employeeBelongsToInstitution($employee, $institutionId)) {
                return response()->json(['message' => 'Guru tidak ditemukan di institusi ini'], 422);
            }

            $days = collect($data['days_of_week'] ?? []);
            if ($days->isEmpty() && isset($data['day_of_week'])) {
                $days = collect([(int) $data['day_of_week']]);
            }
            $days = $days->map(fn ($d) => (int) $d)->unique()->sort()->values();

            if ($days->isEmpty()) {
                return response()->json(['message' => 'Pilih minimal satu hari piket'], 422);
            }

            $institution = Institution::find($institutionId);
            $base = [
                'institution_id' => $institutionId,
                'employee_id' => (int) $data['employee_id'],
                'shift' => $data['shift'],
                'start_time' => $data['start_time'] ?? null,
                'end_time' => $data['end_time'] ?? null,
                'notes' => $data['notes'] ?? null,
                'academic_year_id' => $data['academic_year_id'] ?? $institution?->active_academic_year_id,
                'semester_id' => $data['semester_id'] ?? $institution?->active_semester_id,
            ];

            $created = [];
            $skipped = [];

            foreach ($days as $day) {
                $exists = PiketSchedule::forInstitution($institutionId)
                    ->where('day_of_week', $day)
                    ->where('shift', $base['shift'])
                    ->where('employee_id', $base['employee_id'])
                    ->exists();

                if ($exists) {
                    $skipped[] = PiketSchedule::DAYS[$day] ?? (string) $day;
                    continue;
                }

                $schedule = PiketSchedule::create(array_merge($base, ['day_of_week' => $day]));
                $schedule->load('employee:id,name,nip');
                $created[] = $schedule;
            }

            if (empty($created)) {
                return response()->json([
                    'message' => 'Guru sudah dijadwalkan pada hari dan shift yang dipilih'
                        .($skipped ? ' ('.implode(', ', $skipped).')' : ''),
                ], 422);
            }

            PiketAccess::ensureEmployeeAccess($employee);

            $msg = count($created).' jadwal piket ditambahkan';
            if ($skipped) {
                $msg .= '. Dilewati (sudah ada): '.implode(', ', $skipped);
            }

            return response()->json([
                'message' => $msg,
                'data' => PiketScheduleResource::collection(collect($created)),
                'meta' => [
                    'created_count' => count($created),
                    'skipped_days' => $skipped,
                ],
            ], 201);
        } catch (\Exception $e) {
            Log::error('Piket schedule store failed', ['error' => $e->getMessage()]);

            return response()->json(['message' => 'Gagal menambah jadwal piket'], 500);
        }
    }

    public function schedulesUpdate(StorePiketScheduleRequest $request, PiketSchedule $piketSchedule)
    {
        if (!PiketAccess::canManage($request->user())) {
            return response()->json(['message' => 'Tidak memiliki akses mengatur jadwal piket'], 403);
        }

        $institutionId = $this->requireInstitution($request);
        if ($fail = $this->failIfNoInstitution($institutionId)) {
            return $fail;
        }

        if ((int) $piketSchedule->institution_id !== $institutionId) {
            return response()->json(['message' => 'Jadwal tidak ditemukan'], 404);
        }

        $data = $request->validated();
        $day = (int) ($data['day_of_week'] ?? ($data['days_of_week'][0] ?? $piketSchedule->day_of_week));
        unset($data['days_of_week']);
        $data['day_of_week'] = $day;

        if (isset($data['employee_id'])) {
            $employee = Employee::find($data['employee_id']);
            if (!$employee || !\App\Support\InstitutionContext::employeeBelongsToInstitution($employee, $institutionId)) {
                return response()->json(['message' => 'Guru tidak ditemukan di institusi ini'], 422);
            }
        }

        $dup = PiketSchedule::forInstitution($institutionId)
            ->where('day_of_week', $data['day_of_week'])
            ->where('shift', $data['shift'])
            ->where('employee_id', $data['employee_id'])
            ->where('id', '!=', $piketSchedule->id)
            ->exists();

        if ($dup) {
            return response()->json(['message' => 'Guru sudah dijadwalkan pada hari dan shift tersebut'], 422);
        }

        $piketSchedule->update($data);
        $piketSchedule->load('employee:id,name,nip');

        if ($piketSchedule->employee) {
            PiketAccess::ensureEmployeeAccess($piketSchedule->employee);
        }

        return response()->json([
            'message' => 'Jadwal piket diperbarui',
            'data' => new PiketScheduleResource($piketSchedule),
        ]);
    }

    public function schedulesDestroy(Request $request, PiketSchedule $piketSchedule)
    {
        if (!PiketAccess::canManage($request->user())) {
            return response()->json(['message' => 'Tidak memiliki akses mengatur jadwal piket'], 403);
        }

        $institutionId = $this->requireInstitution($request);
        if ($fail = $this->failIfNoInstitution($institutionId)) {
            return $fail;
        }

        if ((int) $piketSchedule->institution_id !== $institutionId) {
            return response()->json(['message' => 'Jadwal tidak ditemukan'], 404);
        }

        $piketSchedule->delete();

        return response()->json(['message' => 'Jadwal piket dihapus']);
    }

    // ── Logs ───────────────────────────────────────────────────────

    public function logsIndex(Request $request)
    {
        try {
            $institutionId = $this->requireInstitution($request);
            if ($fail = $this->failIfNoInstitution($institutionId)) {
                return $fail;
            }

            $query = PiketLog::with(['employee:id,name,nip'])
                ->withCount('incidents')
                ->forInstitution($institutionId);

            if ($request->filled('date_from')) {
                $query->whereDate('duty_date', '>=', $request->date_from);
            }
            if ($request->filled('date_to')) {
                $query->whereDate('duty_date', '<=', $request->date_to);
            }
            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }
            if ($request->filled('employee_id')) {
                $query->where('employee_id', $request->employee_id);
            }

            $perPage = min((int) $request->get('per_page', 20), 100);
            $logs = $query->orderByDesc('duty_date')->paginate($perPage);

            return PiketLogResource::collection($logs)->additional([
                'meta_extra' => [
                    'can_manage' => PiketAccess::canManage($request->user()),
                    'statuses' => PiketLog::STATUSES,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Piket logs index failed', ['error' => $e->getMessage()]);

            return response()->json(['message' => 'Gagal mengambil log piket'], 500);
        }
    }

    public function logsStore(StorePiketLogRequest $request)
    {
        try {
            $institutionId = $this->requireInstitution($request);
            if ($fail = $this->failIfNoInstitution($institutionId)) {
                return $fail;
            }

            $user = $request->user();
            $employee = PiketAccess::employeeFor($user);
            $data = $request->validated();
            $employeeId = $data['employee_id'] ?? $employee?->id;

            if (!$employeeId) {
                return response()->json(['message' => 'Profil pegawai tidak ditemukan'], 422);
            }

            if (!PiketAccess::canManage($user) && (int) $employeeId !== (int) $employee?->id) {
                return response()->json(['message' => 'Anda hanya dapat membuat log untuk diri sendiri'], 403);
            }

            $existing = PiketLog::forInstitution($institutionId)
                ->whereDate('duty_date', $data['duty_date'])
                ->where('employee_id', $employeeId)
                ->first();

            if ($existing) {
                return response()->json([
                    'message' => 'Log piket untuk tanggal ini sudah ada',
                    'data' => new PiketLogResource($existing->load('employee:id,name,nip')),
                ], 422);
            }

            $log = PiketLog::create([
                'institution_id' => $institutionId,
                'duty_date' => $data['duty_date'],
                'employee_id' => $employeeId,
                'piket_schedule_id' => $data['piket_schedule_id'] ?? null,
                'summary' => $data['summary'] ?? null,
                'handoff_notes' => $data['handoff_notes'] ?? null,
                'status' => $data['status'] ?? PiketLog::STATUS_DRAFT,
                'created_by' => $user->id,
            ]);

            $log->load('employee:id,name,nip');

            return response()->json([
                'message' => 'Log piket disimpan',
                'data' => new PiketLogResource($log),
            ], 201);
        } catch (\Exception $e) {
            Log::error('Piket log store failed', ['error' => $e->getMessage()]);

            return response()->json(['message' => 'Gagal menyimpan log piket'], 500);
        }
    }

    public function logsUpdate(StorePiketLogRequest $request, PiketLog $piketLog)
    {
        $institutionId = $this->requireInstitution($request);
        if ($fail = $this->failIfNoInstitution($institutionId)) {
            return $fail;
        }

        if ((int) $piketLog->institution_id !== $institutionId) {
            return response()->json(['message' => 'Log tidak ditemukan'], 404);
        }

        $user = $request->user();
        $employee = PiketAccess::employeeFor($user);
        $canManage = PiketAccess::canManage($user);

        if (!$canManage && (int) $piketLog->employee_id !== (int) $employee?->id) {
            return response()->json(['message' => 'Tidak dapat mengubah log milik orang lain'], 403);
        }

        if (!$canManage && $piketLog->status === PiketLog::STATUS_REVIEWED) {
            return response()->json(['message' => 'Log yang sudah direview tidak dapat diubah'], 422);
        }

        $data = $request->validated();
        unset($data['employee_id'], $data['duty_date']);

        if (isset($data['status']) && $data['status'] === PiketLog::STATUS_REVIEWED && !$canManage) {
            unset($data['status']);
        }

        $piketLog->update($data);
        $piketLog->load('employee:id,name,nip');

        return response()->json([
            'message' => 'Log piket diperbarui',
            'data' => new PiketLogResource($piketLog),
        ]);
    }

    public function logsReview(Request $request, PiketLog $piketLog)
    {
        if (!PiketAccess::canManage($request->user())) {
            return response()->json(['message' => 'Tidak memiliki akses mereview log piket'], 403);
        }

        $institutionId = $this->requireInstitution($request);
        if ($fail = $this->failIfNoInstitution($institutionId)) {
            return $fail;
        }

        if ((int) $piketLog->institution_id !== $institutionId) {
            return response()->json(['message' => 'Log tidak ditemukan'], 404);
        }

        $data = $request->validate([
            'review_notes' => 'nullable|string|max:2000',
        ]);

        $piketLog->update([
            'status' => PiketLog::STATUS_REVIEWED,
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
            'review_notes' => $data['review_notes'] ?? null,
        ]);

        $piketLog->load('employee:id,name,nip');

        return response()->json([
            'message' => 'Log piket direview',
            'data' => new PiketLogResource($piketLog),
        ]);
    }

    public function logsDestroy(Request $request, PiketLog $piketLog)
    {
        $institutionId = $this->requireInstitution($request);
        if ($fail = $this->failIfNoInstitution($institutionId)) {
            return $fail;
        }

        if ((int) $piketLog->institution_id !== $institutionId) {
            return response()->json(['message' => 'Log tidak ditemukan'], 404);
        }

        $user = $request->user();
        $employee = PiketAccess::employeeFor($user);
        $canManage = PiketAccess::canManage($user);

        if (!$canManage && (int) $piketLog->employee_id !== (int) $employee?->id) {
            return response()->json(['message' => 'Tidak dapat menghapus log milik orang lain'], 403);
        }

        if (!$canManage && $piketLog->status !== PiketLog::STATUS_DRAFT) {
            return response()->json(['message' => 'Hanya log draft yang dapat dihapus'], 422);
        }

        $piketLog->delete();

        return response()->json(['message' => 'Log piket dihapus']);
    }

    // ── Incidents / Monitoring ─────────────────────────────────────

    public function incidentsIndex(Request $request)
    {
        try {
            $institutionId = $this->requireInstitution($request);
            if ($fail = $this->failIfNoInstitution($institutionId)) {
                return $fail;
            }

            $query = PiketIncident::with([
                'employee:id,name,nip',
                'student:id,name,nis',
                'schoolClass:id,name,grade',
                'subject:id,name',
                'violation:id,piket_incident_id,status,violation_type_id,reported_by',
                'violation.violationType:id,name,point_weight',
                'teacherViolation:id,piket_incident_id,status,violation_type_id,point_value,reported_by',
                'teacherViolation.violationType:id,name,point_weight,code',
            ])->forInstitution($institutionId);

            if ($request->filled('date')) {
                $query->whereDate('incident_date', $request->date);
            }
            if ($request->filled('date_from')) {
                $query->whereDate('incident_date', '>=', $request->date_from);
            }
            if ($request->filled('date_to')) {
                $query->whereDate('incident_date', '<=', $request->date_to);
            }
            if ($request->filled('incident_type')) {
                $query->where('incident_type', $request->incident_type);
            }
            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            $perPage = min((int) $request->get('per_page', 50), 100);
            $items = $query->orderByDesc('incident_date')->orderByDesc('id')->paginate($perPage);

            return PiketIncidentResource::collection($items)->additional([
                'meta_extra' => [
                    'types' => PiketIncident::TYPES,
                    'statuses' => PiketIncident::STATUSES,
                    'can_manage' => PiketAccess::canManage($request->user()),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Piket incidents index failed', ['error' => $e->getMessage()]);

            return response()->json(['message' => 'Gagal mengambil data monitoring'], 500);
        }
    }

    public function incidentsStore(StorePiketIncidentRequest $request)
    {
        try {
            $institutionId = $this->requireInstitution($request);
            if ($fail = $this->failIfNoInstitution($institutionId)) {
                return $fail;
            }

            $data = $request->validated();
            $data['institution_id'] = $institutionId;
            $data['source'] = 'manual';
            $data['status'] = $data['status'] ?? PiketIncident::STATUS_OPEN;
            $data['created_by'] = $request->user()->id;

            $incident = PiketIncident::create($data);
            $incident->load([
                'employee:id,name,nip',
                'student:id,name,nis',
                'schoolClass:id,name,grade',
                'subject:id,name',
                'violation',
                'teacherViolation',
            ]);

            return response()->json([
                'message' => 'Insiden dicatat',
                'data' => new PiketIncidentResource($incident),
            ], 201);
        } catch (\Exception $e) {
            Log::error('Piket incident store failed', ['error' => $e->getMessage()]);

            return response()->json(['message' => 'Gagal mencatat insiden'], 500);
        }
    }

    public function incidentsUpdate(Request $request, PiketIncident $piketIncident)
    {
        $institutionId = $this->requireInstitution($request);
        if ($fail = $this->failIfNoInstitution($institutionId)) {
            return $fail;
        }

        if ((int) $piketIncident->institution_id !== $institutionId) {
            return response()->json(['message' => 'Insiden tidak ditemukan'], 404);
        }

        $data = $request->validate([
            'incident_type' => 'sometimes|in:kelas_kosong,terlambat_guru,terlambat_siswa,lainnya',
            'period' => 'nullable|integer|between:1,20',
            'class_id' => 'nullable|integer|exists:class,id',
            'subject_id' => 'nullable|integer|exists:subjects,id',
            'employee_id' => 'nullable|integer|exists:employee,id',
            'student_id' => 'nullable|integer|exists:student,id',
            'detected_at' => 'nullable|date_format:H:i',
            'minutes_late' => 'nullable|integer|min:0|max:600',
            'description' => 'nullable|string|max:5000',
            'status' => ['sometimes', Rule::in(array_keys(PiketIncident::STATUSES))],
            'piket_log_id' => 'nullable|integer|exists:piket_logs,id',
        ]);

        if (isset($data['status']) && in_array($data['status'], [PiketIncident::STATUS_RESOLVED, PiketIncident::STATUS_DISMISSED], true)) {
            $data['resolved_by'] = $request->user()->id;
            $data['resolved_at'] = now();
        }

        $piketIncident->update($data);
        $piketIncident->load([
            'employee:id,name,nip',
            'student:id,name,nis',
            'schoolClass:id,name,grade',
            'subject:id,name',
            'violation.violationType:id,name,point_weight',
            'teacherViolation.violationType:id,name,point_weight,code',
        ]);

        return response()->json([
            'message' => 'Insiden diperbarui',
            'data' => new PiketIncidentResource($piketIncident),
        ]);
    }

    public function incidentsDestroy(Request $request, PiketIncident $piketIncident)
    {
        $institutionId = $this->requireInstitution($request);
        if ($fail = $this->failIfNoInstitution($institutionId)) {
            return $fail;
        }

        if ((int) $piketIncident->institution_id !== $institutionId) {
            return response()->json(['message' => 'Insiden tidak ditemukan'], 404);
        }

        if (!PiketAccess::canManage($request->user()) && $piketIncident->source === 'auto') {
            return response()->json(['message' => 'Insiden otomatis hanya dapat dihapus oleh pengelola'], 403);
        }

        $piketIncident->delete();

        return response()->json(['message' => 'Insiden dihapus']);
    }

    /**
     * Jenis pelanggaran aktif (untuk form ajukan ke BK).
     */
    public function violationTypesActive(Request $request)
    {
        $institutionId = $this->requireInstitution($request);
        if ($fail = $this->failIfNoInstitution($institutionId)) {
            return $fail;
        }

        $types = ViolationType::forInstitution($institutionId)
            ->active()
            ->orderBy('category')
            ->orderBy('name')
            ->get();

        return ViolationTypeResource::collection($types);
    }

    /**
     * Jenis pelanggaran guru aktif (untuk ajukan poin minus ke KS).
     */
    public function teacherViolationTypesActive(Request $request)
    {
        $institutionId = $this->requireInstitution($request);
        if ($fail = $this->failIfNoInstitution($institutionId)) {
            return $fail;
        }

        $this->teacherPointService->ensureDefaultViolationTypes($institutionId);

        $types = TeacherViolationType::forInstitution($institutionId)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return TeacherViolationTypeResource::collection($types);
    }

    /**
     * Guru piket mengajukan insiden siswa sebagai usulan pelanggaran ke BK.
     */
    public function proposeViolation(Request $request, PiketIncident $piketIncident)
    {
        try {
            $institutionId = $this->requireInstitution($request);
            if ($fail = $this->failIfNoInstitution($institutionId)) {
                return $fail;
            }

            if ((int) $piketIncident->institution_id !== $institutionId) {
                return response()->json(['message' => 'Insiden tidak ditemukan'], 404);
            }

            $data = $request->validate([
                'violation_type_id' => 'required|integer|exists:violation_types,id',
                'student_id' => 'nullable|integer|exists:student,id',
                'violation_date' => 'nullable|date',
                'sanction' => 'nullable|string|max:255',
                'description' => 'nullable|string|max:5000',
            ]);

            $violation = $this->violationService->proposeFromPiketIncident(
                $piketIncident,
                $data,
                $request->user()
            );

            return response()->json([
                'message' => 'Usulan pelanggaran dikirim ke BK',
                'data' => new ViolationResource($violation),
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Jenis pelanggaran atau siswa tidak ditemukan'], 404);
        } catch (\Exception $e) {
            Log::error('Piket propose violation failed', ['error' => $e->getMessage()]);

            return response()->json(['message' => 'Gagal mengajukan pelanggaran ke BK'], 500);
        }
    }

    /**
     * Guru piket mengajukan insiden guru sebagai usulan poin minus ke Kepala Sekolah.
     */
    public function proposeTeacherViolation(Request $request, PiketIncident $piketIncident)
    {
        try {
            $institutionId = $this->requireInstitution($request);
            if ($fail = $this->failIfNoInstitution($institutionId)) {
                return $fail;
            }

            if ((int) $piketIncident->institution_id !== $institutionId) {
                return response()->json(['message' => 'Insiden tidak ditemukan'], 404);
            }

            $data = $request->validate([
                'violation_type_id' => 'required|integer|exists:teacher_violation_types,id',
                'employee_id' => 'nullable|integer|exists:employee,id',
                'violation_date' => 'nullable|date',
                'point_value' => 'nullable|integer|min:1|max:1000',
                'sanction' => 'nullable|string|max:255',
                'notes' => 'nullable|string|max:5000',
            ]);

            $violation = $this->teacherPointService->proposeFromPiketIncident(
                $piketIncident,
                $data,
                $request->user()
            );

            return response()->json([
                'message' => 'Usulan pelanggaran guru dikirim ke Kepala Sekolah',
                'data' => new TeacherViolationResource($violation),
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Jenis pelanggaran atau guru tidak ditemukan'], 404);
        } catch (\Exception $e) {
            Log::error('Piket propose teacher violation failed', ['error' => $e->getMessage()]);

            return response()->json(['message' => 'Gagal mengajukan pelanggaran ke Kepala Sekolah'], 500);
        }
    }

    public function scanEmptyClasses(Request $request)
    {
        $institutionId = $this->requireInstitution($request);
        if ($fail = $this->failIfNoInstitution($institutionId)) {
            return $fail;
        }

        $data = $request->validate([
            'date' => 'nullable|date',
        ]);
        $date = $data['date'] ?? now()->toDateString();

        try {
            $created = $this->service->scanEmptyClasses($institutionId, $date, $request->user()->id);

            return response()->json([
                'message' => $created->isEmpty()
                    ? 'Tidak ada kelas kosong baru terdeteksi'
                    : $created->count().' kelas kosong tercatat',
                'data' => PiketIncidentResource::collection($created),
                'count' => $created->count(),
            ]);
        } catch (\Exception $e) {
            Log::error('Piket scan empty classes failed', ['error' => $e->getMessage()]);

            return response()->json(['message' => 'Gagal memindai kelas kosong'], 500);
        }
    }

    public function scanTeacherLateness(Request $request)
    {
        $institutionId = $this->requireInstitution($request);
        if ($fail = $this->failIfNoInstitution($institutionId)) {
            return $fail;
        }

        $data = $request->validate([
            'date' => 'nullable|date',
        ]);
        $date = $data['date'] ?? now()->toDateString();

        try {
            $created = $this->service->scanTeacherLateness($institutionId, $date, $request->user()->id);

            return response()->json([
                'message' => $created->isEmpty()
                    ? 'Tidak ada keterlambatan guru baru terdeteksi'
                    : $created->count().' keterlambatan guru tercatat',
                'data' => PiketIncidentResource::collection($created),
                'count' => $created->count(),
            ]);
        } catch (\Exception $e) {
            Log::error('Piket scan teacher lateness failed', ['error' => $e->getMessage()]);

            return response()->json(['message' => 'Gagal memindai keterlambatan guru'], 500);
        }
    }

    public function scanAll(Request $request)
    {
        $institutionId = $this->requireInstitution($request);
        if ($fail = $this->failIfNoInstitution($institutionId)) {
            return $fail;
        }

        $data = $request->validate([
            'date' => 'nullable|date',
        ]);
        $date = $data['date'] ?? now()->toDateString();
        $userId = $request->user()->id;

        try {
            $empty = $this->service->scanEmptyClasses($institutionId, $date, $userId);
            $late = $this->service->scanTeacherLateness($institutionId, $date, $userId);

            return response()->json([
                'message' => 'Pemindaian selesai',
                'data' => [
                    'kelas_kosong' => PiketIncidentResource::collection($empty),
                    'terlambat_guru' => PiketIncidentResource::collection($late),
                ],
                'counts' => [
                    'kelas_kosong' => $empty->count(),
                    'terlambat_guru' => $late->count(),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Piket scan all failed', ['error' => $e->getMessage()]);

            return response()->json(['message' => 'Gagal menjalankan pemindaian'], 500);
        }
    }

    // ── Weekly PDF ─────────────────────────────────────────────────

    public function weeklyReport(Request $request)
    {
        try {
            $institutionId = $this->requireInstitution($request);
            if ($fail = $this->failIfNoInstitution($institutionId)) {
                return $fail;
            }

            $data = $request->validate([
                'week_start' => 'nullable|date',
            ]);
            $weekStart = $data['week_start'] ?? now()->startOfWeek(Carbon::MONDAY)->toDateString();

            $report = $this->service->weeklyReportData($institutionId, $weekStart);

            $pdf = DomPDF::loadView('piket.weekly_report', $report)
                ->setPaper('a4', 'portrait');

            $filename = 'laporan_piket_'.$report['week_start'].'_'.$report['week_end'].'.pdf';

            if ($request->boolean('stream')) {
                return $pdf->stream($filename, ['Attachment' => false]);
            }

            return $pdf->download($filename);
        } catch (\Exception $e) {
            Log::error('Piket weekly report failed', ['error' => $e->getMessage()]);

            return response()->json(['message' => 'Gagal membuat laporan mingguan'], 500);
        }
    }
}
