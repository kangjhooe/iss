<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\GenerateBulkQrAttendanceRequest;
use App\Http\Requests\ScanQrAttendanceRequest;
use App\Models\Employee;
use App\Models\EmployeeAttendance;
use App\Models\Institution;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\StudentAttendance;
use App\Models\TeachingJournal;
use App\Services\EmployeeAttendanceService;
use App\Services\GeolocationService;
use App\Services\QrCodeService;
use App\Support\InstitutionContext;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class QrAttendanceController extends Controller
{
    public function __construct(
        protected QrCodeService $qrCodeService,
        protected GeolocationService $geolocationService,
        protected EmployeeAttendanceService $employeeAttendanceService
    ) {}

    private function resolveInstitutionId(Request $request): ?int
    {
        return InstitutionContext::resolveForUser(
            $request->user(),
            $request,
            $request->get('institution_id')
        );
    }

    /**
     * Generate QR code untuk siswa.
     */
    public function generateStudentQr(Request $request, Student $student): JsonResponse
    {
        try {
            $institutionId = $this->resolveInstitutionId($request);

            if (!$institutionId || (int) $student->institution_id !== (int) $institutionId) {
                return response()->json(['message' => 'Unauthorized.'], 403);
            }

            if ($student->status !== 'Aktif') {
                return response()->json(['message' => 'QR hanya dapat digenerate untuk siswa aktif.'], 422);
            }

            $student->loadMissing('schoolClass:id,name');
            $qrCodeBase64 = $this->qrCodeService->generateForStudent($student->id, $institutionId);

            return response()->json([
                'message' => 'QR code berhasil digenerate.',
                'data' => [
                    'qr_code' => $qrCodeBase64,
                    'student_id' => $student->id,
                    'student_name' => $student->name,
                    'nis' => $student->nis,
                    'class_name' => $student->schoolClass?->name,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Generate student QR failed', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Gagal generate QR code.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Generate QR code untuk employee (guru/staff).
     */
    public function generateEmployeeQr(Request $request, Employee $employee): JsonResponse
    {
        try {
            $institutionId = $this->resolveInstitutionId($request);

            if (!$institutionId || !InstitutionContext::employeeBelongsToInstitution($employee, $institutionId)) {
                return response()->json(['message' => 'Unauthorized.'], 403);
            }

            if ($employee->status !== 'Aktif') {
                return response()->json(['message' => 'QR hanya dapat digenerate untuk pegawai aktif.'], 422);
            }

            $qrCodeBase64 = $this->qrCodeService->generateForEmployee($employee->id, $institutionId);

            return response()->json([
                'message' => 'QR code berhasil digenerate.',
                'data' => [
                    'qr_code' => $qrCodeBase64,
                    'employee_id' => $employee->id,
                    'employee_name' => $employee->name,
                    'nip' => $employee->nip,
                    'type' => $employee->type,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Generate employee QR failed', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Gagal generate QR code.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Generate QR massal untuk siswa (satu kelas atau daftar ID).
     */
    public function generateStudentBulk(GenerateBulkQrAttendanceRequest $request): JsonResponse
    {
        $institutionId = $this->resolveInstitutionId($request);
        if (!$institutionId) {
            return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
        }

        $classId = $request->filled('class_id') ? (int) $request->class_id : null;
        if ($classId && !$this->classBelongsToInstitution($classId, $institutionId)) {
            return response()->json(['message' => 'Kelas tidak ditemukan.'], 404);
        }

        $studentIds = array_map('intval', $request->input('student_ids', []));
        $cards = $this->qrCodeService->studentCards($institutionId, $classId, $studentIds);

        return response()->json([
            'message' => $cards->isEmpty()
                ? 'Tidak ada siswa aktif yang sesuai.'
                : 'QR code massal berhasil digenerate.',
            'data' => [
                'count' => $cards->count(),
                'class_id' => $classId,
                'class_name' => $classId ? SchoolClass::query()->where('id', $classId)->value('name') : null,
                'cards' => $cards->values(),
            ],
        ]);
    }

    /**
     * Generate QR massal untuk pegawai.
     */
    public function generateEmployeeBulk(GenerateBulkQrAttendanceRequest $request): JsonResponse
    {
        $institutionId = $this->resolveInstitutionId($request);
        if (!$institutionId) {
            return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
        }

        $employeeIds = array_map('intval', $request->input('employee_ids', []));
        $cards = $this->qrCodeService->employeeCards($institutionId, $employeeIds);

        return response()->json([
            'message' => $cards->isEmpty()
                ? 'Tidak ada pegawai aktif yang sesuai.'
                : 'QR code massal berhasil digenerate.',
            'data' => [
                'count' => $cards->count(),
                'cards' => $cards->values(),
            ],
        ]);
    }

    /**
     * Cetak PDF kartu QR siswa.
     */
    public function printStudentPdf(GenerateBulkQrAttendanceRequest $request): Response
    {
        $institutionId = $this->resolveInstitutionId($request);
        if (!$institutionId) {
            return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
        }

        $classId = $request->filled('class_id') ? (int) $request->class_id : null;
        if ($classId && !$this->classBelongsToInstitution($classId, $institutionId)) {
            return response()->json(['message' => 'Kelas tidak ditemukan.'], 404);
        }

        $studentIds = array_map('intval', $request->input('student_ids', []));
        $cards = $this->qrCodeService->studentCards($institutionId, $classId, $studentIds);
        if ($cards->isEmpty()) {
            return response()->json(['message' => 'Tidak ada siswa aktif yang sesuai.'], 422);
        }

        $institution = Institution::query()->find($institutionId);
        $className = $classId ? SchoolClass::query()->where('id', $classId)->value('name') : null;
        $filename = 'qr-absensi-siswa'.($className ? '-'.$this->fileSlug($className) : '').'.pdf';

        return $this->streamQrCardsPdf(
            $institution?->name ?? 'Sekolah',
            $className ? 'Siswa '.$className : 'Siswa',
            $cards->all(),
            $filename
        );
    }

    /**
     * Cetak PDF kartu QR pegawai.
     */
    public function printEmployeePdf(GenerateBulkQrAttendanceRequest $request): Response
    {
        $institutionId = $this->resolveInstitutionId($request);
        if (!$institutionId) {
            return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
        }

        $employeeIds = array_map('intval', $request->input('employee_ids', []));
        $cards = $this->qrCodeService->employeeCards($institutionId, $employeeIds);
        if ($cards->isEmpty()) {
            return response()->json(['message' => 'Tidak ada pegawai aktif yang sesuai.'], 422);
        }

        $institution = Institution::query()->find($institutionId);

        return $this->streamQrCardsPdf(
            $institution?->name ?? 'Sekolah',
            'Pegawai',
            $cards->all(),
            'qr-absensi-pegawai.pdf'
        );
    }

    /**
     * Scan QR code untuk absensi dengan validasi geolocation.
     */
    public function scanQrAttendance(ScanQrAttendanceRequest $request): JsonResponse
    {
        try {
            $institutionId = $this->resolveInstitutionId($request);

            if (!$institutionId) {
                return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
            }

            $qrData = $this->qrCodeService->parseQrData($request->qr_data);
            if (!$qrData) {
                return response()->json([
                    'message' => 'QR code tidak valid. Generate ulang kartu QR absensi.',
                ], 400);
            }

            if ($qrData['institution_id'] !== $institutionId) {
                return response()->json(['message' => 'QR code tidak sesuai dengan institusi Anda.'], 400);
            }

            $institution = Institution::find($institutionId);
            if (!$institution) {
                return response()->json(['message' => 'Institusi tidak ditemukan.'], 404);
            }

            if ($institution->latitude && $institution->longitude) {
                if (!$request->latitude || !$request->longitude) {
                    return response()->json([
                        'message' => 'Koordinat lokasi diperlukan untuk validasi absensi.',
                    ], 400);
                }

                $radius = $institution->location_radius ?? 100;
                $isWithinRadius = $this->geolocationService->isWithinRadius(
                    (float) $request->latitude,
                    (float) $request->longitude,
                    (float) $institution->latitude,
                    (float) $institution->longitude,
                    (int) $radius
                );

                if (!$isWithinRadius) {
                    $distance = $this->geolocationService->calculateDistance(
                        (float) $request->latitude,
                        (float) $request->longitude,
                        (float) $institution->latitude,
                        (float) $institution->longitude
                    );

                    return response()->json([
                        'message' => 'Lokasi Anda berada di luar radius sekolah. Silakan absensi di lokasi sekolah.',
                        'distance' => round($distance, 2),
                        'required_radius' => $radius,
                    ], 400);
                }
            }

            if ($qrData['type'] !== $request->attendance_type) {
                return response()->json([
                    'message' => 'QR code tidak sesuai dengan tipe absensi yang dipilih.',
                    'expected_type' => $request->attendance_type,
                    'qr_type' => $qrData['type'],
                ], 400);
            }

            if ($request->attendance_type === 'student') {
                return $this->processStudentAttendance($qrData, $request, $institutionId);
            }

            return $this->processEmployeeAttendance($qrData, $request, $institutionId);
        } catch (\Exception $e) {
            Log::error('Scan QR attendance failed', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Gagal memproses absensi QR code.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    private function processStudentAttendance(array $qrData, ScanQrAttendanceRequest $request, int $institutionId): JsonResponse
    {
        $studentId = $qrData['id'];
        $teachingJournalId = $request->teaching_journal_id;

        $teachingJournal = TeachingJournal::with(['subject:id,name', 'schoolClass:id,name'])
            ->where('id', $teachingJournalId)
            ->where('institution_id', $institutionId)
            ->first();

        if (!$teachingJournal) {
            return response()->json(['message' => 'Jurnal mengajar tidak ditemukan.'], 404);
        }

        $student = Student::where('id', $studentId)
            ->where('institution_id', $institutionId)
            ->first();

        if (!$student) {
            return response()->json(['message' => 'Siswa tidak ditemukan.'], 404);
        }

        if ($student->status !== 'Aktif') {
            return response()->json(['message' => 'Siswa tidak aktif. Kartu QR tidak dapat digunakan.'], 400);
        }

        if ((int) $student->class_id !== (int) $teachingJournal->class_id) {
            return response()->json([
                'message' => 'Siswa tidak berada di kelas yang sama dengan jurnal mengajar ini.',
            ], 400);
        }

        $existingAttendance = StudentAttendance::where('teaching_journal_id', $teachingJournalId)
            ->where('student_id', $studentId)
            ->first();

        if ($existingAttendance) {
            return response()->json([
                'message' => 'Absensi untuk sesi ini sudah tercatat sebelumnya.',
                'already_recorded' => true,
                'data' => [
                    'attendance_id' => $existingAttendance->id,
                    'student_name' => $student->name,
                    'status' => $existingAttendance->status,
                ],
            ], 200);
        }

        try {
            $attendance = StudentAttendance::create([
                'institution_id' => $institutionId,
                'teaching_journal_id' => $teachingJournalId,
                'student_id' => $studentId,
                'status' => 'hadir',
                'notes' => 'Absensi via QR Code',
            ]);
        } catch (QueryException $e) {
            $existingAttendance = StudentAttendance::where('teaching_journal_id', $teachingJournalId)
                ->where('student_id', $studentId)
                ->first();

            return response()->json([
                'message' => 'Absensi untuk sesi ini sudah tercatat sebelumnya.',
                'already_recorded' => true,
                'data' => [
                    'attendance_id' => $existingAttendance?->id,
                    'student_name' => $student->name,
                    'status' => $existingAttendance?->status,
                ],
            ], 200);
        }

        return response()->json([
            'message' => 'Absensi siswa berhasil dicatat.',
            'already_recorded' => false,
            'data' => [
                'attendance_id' => $attendance->id,
                'student_name' => $student->name,
                'nis' => $student->nis,
                'class' => $teachingJournal->schoolClass->name ?? null,
                'subject' => $teachingJournal->subject->name ?? null,
                'status' => $attendance->status,
            ],
        ], 201);
    }

    private function processEmployeeAttendance(array $qrData, ScanQrAttendanceRequest $request, int $institutionId): JsonResponse
    {
        $employeeId = $qrData['id'];
        $date = $request->date;

        $employee = Employee::where('id', $employeeId)->first();
        if (!$employee || !InstitutionContext::employeeBelongsToInstitution($employee, $institutionId)) {
            return response()->json(['message' => 'Pegawai tidak ditemukan.'], 404);
        }

        if ($employee->status !== 'Aktif') {
            return response()->json(['message' => 'Pegawai tidak aktif. Kartu QR tidak dapat digunakan.'], 400);
        }

        $existingAttendance = EmployeeAttendance::where('institution_id', $institutionId)
            ->where('employee_id', $employeeId)
            ->where('date', $date)
            ->first();

        if ($existingAttendance) {
            return response()->json([
                'message' => 'Absensi untuk tanggal ini sudah tercatat sebelumnya.',
                'already_recorded' => true,
                'data' => [
                    'attendance_id' => $existingAttendance->id,
                    'employee_name' => $employee->name,
                    'status' => $existingAttendance->status,
                    'check_in_time' => $existingAttendance->check_in_time,
                ],
            ], 200);
        }

        $attendance = $this->employeeAttendanceService->upsert(
            $institutionId,
            [
                'employee_id' => $employeeId,
                'date' => $date,
                'status' => 'hadir',
                'check_in_time' => now()->format('H:i'),
                'notes' => 'Absensi via QR Code',
            ]
        );

        return response()->json([
            'message' => 'Absensi pegawai berhasil dicatat.',
            'already_recorded' => false,
            'data' => [
                'attendance_id' => $attendance->id,
                'employee_name' => $employee->name,
                'nip' => $employee->nip,
                'date' => $date,
                'check_in_time' => $attendance->check_in_time,
                'status' => $attendance->status,
            ],
        ], 201);
    }

    private function classBelongsToInstitution(int $classId, int $institutionId): bool
    {
        return SchoolClass::query()
            ->where('id', $classId)
            ->where('institution_id', $institutionId)
            ->exists();
    }

    /**
     * @param  list<array<string, mixed>>  $cards
     */
    private function streamQrCardsPdf(string $institutionName, string $title, array $cards, string $filename): Response
    {
        $pages = array_chunk($cards, 8);
        $pdf = Pdf::loadView('attendance.qr_cards', [
            'institutionName' => $institutionName,
            'title' => $title,
            'pages' => $pages,
        ])->setPaper('a4', 'portrait');

        return $pdf->stream($filename, ['Attachment' => false]);
    }

    private function fileSlug(string $value): string
    {
        $slug = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $value) ?? '');

        return trim($slug, '-') ?: 'kelas';
    }
}
