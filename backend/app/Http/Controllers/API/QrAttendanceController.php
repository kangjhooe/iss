<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\ScanQrAttendanceRequest;
use App\Models\EmployeeAttendance;
use App\Models\Institution;
use App\Models\Student;
use App\Models\Employee;
use App\Models\StudentAttendance;
use App\Models\TeachingJournal;
use App\Services\QrCodeService;
use App\Services\GeolocationService;
use App\Services\EmployeeAttendanceService;
use App\Services\StudentAttendanceService;
use App\Support\InstitutionContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class QrAttendanceController extends Controller
{
    public function __construct(
        protected QrCodeService $qrCodeService,
        protected GeolocationService $geolocationService,
        protected EmployeeAttendanceService $employeeAttendanceService,
        protected StudentAttendanceService $studentAttendanceService
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

            $qrCodeBase64 = $this->qrCodeService->generateForStudent($student->id, $institutionId);

            return response()->json([
                'message' => 'QR code berhasil digenerate.',
                'data' => [
                    'qr_code' => $qrCodeBase64,
                    'student_id' => $student->id,
                    'student_name' => $student->name,
                    'nis' => $student->nis,
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

            $qrCodeBase64 = $this->qrCodeService->generateForEmployee($employee->id, $institutionId);

            return response()->json([
                'message' => 'QR code berhasil digenerate.',
                'data' => [
                    'qr_code' => $qrCodeBase64,
                    'employee_id' => $employee->id,
                    'employee_name' => $employee->name,
                    'nip' => $employee->nip,
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
     * Scan QR code untuk absensi dengan validasi geolocation.
     */
    public function scanQrAttendance(ScanQrAttendanceRequest $request): JsonResponse
    {
        try {
            $institutionId = $this->resolveInstitutionId($request);

            if (!$institutionId) {
                return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
            }

            // Parse QR data
            $qrData = $this->qrCodeService->parseQrData($request->qr_data);
            if (!$qrData) {
                return response()->json(['message' => 'QR code tidak valid.'], 400);
            }

            // Validasi timestamp QR tidak expired
            if (!$this->qrCodeService->isValidTimestamp($qrData['timestamp'])) {
                return response()->json(['message' => 'QR code sudah expired. Silakan generate ulang.'], 400);
            }

            // Validasi institution_id match
            if ($qrData['institution_id'] != $institutionId) {
                return response()->json(['message' => 'QR code tidak sesuai dengan institusi Anda.'], 400);
            }

            // Validasi geolocation (jika koordinat institusi sudah di-set)
            $institution = Institution::find($institutionId);
            if (!$institution) {
                return response()->json(['message' => 'Institusi tidak ditemukan.'], 404);
            }
            
            // Validasi geolocation hanya jika institusi sudah set koordinat
            if ($institution->latitude && $institution->longitude) {
                // Jika koordinat user tidak ada, tolak (kecuali untuk testing)
                if (!$request->latitude || !$request->longitude) {
                    return response()->json([
                        'message' => 'Koordinat lokasi diperlukan untuk validasi absensi.',
                    ], 400);
                }
                
                $radius = $institution->location_radius ?? 100;
                $isWithinRadius = $this->geolocationService->isWithinRadius(
                    $request->latitude,
                    $request->longitude,
                    $institution->latitude,
                    $institution->longitude,
                    $radius
                );

                if (!$isWithinRadius) {
                    $distance = $this->geolocationService->calculateDistance(
                        $request->latitude,
                        $request->longitude,
                        $institution->latitude,
                        $institution->longitude
                    );
                    
                    return response()->json([
                        'message' => 'Lokasi Anda berada di luar radius sekolah. Silakan absensi di lokasi sekolah.',
                        'distance' => round($distance, 2),
                        'required_radius' => $radius,
                    ], 400);
                }
            }

            // Validasi QR code type sesuai dengan attendance_type
            if ($qrData['type'] !== $request->attendance_type) {
                return response()->json([
                    'message' => 'QR code tidak sesuai dengan tipe absensi yang dipilih.',
                    'expected_type' => $request->attendance_type,
                    'qr_type' => $qrData['type'],
                ], 400);
            }

            // Process attendance berdasarkan type
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

    /**
     * Process student attendance dari QR scan.
     */
    private function processStudentAttendance(array $qrData, ScanQrAttendanceRequest $request, int $institutionId): JsonResponse
    {
        $studentId = $qrData['id'];
        $teachingJournalId = $request->teaching_journal_id;

        // Validasi teaching journal exists dulu (sebelum dipakai)
        $teachingJournal = TeachingJournal::with(['subject:id,name', 'schoolClass:id,name'])
            ->where('id', $teachingJournalId)
            ->where('institution_id', $institutionId)
            ->first();

        if (!$teachingJournal) {
            return response()->json(['message' => 'Jurnal mengajar tidak ditemukan.'], 404);
        }

        // Validasi student exists dan dalam institution yang sama
        $student = Student::where('id', $studentId)
            ->where('institution_id', $institutionId)
            ->first();

        if (!$student) {
            return response()->json(['message' => 'Siswa tidak ditemukan.'], 404);
        }

        // Validasi student dalam class yang sama dengan teaching journal
        if ($student->class_id != $teachingJournal->class_id) {
            return response()->json([
                'message' => 'Siswa tidak berada di kelas yang sama dengan jurnal mengajar ini.',
            ], 400);
        }

        // Check jika sudah ada attendance untuk teaching journal ini
        $existingAttendance = StudentAttendance::where('teaching_journal_id', $teachingJournalId)
            ->where('student_id', $studentId)
            ->first();

        if ($existingAttendance) {
            return response()->json([
                'message' => 'Absensi untuk sesi ini sudah tercatat sebelumnya.',
                'data' => [
                    'attendance_id' => $existingAttendance->id,
                    'status' => $existingAttendance->status,
                    'created_at' => $existingAttendance->created_at,
                ],
            ], 200);
        }

        // Create attendance dengan status hadir
        $attendance = StudentAttendance::create([
            'institution_id' => $institutionId,
            'teaching_journal_id' => $teachingJournalId,
            'student_id' => $studentId,
            'status' => 'hadir',
            'notes' => 'Absensi via QR Code',
        ]);

        return response()->json([
            'message' => 'Absensi siswa berhasil dicatat.',
            'data' => [
                'attendance_id' => $attendance->id,
                'student_name' => $student->name,
                'teaching_journal' => [
                    'id' => $teachingJournal->id,
                    'date' => $teachingJournal->journal_date,
                    'subject' => $teachingJournal->subject->name ?? null,
                    'class' => $teachingJournal->schoolClass->name ?? null,
                ],
                'status' => $attendance->status,
            ],
        ], 201);
    }

    /**
     * Process employee attendance dari QR scan.
     */
    private function processEmployeeAttendance(array $qrData, ScanQrAttendanceRequest $request, int $institutionId): JsonResponse
    {
        $employeeId = $qrData['id'];
        $date = $request->date;

        // Validasi employee exists dan dalam institution (induk atau non-induk approved)
        $employee = Employee::where('id', $employeeId)->first();
        if (!$employee || !InstitutionContext::employeeBelongsToInstitution($employee, $institutionId)) {
            return response()->json(['message' => 'Pegawai tidak ditemukan.'], 404);
        }

        // Check jika sudah ada attendance untuk tanggal ini
        $existingAttendance = EmployeeAttendance::where('institution_id', $institutionId)
            ->where('employee_id', $employeeId)
            ->where('date', $date)
            ->first();

        if ($existingAttendance) {
            return response()->json([
                'message' => 'Absensi untuk tanggal ini sudah tercatat sebelumnya.',
                'data' => [
                    'attendance_id' => $existingAttendance->id,
                    'status' => $existingAttendance->status,
                    'check_in_time' => $existingAttendance->check_in_time,
                    'created_at' => $existingAttendance->created_at,
                ],
            ], 200);
        }

        // Create attendance dengan status hadir dan waktu masuk sekarang
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
            'data' => [
                'attendance_id' => $attendance->id,
                'employee_name' => $employee->name,
                'date' => $date,
                'check_in_time' => $attendance->check_in_time,
                'status' => $attendance->status,
            ],
        ], 201);
    }
}
