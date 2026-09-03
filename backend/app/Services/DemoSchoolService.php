<?php

namespace App\Services;

use App\Models\AcademicCalendarEvent;
use App\Models\AcademicYear;
use App\Models\Achievement;
use App\Models\AchievementType;
use App\Models\AdditionalDuty;
use App\Models\AlumniDestination;
use App\Models\BankSoal;
use App\Models\BkkApplication;
use App\Models\BkkVacancy;
use App\Models\Building;
use App\Models\Correspondence;
use App\Models\CorrespondenceCategory;
use App\Models\CorrespondenceDisposition;
use App\Models\CorrespondenceHistory;
use App\Models\CounselingSession;
use App\Models\CounselingType;
use App\Models\DigitalArchive;
use App\Models\DigitalArchiveCategory;
use App\Models\DocumentPickup;
use App\Models\Employee;
use App\Models\EmployeeAttendance;
use App\Models\EmployeeDecree;
use App\Models\EmployeeLeaveRequest;
use App\Models\EmployeeStructuralPosition;
use App\Models\Exam;
use App\Models\ExamParticipant;
use App\Models\ExamQuestion;
use App\Models\ExamSession;
use App\Models\Extracurricular;
use App\Models\ExtracurricularAttendance;
use App\Models\ExtracurricularGrade;
use App\Models\ExtracurricularSession;
use App\Models\ExtracurricularStudent;
use App\Models\FinanceFeeType;
use App\Models\FinanceInvoice;
use App\Models\FinancePayment;
use App\Models\Grade;
use App\Models\GuestVisit;
use App\Models\IndustryPartner;
use App\Models\Institution;
use App\Models\InventoryCategory;
use App\Models\InventoryItem;
use App\Models\InventoryLoan;
use App\Models\InventoryMaintenance;
use App\Models\InventoryStockOpname;
use App\Models\InventoryStockOpnameLine;
use App\Models\InventoryTransaction;
use App\Support\InventoryCatalog;
use App\Models\LabBooking;
use App\Models\Land;
use App\Models\LessonSchedule;
use App\Models\LessonScheduleTemplate;
use App\Models\LibraryBook;
use App\Models\LibraryBookCategory;
use App\Models\LibraryBookCopy;
use App\Models\LibraryLoan;
use App\Models\ParentLink;
use App\Models\PayrollComponent;
use App\Models\PayrollEmployeeProfile;
use App\Models\PayrollPeriod;
use App\Models\PayrollPositionAllowance;
use App\Models\Permission;
use App\Models\PiketIncident;
use App\Models\PiketLog;
use App\Models\PiketSchedule;
use App\Models\PiketSetting;
use App\Models\PklMonitoringLog;
use App\Models\PklPeriod;
use App\Models\PklPlacement;
use App\Models\PointThreshold;
use App\Models\PpdbApplicant;
use App\Models\PpdbChannel;
use App\Models\PpdbPeriod;
use App\Models\QuestionBank;
use App\Models\QuestionOption;
use App\Models\Room;
use App\Models\SchoolClass;
use App\Models\SchoolPost;
use App\Models\Semester;
use App\Models\StructuralPosition;
use App\Models\Student;
use App\Models\StudentAttendance;
use App\Models\StudentChangeRequest;
use App\Models\Subject;
use App\Models\SubjectKkm;
use App\Models\TeacherAchievement;
use App\Models\TeacherAchievementType;
use App\Models\TeacherChangeRequest;
use App\Models\TeacherPointReward;
use App\Models\TeacherViolation;
use App\Models\TeacherViolationType;
use App\Models\TeachingJournal;
use App\Models\UksMedicine;
use App\Models\UksMedicineTransaction;
use App\Models\UksVisit;
use App\Models\UksVisitType;
use App\Models\User;
use App\Models\Violation;
use App\Models\ViolationType;
use App\Models\WaliNote;
use App\Notifications\AcademicCalendarParentNotification;
use App\Support\ExtracurricularAccess;
use App\Support\TeacherAccess;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Provision & reset sekolah demo publik "SMA 1 Demo Servrin".
 */
class DemoSchoolService
{
    public function npsn(): string
    {
        return (string) config('demo.npsn', '99990001');
    }

    public function schoolName(): string
    {
        return (string) config('demo.name', 'SMA 1 Demo Servrin');
    }

    public function staffPassword(): string
    {
        return (string) config('demo.password', 'DemoServrin1!');
    }

    /**
     * Hapus institusi demo lama (jika ada) lalu seed ulang data lengkap.
     *
     * @return array{institution_id:int, wiped:bool, students:int, teachers:int, classes:int}
     */
    public function reset(): array
    {
        return DB::transaction(function () {
            $wiped = $this->wipe();
            $stats = $this->seed();

            Log::info('Demo school reset completed', [
                'npsn' => $this->npsn(),
                'wiped' => $wiped,
                'stats' => $stats,
            ]);

            return array_merge($stats, ['wiped' => $wiped]);
        });
    }

    public function wipe(): bool
    {
        $institutions = Institution::withTrashed()
            ->where(function ($q) {
                $q->where('is_demo', true)
                    ->orWhere('npsn', $this->npsn());
            })
            ->get();

        if ($institutions->isEmpty()) {
            return false;
        }

        foreach ($institutions as $institution) {
            $this->wipeInstitutionFully((int) $institution->id);
        }

        $this->wipeLeftoverDemoUsers();

        return true;
    }

    /**
     * Hapus institusi demo beserta seluruh child data.
     * Pakai FOREIGN_KEY_CHECKS=0 karena banyak FK RESTRICT antar child tables.
     */
    private function wipeInstitutionFully(int $institutionId): void
    {
        $userIds = User::where('institution_id', $institutionId)->pluck('id')->all();

        DB::table('institution')->where('id', $institutionId)->update([
            'active_academic_year_id' => null,
            'active_semester_id' => null,
            'updated_at' => now(),
        ]);

        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        try {
            // Child tanpa institution_id
            if (Schema::hasTable('library_books') && Schema::hasTable('library_book_copies')) {
                $bookIds = DB::table('library_books')->where('institution_id', $institutionId)->pluck('id');
                if ($bookIds->isNotEmpty()) {
                    $copyIds = DB::table('library_book_copies')->whereIn('book_id', $bookIds)->pluck('id');
                    if ($copyIds->isNotEmpty() && Schema::hasTable('library_loans')) {
                        DB::table('library_loans')->whereIn('copy_id', $copyIds)->delete();
                    }
                    DB::table('library_book_copies')->whereIn('book_id', $bookIds)->delete();
                }
            }

            if (Schema::hasTable('extracurriculars') && Schema::hasTable('extracurricular_student')) {
                $ekskulIds = DB::table('extracurriculars')->where('institution_id', $institutionId)->pluck('id');
                if ($ekskulIds->isNotEmpty()) {
                    foreach (['extracurricular_student', 'extracurricular_sessions', 'extracurricular_attendances', 'extracurricular_grades', 'extracurricular_session_grades'] as $tbl) {
                        if (Schema::hasTable($tbl) && Schema::hasColumn($tbl, 'extracurricular_id')) {
                            DB::table($tbl)->whereIn('extracurricular_id', $ekskulIds)->delete();
                        }
                    }
                }
            }

            if (Schema::hasTable('exams')) {
                $examIds = DB::table('exams')->where('institution_id', $institutionId)->pluck('id');
                if ($examIds->isNotEmpty()) {
                    $sessionIds = Schema::hasTable('exam_sessions')
                        ? DB::table('exam_sessions')->whereIn('exam_id', $examIds)->pluck('id')
                        : collect();
                    if ($sessionIds->isNotEmpty() && Schema::hasTable('exam_participants')) {
                        $participantIds = DB::table('exam_participants')->whereIn('exam_session_id', $sessionIds)->pluck('id');
                        if ($participantIds->isNotEmpty() && Schema::hasTable('exam_answers')) {
                            DB::table('exam_answers')->whereIn('exam_participant_id', $participantIds)->delete();
                        }
                        DB::table('exam_participants')->whereIn('id', $participantIds)->delete();
                    }
                    if ($sessionIds->isNotEmpty()) {
                        DB::table('exam_sessions')->whereIn('id', $sessionIds)->delete();
                    }
                    if (Schema::hasTable('exam_questions')) {
                        DB::table('exam_questions')->whereIn('exam_id', $examIds)->delete();
                    }
                }
            }

            if (Schema::hasTable('question_bank') && Schema::hasTable('question_options')) {
                $questionIds = DB::table('question_bank')->where('institution_id', $institutionId)->pluck('id');
                if ($questionIds->isNotEmpty()) {
                    DB::table('question_options')->whereIn('question_bank_id', $questionIds)->delete();
                }
            }

            if (Schema::hasTable('bank_soal') && Schema::hasTable('bank_soal_shares')) {
                $bankIds = DB::table('bank_soal')->where('institution_id', $institutionId)->pluck('id');
                if ($bankIds->isNotEmpty()) {
                    DB::table('bank_soal_shares')->whereIn('bank_soal_id', $bankIds)->delete();
                }
            }

            Storage::disk('public')->deleteDirectory('digital-archives/' . $institutionId);

            foreach ($this->tablesWithInstitutionId() as $table) {
                DB::table($table)->where('institution_id', $institutionId)->delete();
            }

            DB::table('institution')->where('id', $institutionId)->delete();
        } finally {
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }

        if ($userIds !== []) {
            if (Schema::hasTable('notifications')) {
                DB::table('notifications')
                    ->where('notifiable_type', User::class)
                    ->whereIn('notifiable_id', $userIds)
                    ->delete();
            }
            DB::table('personal_access_tokens')
                ->where('tokenable_type', User::class)
                ->whereIn('tokenable_id', $userIds)
                ->delete();
            DB::table('user_permissions')->whereIn('user_id', $userIds)->delete();
            User::whereIn('id', $userIds)->delete();
        }
    }

    /**
     * @return list<string>
     */
    private function tablesWithInstitutionId(): array
    {
        $schema = DB::getDatabaseName();
        $rows = DB::select(
            'SELECT DISTINCT TABLE_NAME AS table_name
             FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = ?
               AND COLUMN_NAME = ?
               AND TABLE_NAME <> ?',
            [$schema, 'institution_id', 'institution']
        );

        return collect($rows)
            ->pluck('table_name')
            ->filter(fn ($name) => Schema::hasTable($name))
            ->values()
            ->all();
    }

    private function wipeLeftoverDemoUsers(): void
    {
        $leftoverIds = User::query()
            ->where(function ($q) {
                $q->where('email', 'like', '%@demo.servrin.id')
                    ->orWhere('email', config('demo.admin_email'))
                    ->orWhere('email', config('demo.teacher_email'))
                    ->orWhere('email', config('demo.bk_email'))
                    ->orWhere('email', config('demo.parent_email'));
            })
            ->pluck('id')
            ->merge(
                User::query()
                    ->whereNull('institution_id')
                    ->where('role', 'student')
                    ->where('login_nik', 'like', '320199%')
                    ->pluck('id')
            )
            ->unique()
            ->all();

        if ($leftoverIds === []) {
            return;
        }

        DB::table('personal_access_tokens')
            ->where('tokenable_type', User::class)
            ->whereIn('tokenable_id', $leftoverIds)
            ->delete();
        DB::table('user_permissions')->whereIn('user_id', $leftoverIds)->delete();
        User::whereIn('id', $leftoverIds)->delete();
    }

    /**
     * @return array{institution_id:int, students:int, teachers:int, classes:int}
     */
    public function seed(): array
    {
        [$academicYear, $semester] = $this->resolveAcademicPeriod();

        $institution = Institution::create([
            'name' => $this->schoolName(),
            'npsn' => $this->npsn(),
            'level' => 'SMA',
            'type' => 'Negeri',
            'address' => 'Jl. Pendidikan No. 1, Kota Demo',
            'village' => 'Demo',
            'sub_district' => 'Demo',
            'district' => 'Kota Demo',
            'province' => 'Jawa Barat',
            'postal_code' => '40111',
            'phone' => '0221234567',
            'email' => 'info@demo.servrin.id',
            'website' => rtrim((string) config('demo.website', config('frontend.url')), '/') ?: 'https://servr.in',
            'principal_name' => 'Drs. Ahmad Demo, M.Pd.',
            'principal_nip' => '196501011990031001',
            'description' => 'Sekolah demo publik Servrin. Data di-reset setiap hari pukul 03:00 WIB.',
            'vision' => 'Menjadi sekolah unggul yang berkarakter dan berdaya saing.',
            'mission' => 'Menyelenggarakan pendidikan berkualitas dengan dukungan sistem informasi terpadu.',
            'is_active' => true,
            'is_demo' => true,
            'active_academic_year_id' => $academicYear->id,
            'active_semester_id' => $semester->id,
            'admission_label' => 'PPDB',
        ]);

        $admin = User::create([
            'institution_id' => $institution->id,
            'name' => 'Admin Demo Servrin',
            'email' => config('demo.admin_email'),
            'password' => $this->staffPassword(),
            'role' => 'institution_admin',
            'email_verified_at' => now(),
            'is_active' => true,
            'must_change_password' => false,
        ]);

        $inventoryCategories = $this->seedInventoryCategories($institution->id);
        $correspondenceCategories = $this->seedCorrespondenceCategories($institution->id);
        $this->seedPointThresholds($institution->id);
        $this->seedTeacherPointRewards($institution->id);

        $building = $this->seedFacility($institution);
        $rooms = $this->seedRooms($institution->id, $building);
        $teachers = $this->seedTeachers($institution->id);
        $this->seedPiket($institution, $academicYear, $semester, $teachers);
        $classes = $this->seedClasses($institution, $academicYear, $semester, $rooms, $teachers);
        $this->seedScheduleTemplate($institution, $semester, $classes);
        $subjects = $this->seedSubjects($institution->id);
        $schedules = $this->seedLessonSchedules($institution->id, $semester->id, $classes, $subjects, $teachers, $rooms);
        $students = $this->seedStudents($institution, $academicYear, $semester, $classes);
        $this->seedParent($institution, $students);
        $this->seedViolations($institution, $academicYear, $semester, $students, $admin);
        $this->seedTeacherViolations($institution, $academicYear, $semester, $teachers, $admin);
        $this->seedCounseling($institution, $academicYear, $semester, $students, $admin);
        $this->seedJournalsAndAttendance($institution, $semester, $schedules, $students);
        $this->seedGrades($institution, $academicYear, $semester, $classes, $subjects, $students, $teachers);
        $this->seedAchievements($institution, $academicYear, $semester, $students, $admin);
        $this->seedTeacherAchievements($institution, $academicYear, $semester, $teachers, $admin);
        $this->seedExtracurriculars($institution, $academicYear, $semester, $teachers, $students, $rooms);
        $inventoryItems = $this->seedInventoryItems($institution->id, $inventoryCategories, $rooms, $admin);
        $this->seedIndividualInventoryAssets($institution, $inventoryItems, $rooms, $admin);
        $this->seedInventoryOps($institution, $inventoryItems, $rooms, $teachers, $students, $admin);
        $this->seedLibrary($institution, $students, $admin);
        $this->seedFinance($institution, $academicYear, $students, $admin);
        $this->seedUks($institution, $academicYear, $semester, $students, $admin);
        $calendarEvent = $this->seedAcademicCalendar($institution, $academicYear, $semester, $admin);
        $this->seedSchoolPosts($institution, $admin);
        $this->seedPpdb($institution, $academicYear);
        $this->seedKepegawaian($institution, $teachers, $admin);
        $this->seedPayroll($institution, $teachers, $admin);
        $this->seedEmployeeAttendance($institution, $teachers);
        $this->seedCorrespondence($institution, $correspondenceCategories, $admin);
        $this->seedLabs($institution, $teachers, $admin, $building);
        $this->seedAlumni($institution, $academicYear, $semester, $classes, $admin);
        $this->seedPklAndBkk($institution, $academicYear, $students, $teachers, $admin);
        $this->seedWaliNotes($institution, $classes, $students, $teachers, $admin);
        $this->seedDigitalArchive($institution, $admin);
        $this->seedGuestBook($institution, $admin);
        $this->seedPiketLogs($institution, $teachers, $students, $classes, $admin);
        $this->seedChangeRequests($institution, $students, $teachers, $admin);
        $this->seedExam($institution, $subjects, $students, $admin);
        $this->seedNotifications($institution, $calendarEvent, $admin, $students);

        app(WaliKelasPermissionService::class)->syncAllCurrentWaliKelas();

        return [
            'institution_id' => $institution->id,
            'students' => count($students),
            'teachers' => count($teachers),
            'classes' => count($classes),
        ];
    }

    /**
     * @return array{0:AcademicYear,1:Semester}
     */
    private function resolveAcademicPeriod(): array
    {
        $academicYear = AcademicYear::query()
            ->where('status', 'Aktif')
            ->orderByDesc('start_date')
            ->first();

        if (!$academicYear) {
            $academicYear = AcademicYear::firstOrCreate(
                ['code' => '2025/2026'],
                [
                    'name' => 'Tahun Ajaran 2025/2026',
                    'start_date' => '2025-07-01',
                    'end_date' => '2026-06-30',
                    'status' => 'Aktif',
                    'description' => 'Tahun ajaran demo',
                ]
            );
        }

        $semester = Semester::query()
            ->where('academic_year_id', $academicYear->id)
            ->where('status', 'Aktif')
            ->orderBy('order')
            ->first();

        if (!$semester) {
            $semester = Semester::firstOrCreate(
                [
                    'academic_year_id' => $academicYear->id,
                    'name' => 'Ganjil',
                ],
                [
                    'order' => 1,
                    'start_date' => $academicYear->start_date ?? '2025-07-01',
                    'end_date' => '2025-12-31',
                    'status' => 'Aktif',
                    'description' => 'Semester aktif demo',
                ]
            );
        }

        return [$academicYear, $semester];
    }

    /**
     * @return list<InventoryCategory>
     */
    private function seedInventoryCategories(int $institutionId): array
    {
        $defs = [
            ['code' => 'ELEK', 'name' => 'Elektronik', 'description' => 'Peralatan elektronik'],
            ['code' => 'MEU', 'name' => 'Meubelair', 'description' => 'Perabotan dan furniture'],
            ['code' => 'LAB', 'name' => 'Peralatan Lab', 'description' => 'Peralatan laboratorium'],
            ['code' => 'ATK', 'name' => 'Alat Tulis Kantor', 'description' => 'Alat tulis kantor'],
            ['code' => 'OLA', 'name' => 'Peralatan Olahraga', 'description' => 'Peralatan olahraga'],
            ['code' => 'MED', 'name' => 'Peralatan Medis/Kesehatan', 'description' => 'Peralatan medis dan kesehatan'],
        ];

        $categories = [];
        foreach ($defs as $category) {
            $categories[] = InventoryCategory::create([
                'institution_id' => $institutionId,
                'code' => $category['code'],
                'name' => $category['name'],
                'description' => $category['description'],
                'is_active' => true,
            ]);
        }

        return $categories;
    }

    private function seedPointThresholds(int $institutionId): void
    {
        $defs = [
            ['point_min' => 0, 'point_max' => 24, 'action_name' => 'Pembinaan ringan', 'sort_order' => 1],
            ['point_min' => 25, 'point_max' => 49, 'action_name' => 'Panggilan orang tua', 'sort_order' => 2],
            ['point_min' => 50, 'point_max' => 74, 'action_name' => 'Surat peringatan', 'sort_order' => 3],
            ['point_min' => 75, 'point_max' => 100, 'action_name' => 'Skorsing / rapat kasus', 'sort_order' => 4],
        ];

        foreach ($defs as $def) {
            PointThreshold::create([
                'institution_id' => $institutionId,
                'point_min' => $def['point_min'],
                'point_max' => $def['point_max'],
                'action_name' => $def['action_name'],
                'description' => $def['action_name'] . ' (demo)',
                'sort_order' => $def['sort_order'],
                'is_active' => true,
            ]);
        }
    }

    private function seedTeacherPointRewards(int $institutionId): void
    {
        $defs = [
            ['point_min' => 0, 'point_max' => 19, 'reward_name' => 'Apresiasi dasar', 'sort_order' => 1],
            ['point_min' => 20, 'point_max' => 49, 'reward_name' => 'Piagam penghargaan', 'sort_order' => 2],
            ['point_min' => 50, 'point_max' => 100, 'reward_name' => 'Guru berprestasi', 'sort_order' => 3],
        ];

        foreach ($defs as $def) {
            TeacherPointReward::create([
                'institution_id' => $institutionId,
                'point_min' => $def['point_min'],
                'point_max' => $def['point_max'],
                'reward_name' => $def['reward_name'],
                'description' => $def['reward_name'] . ' (demo)',
                'sort_order' => $def['sort_order'],
                'is_active' => true,
            ]);
        }
    }

    /**
     * @return array{masuk: list<CorrespondenceCategory>, keluar: list<CorrespondenceCategory>, internal: list<CorrespondenceCategory>}
     */
    private function seedCorrespondenceCategories(int $institutionId): array
    {
        $defs = [
            'masuk' => ['Surat Resmi', 'Surat Undangan', 'Surat Pemberitahuan', 'Lainnya'],
            'keluar' => ['Surat Resmi', 'Surat Undangan', 'Surat Edaran', 'Lainnya'],
            'internal' => ['Surat Edaran', 'Surat Memo', 'Lainnya'],
        ];

        $byType = ['masuk' => [], 'keluar' => [], 'internal' => []];
        foreach ($defs as $type => $names) {
            foreach ($names as $name) {
                $byType[$type][] = CorrespondenceCategory::create([
                    'institution_id' => $institutionId,
                    'type' => $type,
                    'name' => $name,
                    'description' => $name . ' (demo)',
                ]);
            }
        }

        return $byType;
    }

    /**
     * @return list<Room>
     */
    private function seedRooms(int $institutionId, ?Building $building = null): array
    {
        $rooms = [];
        $defs = [
            ['X-1', 'R-X1'],
            ['X-2', 'R-X2'],
            ['XI-1', 'R-XI1'],
            ['XI-2', 'R-XI2'],
            ['XII-1', 'R-XII1'],
            ['XII-2', 'R-XII2'],
        ];

        foreach ($defs as $i => [$name, $code]) {
            $rooms[] = Room::create([
                'institution_id' => $institutionId,
                'building_id' => $building?->id,
                'name' => 'Ruang ' . $name,
                'code' => $code,
                'type' => 'Kelas',
                'floor' => 1 + intdiv($i, 3),
                'capacity' => 36,
                'condition' => 'Baik',
                'description' => 'Ruang kelas demo',
            ]);
        }

        Room::create([
            'institution_id' => $institutionId,
            'building_id' => $building?->id,
            'name' => 'Perpustakaan',
            'code' => 'R-PERPUS',
            'type' => 'Perpustakaan',
            'floor' => 1,
            'capacity' => 40,
            'condition' => 'Baik',
        ]);

        Room::create([
            'institution_id' => $institutionId,
            'building_id' => $building?->id,
            'name' => 'UKS',
            'code' => 'R-UKS',
            'type' => 'Lainnya',
            'floor' => 1,
            'capacity' => 8,
            'condition' => 'Baik',
        ]);

        Room::create([
            'institution_id' => $institutionId,
            'building_id' => $building?->id,
            'name' => 'Kantor TU',
            'code' => 'R-TU',
            'type' => 'Kantor',
            'floor' => 1,
            'capacity' => 12,
            'condition' => 'Baik',
        ]);

        return $rooms;
    }

    /**
     * @return list<Employee>
     */
    private function seedTeachers(int $institutionId): array
    {
        // Index 0–5 dipakai wali kelas / jadwal mapel; sisanya duty-domain.
        $defs = [
            [
                'name' => 'Kepala Sekolah Demo',
                'email' => config('demo.teacher_email'), // guru01 — apresiasi & pelanggaran guru (approve)
                'gender' => 'L',
                'subject' => 'Matematika',
                'duties' => ['kepala_sekolah'],
            ],
            [
                'name' => 'Guru Piket Demo',
                'email' => 'guru02@demo.servrin.id', // catat pelanggaran guru + modul piket
                'gender' => 'P',
                'subject' => 'Bahasa Indonesia',
                'duties' => ['guru_piket'],
            ],
            [
                'name' => 'Waka Kurikulum Demo',
                'email' => 'guru03@demo.servrin.id',
                'gender' => 'L',
                'subject' => 'Bahasa Inggris',
                'duties' => ['waka_kurikulum'],
            ],
            [
                'name' => 'Waka Sarpras Demo',
                'email' => 'guru04@demo.servrin.id',
                'gender' => 'P',
                'subject' => 'Fisika',
                'duties' => ['waka_sarpras'],
            ],
            [
                'name' => 'Bendahara Demo',
                'email' => 'guru05@demo.servrin.id',
                'gender' => 'L',
                'subject' => 'Kimia',
                'duties' => ['bendahara'],
            ],
            [
                'name' => 'Ketua Perpus Demo',
                'email' => 'guru06@demo.servrin.id',
                'gender' => 'P',
                'subject' => 'Biologi',
                'duties' => ['ketua_perpus'],
            ],
            [
                'name' => 'Guru BK Demo',
                'email' => config('demo.bk_email'),
                'gender' => 'P',
                'subject' => 'BK',
                'duties' => ['koordinator_bk'],
            ],
            [
                'name' => 'Koordinator UKS Demo',
                'email' => 'uks@demo.servrin.id',
                'gender' => 'P',
                'subject' => 'PJOK',
                'duties' => ['koordinator_uks'],
            ],
            [
                'name' => 'Waka Kesiswaan Demo',
                'email' => 'kesiswaan@demo.servrin.id',
                'gender' => 'L',
                'subject' => 'PPKn',
                'duties' => ['waka_kesiswaan'],
            ],
            [
                'name' => 'Koordinator PPDB Demo',
                'email' => 'ppdb@demo.servrin.id',
                'gender' => 'P',
                'subject' => 'Sejarah',
                'duties' => ['koordinator_ppdb'],
            ],
            [
                'name' => 'Koordinator Ekskul Demo',
                'email' => 'ekskul@demo.servrin.id',
                'gender' => 'L',
                'subject' => 'Seni Budaya',
                'duties' => ['koordinator_ekstrakurikuler'],
            ],
            [
                'name' => 'Operator Sekolah Demo',
                'email' => 'operator@demo.servrin.id',
                'gender' => 'P',
                'subject' => 'TIK',
                'duties' => ['operator_sekolah'],
            ],
            [
                'name' => 'Waka Humas Demo',
                'email' => 'humas@demo.servrin.id',
                'gender' => 'L',
                'subject' => 'Sosiologi',
                'duties' => ['waka_humas'],
                'extra_permissions' => ['school_content'],
            ],
            [
                'name' => 'Kepala TU Demo',
                'email' => 'tu@demo.servrin.id',
                'gender' => 'P',
                'subject' => 'Administrasi',
                'duties' => ['kepala_tata_usaha'],
            ],
            [
                'name' => 'Kepala Lab Demo',
                'email' => 'lab@demo.servrin.id',
                'gender' => 'L',
                'subject' => 'Kimia',
                'duties' => ['kepala_lab'],
            ],
        ];

        $teachers = [];
        foreach ($defs as $i => $def) {
            $nik = sprintf('320188%010d', $i + 1);
            $employee = Employee::create([
                'institution_id' => $institutionId,
                'nik' => $nik,
                'type' => 'Guru',
                'nip' => sprintf('19800101%06d', $i + 1),
                'name' => $def['name'],
                'gender' => $def['gender'],
                'birth_date' => '1980-01-15',
                'birth_place' => 'Bandung',
                'email' => $def['email'],
                'phone' => '0812345678' . sprintf('%02d', $i),
                'subject' => $def['subject'],
                'status' => 'Aktif',
                'employment_status' => 'PNS',
                'join_date' => '2015-07-01',
            ]);

            $permissionKeys = TeacherAccess::defaultPermissionKeys();
            foreach ($def['duties'] ?? [] as $dutyKey) {
                $duty = AdditionalDuty::where('key', $dutyKey)->first();
                if (!$duty) {
                    Log::warning('Demo school: additional duty missing', ['key' => $dutyKey]);
                    continue;
                }
                $employee->additionalDuties()->attach($duty->id, [
                    'started_at' => now()->toDateString(),
                    'ended_at' => null,
                ]);
                $dutyKeys = $duty->permissions()->pluck('key')->all();
                $permissionKeys = array_values(array_unique(array_merge($permissionKeys, $dutyKeys)));
            }
            $permissionKeys = array_values(array_unique(array_merge(
                $permissionKeys,
                $def['extra_permissions'] ?? []
            )));

            $user = User::create([
                'institution_id' => $institutionId,
                'name' => $def['name'],
                'email' => $def['email'],
                'password' => $this->staffPassword(),
                'role' => 'teacher',
                'email_verified_at' => now(),
                'is_active' => true,
                'must_change_password' => false,
            ]);
            $this->syncPermissions($user, $permissionKeys);

            $teachers[] = $employee;
        }

        return $teachers;
    }

    /**
     * @param  list<Employee>  $teachers
     */
    private function seedPiket(
        Institution $institution,
        AcademicYear $academicYear,
        Semester $semester,
        array $teachers
    ): void {
        PiketSetting::forInstitution($institution->id);

        // guru02 = Guru Piket Demo (index 1)
        $piketTeacher = $teachers[1] ?? $teachers[0] ?? null;
        if (!$piketTeacher) {
            return;
        }

        // Cadangan: KS (index 0) piket Kamis agar ada >1 petugas
        $backup = $teachers[0] ?? null;

        foreach ([1, 2, 3, 5] as $day) {
            PiketSchedule::create([
                'institution_id' => $institution->id,
                'academic_year_id' => $academicYear->id,
                'semester_id' => $semester->id,
                'employee_id' => $piketTeacher->id,
                'day_of_week' => $day,
                'shift' => 'full',
                'start_time' => '07:00',
                'end_time' => '14:00',
                'notes' => 'Jadwal piket demo',
            ]);
        }

        if ($backup && $backup->id !== $piketTeacher->id) {
            PiketSchedule::create([
                'institution_id' => $institution->id,
                'academic_year_id' => $academicYear->id,
                'semester_id' => $semester->id,
                'employee_id' => $backup->id,
                'day_of_week' => 4, // Kamis
                'shift' => 'full',
                'start_time' => '07:00',
                'end_time' => '14:00',
                'notes' => 'Jadwal piket cadangan (KS)',
            ]);
        }
    }

    /**
     * @param  list<Room>  $rooms
     * @param  list<Employee>  $teachers
     * @return list<SchoolClass>
     */
    private function seedClasses(
        Institution $institution,
        AcademicYear $academicYear,
        Semester $semester,
        array $rooms,
        array $teachers
    ): array {
        $defs = [
            ['name' => 'X IPA 1', 'grade' => 10, 'code' => 'X-IPA-1'],
            ['name' => 'X IPA 2', 'grade' => 10, 'code' => 'X-IPA-2'],
            ['name' => 'XI IPA 1', 'grade' => 11, 'code' => 'XI-IPA-1'],
            ['name' => 'XI IPA 2', 'grade' => 11, 'code' => 'XI-IPA-2'],
            ['name' => 'XII IPA 1', 'grade' => 12, 'code' => 'XII-IPA-1'],
            ['name' => 'XII IPA 2', 'grade' => 12, 'code' => 'XII-IPA-2'],
        ];

        $classes = [];
        foreach ($defs as $i => $def) {
            $classes[] = SchoolClass::create([
                'institution_id' => $institution->id,
                'room_id' => $rooms[$i]->id ?? null,
                'teacher_id' => $teachers[$i]->id ?? null,
                'code' => $def['code'],
                'name' => $def['name'],
                'grade' => $def['grade'],
                'academic_year' => $academicYear->code,
                'academic_year_id' => $academicYear->id,
                'semester_id' => $semester->id,
                'capacity' => 36,
                'status' => 'Aktif',
            ]);
        }

        return $classes;
    }

    /**
     * @return list<Subject>
     */
    private function seedSubjects(int $institutionId): array
    {
        $defs = [
            ['MTK', 'Matematika'],
            ['BIN', 'Bahasa Indonesia'],
            ['BIG', 'Bahasa Inggris'],
            ['FIS', 'Fisika'],
            ['KIM', 'Kimia'],
            ['BIO', 'Biologi'],
            ['SEJ', 'Sejarah'],
            ['PJOK', 'Pendidikan Jasmani'],
        ];

        $subjects = [];
        foreach ($defs as [$code, $name]) {
            $subjects[] = Subject::create([
                'institution_id' => $institutionId,
                'code' => $code,
                'name' => $name,
                'is_active' => true,
            ]);
        }

        return $subjects;
    }

    /**
     * @param  list<SchoolClass>  $classes
     * @param  list<Subject>  $subjects
     * @param  list<Employee>  $teachers
     * @param  list<Room>  $rooms
     * @return list<LessonSchedule>
     */
    private function seedLessonSchedules(
        int $institutionId,
        int $semesterId,
        array $classes,
        array $subjects,
        array $teachers,
        array $rooms
    ): array {
        $periods = [
            1 => ['07:00', '07:45'],
            2 => ['07:45', '08:30'],
            3 => ['08:45', '09:30'],
            4 => ['09:30', '10:15'],
            5 => ['10:30', '11:15'],
        ];

        $mapelTeachers = min(6, count($teachers));
        $schedules = [];

        foreach ($classes as $ci => $class) {
            foreach ([1, 2, 3, 4, 5] as $day) { // Senin–Jumat
                foreach ($periods as $period => [$start, $end]) {
                    $subject = $subjects[($ci + $day + $period) % count($subjects)];
                    $teacher = $teachers[($ci + $day + $period) % $mapelTeachers];
                    $schedules[] = LessonSchedule::create([
                        'institution_id' => $institutionId,
                        'semester_id' => $semesterId,
                        'class_id' => $class->id,
                        'subject_id' => $subject->id,
                        'employee_id' => $teacher->id,
                        'room_id' => $rooms[$ci]->id ?? null,
                        'day_of_week' => $day,
                        'period' => $period,
                        'start_time' => $start,
                        'end_time' => $end,
                    ]);
                }
            }
        }

        return $schedules;
    }

    /**
     * @param  list<SchoolClass>  $classes
     * @return list<Student>
     */
    private function seedStudents(
        Institution $institution,
        AcademicYear $academicYear,
        Semester $semester,
        array $classes
    ): array {
        $accountService = app(StudentAccountService::class);
        $students = [];
        $firstNik = (string) config('demo.student_nik', '3201990000000001');
        $firstBirth = (string) config('demo.student_birth_date', '2008-05-15');
        $seq = 0;

        $firstNames = ['Andi', 'Budi', 'Citra', 'Dewi', 'Eko', 'Fajar', 'Gita', 'Hana', 'Irfan', 'Joko'];
        $lastNames = ['Pratama', 'Saputra', 'Wulandari', 'Nugroho', 'Lestari', 'Wijaya'];

        foreach ($classes as $class) {
            for ($i = 0; $i < 10; $i++) {
                $seq++;
                $nik = sprintf('%s', (int) $firstNik + $seq - 1);
                $nik = str_pad($nik, 16, '0', STR_PAD_LEFT);
                $gender = $i % 2 === 0 ? 'L' : 'P';
                $birthYear = 2008 + (12 - (int) $class->grade);
                $birthDate = $seq === 1
                    ? $firstBirth
                    : sprintf('%d-%02d-%02d', $birthYear, (($i % 12) + 1), (($i % 28) + 1));

                $student = Student::create([
                    'institution_id' => $institution->id,
                    'nik' => $nik,
                    'nis' => sprintf('S%04d', $seq),
                    'nisn' => sprintf('00%08d', 10000000 + $seq),
                    'name' => $firstNames[$i % count($firstNames)] . ' ' . $lastNames[($seq + $i) % count($lastNames)],
                    'gender' => $gender,
                    'birth_date' => $birthDate,
                    'birth_place' => 'Kota Demo',
                    'address' => 'Jl. Siswa No. ' . $seq . ', Kota Demo',
                    'religion' => 'Islam',
                    'tingkat' => (int) $class->grade,
                    'class_id' => $class->id,
                    'academic_year' => $academicYear->code,
                    'academic_year_id' => $academicYear->id,
                    'semester_id' => $semester->id,
                    'status' => 'Aktif',
                    'father_name' => 'Ayah ' . $firstNames[$i % count($firstNames)],
                    'mother_name' => 'Ibu ' . $firstNames[$i % count($firstNames)],
                    'guardian_phone' => '08130000' . sprintf('%04d', $seq),
                ]);

                $result = $accountService->ensureAccount($student);
                if ($result['user']) {
                    $result['user']->forceFill([
                        'must_change_password' => false,
                    ])->save();
                }

                $students[] = $student;
            }
        }

        return $students;
    }

    /**
     * @param  list<Student>  $students
     * @param  list<Employee>  $teachers
     */
    private function seedViolations(
        Institution $institution,
        AcademicYear $academicYear,
        Semester $semester,
        array $students,
        User $admin
    ): void {
        $types = [
            ['code' => 'TLT', 'name' => 'Terlambat masuk kelas', 'category' => 'ringan', 'point_weight' => 5],
            ['code' => 'ATR', 'name' => 'Tidak memakai atribut lengkap', 'category' => 'ringan', 'point_weight' => 5],
            ['code' => 'BLK', 'name' => 'Membolos', 'category' => 'sedang', 'point_weight' => 15],
            ['code' => 'RKT', 'name' => 'Berkelahi', 'category' => 'berat', 'point_weight' => 50],
        ];

        $typeModels = [];
        foreach ($types as $type) {
            $typeModels[] = ViolationType::create([
                'institution_id' => $institution->id,
                'name' => $type['name'],
                'code' => $type['code'],
                'category' => $type['category'],
                'point_weight' => $type['point_weight'],
                'default_sanction' => 'Peringatan lisan',
                'is_active' => true,
            ]);
        }

        $bkUser = User::where('email', config('demo.bk_email'))->first() ?? $admin;
        $reporterId = $bkUser->id;

        for ($i = 0; $i < 12; $i++) {
            $student = $students[$i * 3] ?? $students[0];
            $type = $typeModels[$i % count($typeModels)];
            Violation::create([
                'institution_id' => $institution->id,
                'student_id' => $student->id,
                'violation_type_id' => $type->id,
                'reported_by' => $reporterId,
                'reviewed_by' => $reporterId,
                'reviewed_at' => now()->subDays($i),
                'violation_date' => now()->subDays($i + 1)->toDateString(),
                'sanction' => $type->default_sanction,
                'status' => $i % 4 === 0 ? Violation::STATUS_PENDING : Violation::STATUS_DICATAT,
                'description' => 'Contoh pelanggaran demo #' . ($i + 1),
                'academic_year_id' => $academicYear->id,
                'semester_id' => $semester->id,
                'class_id' => $student->class_id,
            ]);
        }
    }

    /**
     * @param  list<Student>  $students
     */
    private function seedFinance(
        Institution $institution,
        AcademicYear $academicYear,
        array $students,
        User $admin
    ): void {
        $spp = FinanceFeeType::create([
            'institution_id' => $institution->id,
            'code' => 'SPP',
            'name' => 'SPP Bulanan',
            'description' => 'Iuran SPP bulanan (demo)',
            'frequency' => 'monthly',
            'scope' => 'student',
            'default_amount' => 150000,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $periodLabel = now()->format('Y-m');
        foreach (array_slice($students, 0, 20) as $i => $student) {
            $amount = 150000;
            $paid = $i % 3 === 0 ? $amount : ($i % 3 === 1 ? 75000 : 0);
            $status = $paid >= $amount ? 'paid' : ($paid > 0 ? 'partial' : 'unpaid');

            $invoice = FinanceInvoice::create([
                'institution_id' => $institution->id,
                'fee_type_id' => $spp->id,
                'student_id' => $student->id,
                'class_id' => $student->class_id,
                'academic_year_id' => $academicYear->id,
                'title' => 'SPP ' . $periodLabel,
                'period_label' => $periodLabel,
                'amount' => $amount,
                'amount_paid' => $paid,
                'due_date' => now()->endOfMonth()->toDateString(),
                'status' => $status,
                'created_by' => $admin->id,
            ]);

            if ($paid > 0) {
                FinancePayment::create([
                    'institution_id' => $institution->id,
                    'invoice_id' => $invoice->id,
                    'amount' => $paid,
                    'paid_at' => now()->subDays($i % 7),
                    'method' => 'cash',
                    'reference' => 'DEMO-PAY-' . ($i + 1),
                    'recorded_by' => $admin->id,
                ]);
            }
        }

        $kegiatan = FinanceFeeType::create([
            'institution_id' => $institution->id,
            'code' => 'KEG',
            'name' => 'Uang Kegiatan',
            'description' => 'Iuran kegiatan tahunan (demo)',
            'frequency' => 'yearly',
            'scope' => 'student',
            'default_amount' => 250000,
            'is_active' => true,
            'sort_order' => 2,
        ]);
        FinanceFeeType::create([
            'institution_id' => $institution->id,
            'code' => 'GDG',
            'name' => 'Uang Gedung',
            'description' => 'Iuran sekali bayar siswa baru (demo)',
            'frequency' => 'one_time',
            'scope' => 'student',
            'default_amount' => 1000000,
            'is_active' => true,
            'sort_order' => 3,
        ]);

        foreach (array_slice($students, 0, 8) as $i => $student) {
            $amount = 250000;
            $paid = $i % 2 === 0 ? $amount : 0;
            $invoice = FinanceInvoice::create([
                'institution_id' => $institution->id,
                'fee_type_id' => $kegiatan->id,
                'student_id' => $student->id,
                'class_id' => $student->class_id,
                'academic_year_id' => $academicYear->id,
                'title' => 'Uang Kegiatan ' . $academicYear->code,
                'period_label' => $academicYear->code,
                'amount' => $amount,
                'amount_paid' => $paid,
                'due_date' => now()->addDays(20)->toDateString(),
                'status' => $paid > 0 ? 'paid' : 'unpaid',
                'created_by' => $admin->id,
            ]);
            if ($paid > 0) {
                FinancePayment::create([
                    'institution_id' => $institution->id,
                    'invoice_id' => $invoice->id,
                    'amount' => $paid,
                    'paid_at' => now()->subDays(3),
                    'method' => 'transfer',
                    'reference' => 'DEMO-KEG-' . ($i + 1),
                    'recorded_by' => $admin->id,
                ]);
            }
        }
    }

    /**
     * @param  list<Student>  $students
     */
    private function seedUks(
        Institution $institution,
        AcademicYear $academicYear,
        Semester $semester,
        array $students,
        User $admin
    ): void {
        $types = [
            ['code' => 'DEM', 'name' => 'Demam'],
            ['code' => 'SDR', 'name' => 'Sakit kepala'],
            ['code' => 'LLK', 'name' => 'Luka ringan'],
        ];

        $typeModels = [];
        foreach ($types as $type) {
            $typeModels[] = UksVisitType::create([
                'institution_id' => $institution->id,
                'name' => $type['name'],
                'code' => $type['code'],
                'description' => $type['name'] . ' (demo)',
                'is_active' => true,
            ]);
        }

        $uksUser = User::where('email', 'uks@demo.servrin.id')->first() ?? $admin;

        $visits = [];
        for ($i = 0; $i < 8; $i++) {
            $student = $students[$i * 5] ?? $students[0];
            $type = $typeModels[$i % count($typeModels)];
            $visits[] = UksVisit::create([
                'institution_id' => $institution->id,
                'student_id' => $student->id,
                'recorded_by' => $uksUser->id,
                'uks_visit_type_id' => $type->id,
                'visit_date' => now()->subDays($i)->toDateString(),
                'status' => 'selesai',
                'complaint' => 'Keluhan demo: ' . $type->name,
                'action_taken' => 'Istirahat di UKS + obat',
                'temperature_c' => 37.0 + ($i % 3) * 0.3,
                'academic_year_id' => $academicYear->id,
                'semester_id' => $semester->id,
                'class_id' => $student->class_id,
            ]);
        }

        $medicineDefs = [
            ['code' => 'PCT', 'name' => 'Paracetamol 500mg', 'unit' => 'tablet', 'qty' => 80, 'min' => 20],
            ['code' => 'IBU', 'name' => 'Ibuprofen 200mg', 'unit' => 'tablet', 'qty' => 40, 'min' => 10],
            ['code' => 'ORS', 'name' => 'Oralit', 'unit' => 'sachet', 'qty' => 25, 'min' => 8],
            ['code' => 'BTD', 'name' => 'Betadine', 'unit' => 'botol', 'qty' => 6, 'min' => 2],
            ['code' => 'PLS', 'name' => 'Plester', 'unit' => 'lembar', 'qty' => 50, 'min' => 15],
            ['code' => 'VTM', 'name' => 'Vitamin C', 'unit' => 'tablet', 'qty' => 12, 'min' => 15],
            ['code' => 'ANT', 'name' => 'Antasida', 'unit' => 'tablet', 'qty' => 30, 'min' => 10],
        ];

        $medicines = [];
        foreach ($medicineDefs as $def) {
            $medicines[] = UksMedicine::create([
                'institution_id' => $institution->id,
                'name' => $def['name'],
                'code' => $def['code'],
                'unit' => $def['unit'],
                'quantity' => $def['qty'],
                'min_stock' => $def['min'],
                'expiry_date' => now()->addMonths(8 + (strlen($def['code']) % 6))->toDateString(),
                'description' => $def['name'] . ' stok UKS demo',
                'is_active' => true,
            ]);
        }

        foreach (array_slice($visits, 0, 3) as $i => $visit) {
            $medicine = $medicines[$i % count($medicines)];
            $qty = $i === 0 ? 2 : 1;
            if ($medicine->quantity < $qty) {
                continue;
            }
            $medicine->decrement('quantity', $qty);
            UksMedicineTransaction::create([
                'institution_id' => $institution->id,
                'uks_medicine_id' => $medicine->id,
                'type' => 'keluar',
                'quantity' => $qty,
                'transaction_date' => $visit->visit_date,
                'notes' => 'Pemberian obat kunjungan demo',
                'uks_visit_id' => $visit->id,
                'created_by' => $uksUser->id,
            ]);
        }

        UksMedicineTransaction::create([
            'institution_id' => $institution->id,
            'uks_medicine_id' => $medicines[0]->id,
            'type' => 'masuk',
            'quantity' => 20,
            'transaction_date' => now()->subDays(10)->toDateString(),
            'notes' => 'Pembelian stok demo',
            'created_by' => $uksUser->id,
        ]);
        $medicines[0]->increment('quantity', 20);
    }

    /**
     * @param  list<Employee>  $teachers
     */
    private function seedTeacherViolations(
        Institution $institution,
        AcademicYear $academicYear,
        Semester $semester,
        array $teachers,
        User $admin
    ): void {
        $defs = [
            ['code' => 'TLT-G', 'name' => 'Terlambat masuk mengajar', 'category' => 'kehadiran', 'point_weight' => 5],
            ['code' => 'ABS-G', 'name' => 'Tidak hadir tanpa keterangan', 'category' => 'kehadiran', 'point_weight' => 15],
            ['code' => 'ADM-G', 'name' => 'Terlambat mengumpulkan administrasi', 'category' => 'administrasi', 'point_weight' => 5],
            ['code' => 'DSP-G', 'name' => 'Melanggar tata tertib pegawai', 'category' => 'kedisiplinan', 'point_weight' => 10],
        ];

        $types = [];
        foreach ($defs as $i => $def) {
            $types[] = TeacherViolationType::create([
                'institution_id' => $institution->id,
                'name' => $def['name'],
                'code' => $def['code'],
                'point_weight' => $def['point_weight'],
                'category' => $def['category'],
                'default_sanction' => 'Peringatan lisan',
                'description' => $def['name'] . ' (demo)',
                'sort_order' => $i + 1,
                'is_active' => true,
            ]);
        }

        $mapelTeachers = array_slice($teachers, 0, 6);
        for ($i = 0; $i < 8; $i++) {
            $type = $types[$i % count($types)];
            $employee = $mapelTeachers[$i % count($mapelTeachers)];
            TeacherViolation::create([
                'institution_id' => $institution->id,
                'employee_id' => $employee->id,
                'violation_type_id' => $type->id,
                'violation_date' => now()->subDays($i + 2)->toDateString(),
                'point_value' => $type->point_weight,
                'notes' => 'Contoh pelanggaran guru demo #' . ($i + 1),
                'status' => $i % 3 === 0 ? TeacherViolation::STATUS_PENDING : TeacherViolation::STATUS_APPROVED,
                'sanction' => $type->default_sanction,
                'reported_by' => $admin->id,
                'reviewed_by' => $i % 3 === 0 ? null : $admin->id,
                'reviewed_at' => $i % 3 === 0 ? null : now()->subDays($i),
                'academic_year_id' => $academicYear->id,
                'semester_id' => $semester->id,
            ]);
        }
    }

    /**
     * @param  list<Student>  $students
     */
    private function seedCounseling(
        Institution $institution,
        AcademicYear $academicYear,
        Semester $semester,
        array $students,
        User $admin
    ): void {
        $typeDefs = [
            ['code' => 'PRI', 'name' => 'Bimbingan pribadi'],
            ['code' => 'SOS', 'name' => 'Bimbingan sosial'],
            ['code' => 'KAR', 'name' => 'Bimbingan karier'],
            ['code' => 'BLJ', 'name' => 'Bimbingan belajar'],
        ];

        $types = [];
        foreach ($typeDefs as $def) {
            $types[] = CounselingType::create([
                'institution_id' => $institution->id,
                'name' => $def['name'],
                'code' => $def['code'],
                'description' => $def['name'] . ' (demo)',
                'is_active' => true,
            ]);
        }

        $counselor = User::where('email', config('demo.bk_email'))->first() ?? $admin;

        for ($i = 0; $i < 8; $i++) {
            $student = $students[$i * 4] ?? $students[0];
            $type = $types[$i % count($types)];
            CounselingSession::create([
                'institution_id' => $institution->id,
                'student_id' => $student->id,
                'counselor_id' => $counselor->id,
                'counseling_type_id' => $type->id,
                'session_date' => now()->subDays($i + 1)->toDateString(),
                'status' => $i % 4 === 0 ? 'jadwal' : 'selesai',
                'summary' => 'Sesi konseling demo: ' . $type->name,
                'follow_up_notes' => $i % 4 === 0 ? null : 'Follow-up dijadwalkan minggu depan',
                'academic_year_id' => $academicYear->id,
                'semester_id' => $semester->id,
                'class_id' => $student->class_id,
            ]);
        }
    }

    /**
     * @param  list<LessonSchedule>  $schedules
     * @param  list<Student>  $students
     */
    private function seedJournalsAndAttendance(
        Institution $institution,
        Semester $semester,
        array $schedules,
        array $students
    ): void {
        $byClass = [];
        foreach ($students as $student) {
            $byClass[$student->class_id][] = $student;
        }

        // Ambil 2 jadwal per kelas (periode 1 Senin & Selasa) agar maksimal ~12 jurnal
        $picked = [];
        $seenClass = [];
        foreach ($schedules as $schedule) {
            $classId = $schedule->class_id;
            $seenClass[$classId] = ($seenClass[$classId] ?? 0);
            if ($seenClass[$classId] >= 2) {
                continue;
            }
            if ((int) $schedule->period !== 1) {
                continue;
            }
            if (!in_array((int) $schedule->day_of_week, [1, 2], true)) {
                continue;
            }
            $picked[] = $schedule;
            $seenClass[$classId]++;
        }

        foreach ($picked as $i => $schedule) {
            $journalDate = now()->startOfWeek()->addDays(((int) $schedule->day_of_week) - 1);
            if ($journalDate->isFuture()) {
                $journalDate = $journalDate->subWeek();
            }

            $journal = TeachingJournal::create([
                'institution_id' => $institution->id,
                'semester_id' => $semester->id,
                'lesson_schedule_id' => $schedule->id,
                'class_id' => $schedule->class_id,
                'subject_id' => $schedule->subject_id,
                'employee_id' => $schedule->employee_id,
                'journal_date' => $journalDate->toDateString(),
                'period' => $schedule->period,
                'material_taught' => 'Materi pembelajaran demo #' . ($i + 1),
                'attendance_notes' => 'Absensi tercatat otomatis (demo)',
                'notes' => 'Jurnal mengajar contoh untuk demo',
            ]);

            foreach ($byClass[$schedule->class_id] ?? [] as $si => $student) {
                $status = StudentAttendance::STATUS_HADIR;
                if ($si === 0) {
                    $status = StudentAttendance::STATUS_SAKIT;
                } elseif ($si === 1) {
                    $status = StudentAttendance::STATUS_IZIN;
                } elseif ($si === 9) {
                    $status = StudentAttendance::STATUS_ALPHA;
                }

                StudentAttendance::create([
                    'institution_id' => $institution->id,
                    'teaching_journal_id' => $journal->id,
                    'student_id' => $student->id,
                    'status' => $status,
                ]);
            }
        }
    }

    /**
     * @param  list<SchoolClass>  $classes
     * @param  list<Subject>  $subjects
     * @param  list<Student>  $students
     * @param  list<Employee>  $teachers
     */
    private function seedGrades(
        Institution $institution,
        AcademicYear $academicYear,
        Semester $semester,
        array $classes,
        array $subjects,
        array $students,
        array $teachers
    ): void {
        $byClass = [];
        foreach ($students as $student) {
            $byClass[$student->class_id][] = $student;
        }

        foreach ($classes as $ci => $class) {
            $classStudents = $byClass[$class->id] ?? [];
            if ($classStudents === []) {
                continue;
            }

            // 3 mapel pertama × UH/UTS/UAS/nilai akhir agar raport tidak kosong
            foreach (array_slice($subjects, 0, 3) as $si => $subject) {
                $teacher = $teachers[($ci + $si) % min(6, count($teachers))];
                foreach ($classStudents as $sti => $student) {
                    $base = 70 + (($sti + $si + $ci) % 26);
                    $uh = min(100, $base);
                    $uts = min(100, $base + 2);
                    $uas = min(100, $base + 4);
                    $akhir = round(($uh + $uts + $uas) / 3, 2);

                    foreach ([
                        [Grade::penilaianType(1), $uh, 'Nilai penilaian 1 demo'],
                        [Grade::TYPE_UTS, $uts, 'Nilai UTS demo'],
                        [Grade::TYPE_UAS, $uas, 'Nilai UAS demo'],
                        [Grade::TYPE_NILAI_AKHIR, $akhir, 'Nilai akhir demo'],
                    ] as [$type, $value, $notes]) {
                        Grade::create([
                            'institution_id' => $institution->id,
                            'academic_year_id' => $academicYear->id,
                            'semester_id' => $semester->id,
                            'class_id' => $class->id,
                            'subject_id' => $subject->id,
                            'student_id' => $student->id,
                            'employee_id' => $teacher->id,
                            'grade_type' => $type,
                            'value' => $value,
                            'notes' => $notes,
                        ]);
                    }
                }
            }
        }

        foreach ($subjects as $subject) {
            foreach ([10, 11, 12] as $gradeLevel) {
                SubjectKkm::create([
                    'institution_id' => $institution->id,
                    'subject_id' => $subject->id,
                    'grade' => $gradeLevel,
                    'semester_id' => $semester->id,
                    'kkm' => 75,
                    'set_by_employee_id' => $teachers[2]->id ?? $teachers[0]->id,
                ]);
            }
        }
    }

    /**
     * @param  list<Student>  $students
     */
    private function seedAchievements(
        Institution $institution,
        AcademicYear $academicYear,
        Semester $semester,
        array $students,
        User $admin
    ): void {
        $typeDefs = [
            [
                'code' => 'OLM',
                'name' => 'Juara olimpiade',
                'point_value' => 20,
                'category' => 'akademik',
                'purpose' => AchievementType::PURPOSE_AKREDITASI,
                'level_point_values' => [
                    'sekolah' => 5,
                    'kabupaten' => 10,
                    'provinsi' => 15,
                    'nasional' => 20,
                    'internasional' => 30,
                ],
            ],
            [
                'code' => 'ORK',
                'name' => 'Juara olahraga',
                'point_value' => 15,
                'category' => 'non_akademik',
                'purpose' => AchievementType::PURPOSE_APRESIASI,
                'level_point_values' => [
                    'sekolah' => 3,
                    'kabupaten' => 8,
                    'provinsi' => 12,
                    'nasional' => 15,
                    'internasional' => 25,
                ],
            ],
            [
                'code' => 'SEN',
                'name' => 'Juara seni',
                'point_value' => 15,
                'category' => 'non_akademik',
                'purpose' => AchievementType::PURPOSE_AKREDITASI,
                'level_point_values' => [
                    'sekolah' => 3,
                    'kabupaten' => 8,
                    'provinsi' => 12,
                    'nasional' => 15,
                    'internasional' => 25,
                ],
            ],
        ];

        $types = [];
        foreach ($typeDefs as $def) {
            $types[] = AchievementType::create([
                'institution_id' => $institution->id,
                'name' => $def['name'],
                'code' => $def['code'],
                'point_value' => $def['point_value'],
                'level_point_values' => $def['level_point_values'],
                'category' => $def['category'],
                'purpose' => $def['purpose'],
                'description' => $def['name'] . ' (demo)',
                'is_active' => true,
            ]);
        }

        $levels = ['sekolah', 'kabupaten', 'provinsi', 'nasional'];
        $ranks = ['juara_1', 'juara_2', 'juara_3', 'finalis'];
        $titles = [
            'Olimpiade Matematika',
            'Lomba Basket Antar Sekolah',
            'Festival Seni Budaya',
            'Olimpiade Fisika',
            'Kejuaraan Futsal',
            'Lomba Paduan Suara',
        ];

        for ($i = 0; $i < 6; $i++) {
            $type = $types[$i % count($types)];
            $student = $students[$i * 7] ?? $students[0];
            $level = $levels[$i % count($levels)];
            Achievement::create([
                'institution_id' => $institution->id,
                'student_id' => $student->id,
                'achievement_type_id' => $type->id,
                'purpose' => $type->purpose,
                'title' => $titles[$i],
                'level' => $level,
                'rank' => $ranks[$i % count($ranks)],
                'given_by' => $admin->id,
                'achievement_date' => now()->subDays($i * 3)->toDateString(),
                'point_value' => $type->resolvePointValue($level),
                'notes' => 'Prestasi demo #' . ($i + 1),
                'status' => Achievement::STATUS_DICATAT,
                'reviewed_by' => $admin->id,
                'reviewed_at' => now()->subDays($i * 3),
                'academic_year_id' => $academicYear->id,
                'semester_id' => $semester->id,
            ]);
        }
    }

    /**
     * @param  list<Employee>  $teachers
     */
    private function seedTeacherAchievements(
        Institution $institution,
        AcademicYear $academicYear,
        Semester $semester,
        array $teachers,
        User $admin
    ): void {
        $typeDefs = [
            ['code' => 'WRK', 'name' => 'Workshop / pelatihan', 'point_value' => 10, 'category' => 'pengembangan'],
            ['code' => 'INOV', 'name' => 'Inovasi pembelajaran', 'point_value' => 20, 'category' => 'inovasi'],
            ['code' => 'PENG', 'name' => 'Pengabdian masyarakat', 'point_value' => 15, 'category' => 'pengabdian'],
        ];

        $types = [];
        foreach ($typeDefs as $i => $def) {
            $types[] = TeacherAchievementType::create([
                'institution_id' => $institution->id,
                'name' => $def['name'],
                'code' => $def['code'],
                'point_value' => $def['point_value'],
                'category' => $def['category'],
                'description' => $def['name'] . ' (demo)',
                'sort_order' => $i + 1,
                'is_active' => true,
            ]);
        }

        $mapelTeachers = array_slice($teachers, 0, 6);
        for ($i = 0; $i < 5; $i++) {
            $type = $types[$i % count($types)];
            $employee = $mapelTeachers[$i % count($mapelTeachers)];
            TeacherAchievement::create([
                'institution_id' => $institution->id,
                'employee_id' => $employee->id,
                'achievement_type_id' => $type->id,
                'title' => $type->name . ' — contoh demo',
                'achievement_date' => now()->subDays($i * 5)->toDateString(),
                'point_value' => $type->point_value,
                'level' => $i % 2 === 0 ? 'sekolah' : 'kabupaten',
                'notes' => 'Prestasi guru demo #' . ($i + 1),
                'status' => TeacherAchievement::STATUS_APPROVED,
                'submitted_by' => $admin->id,
                'given_by' => $admin->id,
                'reviewed_by' => $admin->id,
                'reviewed_at' => now()->subDays($i * 5),
                'academic_year_id' => $academicYear->id,
                'semester_id' => $semester->id,
            ]);
        }
    }

    /**
     * @param  list<Employee>  $teachers
     * @param  list<Student>  $students
     * @param  list<Room>  $rooms
     */
    private function seedExtracurriculars(
        Institution $institution,
        AcademicYear $academicYear,
        Semester $semester,
        array $teachers,
        array $students,
        array $rooms
    ): void {
        $defs = [
            ['name' => 'Pramuka', 'days' => [5], 'start' => '14:00', 'end' => '16:00', 'supervisor' => 0],
            ['name' => 'Futsal', 'days' => [3], 'start' => '15:00', 'end' => '17:00', 'supervisor' => 1],
            ['name' => 'Paduan Suara', 'days' => [2], 'start' => '14:30', 'end' => '16:00', 'supervisor' => 2],
        ];

        foreach ($defs as $di => $def) {
            $ekskul = Extracurricular::create([
                'institution_id' => $institution->id,
                'name' => $def['name'],
                'description' => $def['name'] . ' (ekskul demo)',
                'supervisor_employee_id' => $teachers[$def['supervisor']]->id ?? $teachers[0]->id,
                'academic_year_id' => $academicYear->id,
                'semester_id' => $semester->id,
                'capacity' => 30,
                'kkm' => 75,
                'status' => 'Aktif',
                'days_of_week' => $def['days'],
                'start_time' => $def['start'],
                'end_time' => $def['end'],
                'room_id' => $rooms[min($di, count($rooms) - 1)]->id ?? null,
                'is_outdoor' => $def['name'] === 'Futsal',
                'is_pramuka' => $def['name'] === 'Pramuka',
            ]);
            ExtracurricularAccess::grantAccessForEmployee($ekskul->supervisor_employee_id);

            $members = [];
            for ($i = 0; $i < 8; $i++) {
                $student = $students[($di * 8) + $i] ?? null;
                if (!$student) {
                    break;
                }
                ExtracurricularStudent::create([
                    'extracurricular_id' => $ekskul->id,
                    'student_id' => $student->id,
                    'academic_year_id' => $academicYear->id,
                    'semester_id' => $semester->id,
                    'joined_at' => now()->subMonths(2)->toDateString(),
                    'status' => 'aktif',
                ]);
                $members[] = $student;
            }

            $supervisorId = $ekskul->supervisor_employee_id;
            for ($s = 1; $s <= 2; $s++) {
                $sessionDate = now()->startOfWeek()->subWeeks($s)->addDays(($def['days'][0] ?? 1) - 1);
                $session = ExtracurricularSession::create([
                    'institution_id' => $institution->id,
                    'extracurricular_id' => $ekskul->id,
                    'semester_id' => $semester->id,
                    'session_date' => $sessionDate->toDateString(),
                    'start_time' => $def['start'],
                    'end_time' => $def['end'],
                    'topic' => 'Latihan ' . $def['name'] . ' #' . $s,
                    'notes' => 'Sesi ekskul demo',
                    'recorded_by' => $supervisorId,
                ]);

                foreach ($members as $mi => $member) {
                    $status = ExtracurricularAttendance::STATUSES[$mi % 4];
                    ExtracurricularAttendance::create([
                        'session_id' => $session->id,
                        'student_id' => $member->id,
                        'status' => $status,
                    ]);
                }
            }

            foreach ($members as $mi => $member) {
                $score = 78 + ($mi % 18);
                ExtracurricularGrade::create([
                    'institution_id' => $institution->id,
                    'extracurricular_id' => $ekskul->id,
                    'student_id' => $member->id,
                    'semester_id' => $semester->id,
                    'academic_year_id' => $academicYear->id,
                    'score' => $score,
                    'predicate' => ExtracurricularGrade::predicateFromScore((float) $score, $ekskul->kkm),
                    'notes' => 'Nilai ekskul demo',
                    'recorded_by' => $supervisorId,
                ]);
            }
        }
    }

    /**
     * @param  list<InventoryCategory>  $categories
     * @param  list<Room>  $rooms
     * @return list<InventoryItem>
     */
    private function seedInventoryItems(
        int $institutionId,
        array $categories,
        array $rooms,
        User $admin
    ): array {
        $defs = [
            ['code' => 'PC-01', 'name' => 'Komputer Lab', 'cat' => 0, 'qty' => 10],
            ['code' => 'PRJ-01', 'name' => 'Proyektor', 'cat' => 0, 'qty' => 4],
            ['code' => 'MJ-01', 'name' => 'Meja siswa', 'cat' => 1, 'qty' => 120],
            ['code' => 'KR-01', 'name' => 'Kursi siswa', 'cat' => 1, 'qty' => 120],
            ['code' => 'MIC-01', 'name' => 'Mikroskop', 'cat' => 2, 'qty' => 12],
            ['code' => 'BOLA-01', 'name' => 'Bola futsal', 'cat' => 4, 'qty' => 8],
        ];

        $items = [];
        foreach ($defs as $i => $def) {
            $category = $categories[$def['cat']] ?? $categories[0];
            $items[] = InventoryItem::create([
                'institution_id' => $institutionId,
                'category_id' => $category->id,
                'code' => $def['code'],
                'name' => $def['name'],
                'brand' => 'DemoBrand',
                'condition' => 'Baik',
                'status' => 'Tersedia',
                'quantity' => $def['qty'],
                'unit' => 'unit',
                'room_id' => $rooms[$i % count($rooms)]->id ?? null,
                'purchase_date' => now()->subYears(1)->toDateString(),
                'purchase_price' => 1000000 + ($i * 250000),
                'description' => $def['name'] . ' inventaris demo',
                'created_by' => $admin->id,
            ]);
        }

        return $items;
    }

    /**
     * @param  list<InventoryItem>  $items
     * @param  list<Room>  $rooms
     */
    private function seedIndividualInventoryAssets(
        Institution $institution,
        array $items,
        array $rooms,
        User $admin
    ): void {
        $mikroskop = collect($items)->first(fn ($i) => $i->code === 'MIC-01');
        if (! $mikroskop) {
            return;
        }

        $mikroskop->update([
            'tracking_type' => InventoryCatalog::TRACKING_INDIVIDUAL,
            'identity_status' => InventoryCatalog::IDENTITY_COMPLETE,
            'master_code' => $mikroskop->code,
            'quantity' => 0,
            'updated_by' => $admin->id,
        ]);

        /** @var \App\Services\InventoryAssetService $assetService */
        $assetService = app(\App\Services\InventoryAssetService::class);
        $labRoom = collect($rooms)->first(fn ($r) => str_contains(strtolower($r->name ?? ''), 'lab')) ?? ($rooms[0] ?? null);

        $assets = $assetService->createForItem($mikroskop->fresh(), 3, $admin->id, [
            'room_id' => $labRoom?->id,
            'condition' => 'Baik',
        ]);

        if (count($assets) >= 2 && ($rooms[1] ?? null)) {
            $assetService->transfer($assets[1], $rooms[1]->id, $admin->id, now()->subDays(10)->toDateString(), 'DEMO-MUT-001', 'Mutasi demo mikroskop');
        }

        $opname = InventoryStockOpname::create([
            'institution_id' => $institution->id,
            'opname_number' => 'OPNAME/' . date('Y') . '/001',
            'opname_date' => now()->subDays(7)->toDateString(),
            'room_id' => null,
            'status' => 'finalized',
            'notes' => 'Stock opname demo (sudah difinalisasi)',
            'finalized_at' => now()->subDays(6),
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
        ]);

        $stockItems = InventoryItem::where('institution_id', $institution->id)
            ->where('tracking_type', InventoryCatalog::TRACKING_STOCK)
            ->limit(3)
            ->get();

        foreach ($stockItems as $item) {
            InventoryStockOpnameLine::create([
                'opname_id' => $opname->id,
                'item_id' => $item->id,
                'book_quantity' => (int) $item->quantity,
                'counted_quantity' => (int) $item->quantity,
                'variance' => 0,
            ]);
        }
    }

    /**
     * @param  list<Student>  $students
     */
    private function seedLibrary(
        Institution $institution,
        array $students,
        User $admin
    ): void {
        $category = LibraryBookCategory::create([
            'institution_id' => $institution->id,
            'code' => 'UMUM',
            'name' => 'Umum',
            'description' => 'Koleksi umum demo',
            'is_active' => true,
        ]);

        $fiksi = LibraryBookCategory::create([
            'institution_id' => $institution->id,
            'code' => 'FIKS',
            'name' => 'Fiksi',
            'description' => 'Koleksi fiksi demo',
            'is_active' => true,
        ]);

        $bookDefs = [
            ['title' => 'Matematika SMA Kelas X', 'author' => 'Tim Demo', 'cat' => $category, 'copies' => 3],
            ['title' => 'Bahasa Indonesia Kelas XI', 'author' => 'Tim Demo', 'cat' => $category, 'copies' => 2],
            ['title' => 'Fisika Dasar', 'author' => 'Tim Sains Demo', 'cat' => $category, 'copies' => 2],
            ['title' => 'Novel Remaja Harapan', 'author' => 'Penulis Demo', 'cat' => $fiksi, 'copies' => 2],
            ['title' => 'Sejarah Indonesia Modern', 'author' => 'Sejarawan Demo', 'cat' => $category, 'copies' => 2],
        ];

        $loanableCopies = [];
        foreach ($bookDefs as $bi => $def) {
            $book = LibraryBook::create([
                'institution_id' => $institution->id,
                'category_id' => $def['cat']->id,
                'isbn' => sprintf('978602%07d', 1000000 + $bi),
                'title' => $def['title'],
                'author' => $def['author'],
                'publisher' => 'Penerbit Demo',
                'year' => 2022 + ($bi % 3),
                'language' => 'id',
                'pages' => 120 + ($bi * 20),
                'shelf_code' => 'A-' . ($bi + 1),
                'description' => $def['title'] . ' (koleksi demo)',
                'created_by' => $admin->id,
            ]);

            for ($c = 1; $c <= $def['copies']; $c++) {
                $copy = LibraryBookCopy::create([
                    'book_id' => $book->id,
                    'copy_code' => sprintf('COPY-%02d-%02d', $bi + 1, $c),
                    'status' => 'Tersedia',
                    'condition' => 'Baik',
                ]);
                if ($c === 1) {
                    $loanableCopies[] = $copy;
                }
            }
        }

        foreach (array_slice($loanableCopies, 0, 3) as $i => $copy) {
            $student = $students[$i * 5] ?? $students[0];
            $active = $i < 2;
            LibraryLoan::create([
                'institution_id' => $institution->id,
                'copy_id' => $copy->id,
                'borrower_type' => 'Student',
                'borrower_id' => $student->id,
                'borrower_name' => $student->name,
                'borrower_identifier' => $student->nis,
                'loan_date' => now()->subDays(7 - $i)->toDateString(),
                'due_date' => now()->addDays(7 + $i)->toDateString(),
                'returned_at' => $active ? null : now()->subDay(),
                'status' => $active ? 'Dipinjam' : 'Dikembalikan',
                'fine_amount' => 0,
                'notes' => 'Peminjaman demo',
                'created_by' => $admin->id,
            ]);
            if ($active) {
                $copy->update(['status' => 'Dipinjam']);
            }
        }
    }

    /**
     * @param  list<string>  $keys
     */
    private function syncPermissions(User $user, array $keys): void
    {
        $ids = [];
        foreach (array_unique($keys) as $key) {
            $permission = Permission::firstOrCreate(
                ['key' => $key],
                ['label' => ucfirst(str_replace('_', ' ', $key))]
            );
            $ids[] = $permission->id;
        }
        $user->permissions()->sync($ids);
    }

    /**
     * @param  list<Student>  $students
     */
    private function seedParent(Institution $institution, array $students): void
    {
        $child = $students[0] ?? null;
        if (!$child) {
            return;
        }

        $parent = User::create([
            'institution_id' => $institution->id,
            'name' => 'Orang Tua Demo',
            'email' => config('demo.parent_email', 'ortu@demo.servrin.id'),
            'password' => $this->staffPassword(),
            'role' => 'parent',
            'email_verified_at' => now(),
            'is_active' => true,
            'must_change_password' => false,
        ]);

        ParentLink::create([
            'user_id' => $parent->id,
            'student_id' => $child->id,
            'institution_id' => $institution->id,
            'relation' => 'ayah',
        ]);
    }

    private function seedAcademicCalendar(
        Institution $institution,
        AcademicYear $academicYear,
        Semester $semester,
        User $admin
    ): ?AcademicCalendarEvent {
        $defs = [
            [
                'title' => 'Rapat Koordinasi Guru',
                'type' => 'Kegiatan',
                'start' => now()->startOfMonth()->addDays(2),
                'end' => null,
                'color' => '#0f766e',
            ],
            [
                'title' => 'Try Out Ujian Sekolah',
                'type' => 'Ujian',
                'start' => now()->startOfMonth()->addDays(8),
                'end' => now()->startOfMonth()->addDays(10),
                'color' => '#dc2626',
            ],
            [
                'title' => 'Libur Isra Miraj',
                'type' => 'Libur',
                'start' => now()->startOfMonth()->addDays(14),
                'end' => null,
                'color' => '#16a34a',
            ],
            [
                'title' => 'Upacara Hari Kemerdekaan',
                'type' => 'Kegiatan',
                'start' => now()->month === 8 ? now()->copy()->setDate((int) now()->year, 8, 17) : now(),
                'end' => null,
                'color' => '#dc2626',
            ],
            [
                'title' => 'Ujian Tengah Semester',
                'type' => 'Ujian',
                'start' => now()->addDays(10),
                'end' => now()->addDays(16),
                'color' => '#2563eb',
            ],
            [
                'title' => 'Rapat Orang Tua',
                'type' => 'Kegiatan',
                'start' => now()->addDays(21),
                'end' => null,
                'color' => '#7c3aed',
            ],
            [
                'title' => 'Pembagian Raport',
                'type' => 'Kegiatan',
                'start' => now()->addDays(50),
                'end' => null,
                'color' => '#ca8a04',
            ],
            [
                'title' => 'Libur Semester',
                'type' => 'Libur',
                'start' => now()->addDays(55),
                'end' => now()->addDays(68),
                'color' => '#16a34a',
            ],
            [
                'title' => 'Masa Pengenalan Lingkungan Sekolah',
                'type' => 'Kegiatan',
                'start' => now()->subDays(40),
                'end' => now()->subDays(37),
                'color' => '#0f766e',
            ],
            [
                'title' => 'Workshop Literasi Digital',
                'type' => 'Other',
                'start' => now()->addDays(5),
                'end' => null,
                'color' => '#64748b',
            ],
        ];

        $created = [];
        foreach ($defs as $def) {
            $start = $def['start']->copy();
            $end = $def['end']?->copy() ?? $start->copy();
            $created[] = AcademicCalendarEvent::create([
                'institution_id' => $institution->id,
                'academic_year_id' => $academicYear->id,
                'semester_id' => $semester->id,
                'title' => $def['title'],
                'description' => $def['title'] . ' (kalender demo)',
                'event_type' => $def['type'],
                'start_date' => $start->toDateString(),
                'end_date' => $end->toDateString(),
                'is_all_day' => true,
                'reminder_days_before' => in_array($def['type'], ['Ujian', 'Libur'], true) ? [3, 7] : [1],
                'color' => $def['color'],
                'status' => 'Aktif',
                'created_by' => $admin->id,
            ]);
        }

        // Prefer event di bulan berjalan untuk notifikasi demo
        foreach ($created as $event) {
            if ($event->start_date && $event->start_date->isSameMonth(now())) {
                return $event;
            }
        }

        return $created[0] ?? null;
    }

    private function seedSchoolPosts(Institution $institution, User $admin): void
    {
        $humas = User::where('email', 'humas@demo.servrin.id')->first() ?? $admin;
        $defs = [
            [
                'type' => 'news',
                'title' => 'Selamat datang di tahun ajaran baru',
                'body' => 'SMA 1 Demo Servrin mengucapkan selamat datang kepada seluruh siswa, guru, dan orang tua. Gunakan sekolah demo ini untuk mencoba modul Servrin. Data di-reset setiap hari pukul 03:00 WIB.',
                'sort' => 3,
            ],
            [
                'type' => 'news',
                'title' => 'Pengumuman Ujian Tengah Semester',
                'body' => 'UTS akan dilaksanakan sesuai kalender akademik. Siswa diharapkan membawa alat tulis sendiri dan hadir 15 menit sebelum ujian dimulai.',
                'sort' => 2,
            ],
            [
                'type' => 'gallery',
                'title' => 'Kegiatan 17 Agustus di sekolah',
                'body' => 'Dokumentasi upacara dan lomba kemerdekaan (galeri demo tanpa foto unggahan).',
                'sort' => 1,
            ],
        ];

        foreach ($defs as $def) {
            SchoolPost::create([
                'institution_id' => $institution->id,
                'type' => $def['type'],
                'title' => $def['title'],
                'slug' => Str::slug($def['title']),
                'body' => $def['body'],
                'published_at' => now()->subDays($def['sort']),
                'is_published' => true,
                'sort' => $def['sort'],
                'created_by' => $humas->id,
            ]);
        }
    }

    private function seedPpdb(Institution $institution, AcademicYear $academicYear): void
    {
        $period = PpdbPeriod::create([
            'institution_id' => $institution->id,
            'academic_year_id' => $academicYear->id,
            'name' => 'PPDB Gelombang 1',
            'level' => 'SMA',
            'open_date' => now()->subDays(7)->toDateString(),
            'close_date' => now()->addDays(30)->toDateString(),
            're_registration_deadline' => now()->addDays(40)->toDateString(),
            'status' => 'open',
            'description' => 'Periode PPDB demo SMA 1 Demo Servrin',
            'registration_fee' => 150000,
            're_registration_fee' => 500000,
        ]);

        $zonasi = PpdbChannel::create([
            'institution_id' => $institution->id,
            'code' => 'ZONASI',
            'name' => 'Jalur Zonasi',
            'quota' => 40,
            'requirements' => 'Domisili sesuai zonasi sekolah',
            'is_active' => true,
            'sort_order' => 1,
        ]);
        $afirmasi = PpdbChannel::create([
            'institution_id' => $institution->id,
            'code' => 'AFIRMASI',
            'name' => 'Jalur Afirmasi',
            'quota' => 12,
            'requirements' => 'Kartu program bantuan pemerintah',
            'is_active' => true,
            'sort_order' => 2,
        ]);

        $names = [
            ['Raka Pratama', 'L', 'submitted', 'unpaid', $zonasi],
            ['Sinta Lestari', 'P', 'submitted', 'unpaid', $zonasi],
            ['Dimas Nugroho', 'L', 'verification', 'unpaid', $zonasi],
            ['Aulia Rahman', 'P', 'verification', 'unpaid', $afirmasi],
            ['Fajar Wijaya', 'L', 'verified', 'unpaid', $zonasi],
            ['Maya Putri', 'P', 'verified', 'paid', $afirmasi],
            ['Bagas Saputra', 'L', 'passed', 'paid', $zonasi],
            ['Nadia Wulandari', 'P', 'passed', 'unpaid', $zonasi],
            ['Hendra Gunawan', 'L', 'rejected', 'unpaid', $zonasi],
            ['Lestari Ananda', 'P', 'failed', 'unpaid', $afirmasi],
        ];

        foreach ($names as $i => [$name, $gender, $status, $payment, $channel]) {
            $paid = $payment === 'paid';
            PpdbApplicant::create([
                'ppdb_period_id' => $period->id,
                'ppdb_channel_id' => $channel->id,
                'registration_number' => sprintf('PPDB-%d-%05d', $period->id, $i + 1),
                'status' => $status,
                'name' => $name,
                'nik' => sprintf('320177%010d', $i + 1),
                'nisn' => sprintf('00%08d', 20000000 + $i + 1),
                'gender' => $gender,
                'birth_date' => sprintf('2010-%02d-%02d', ($i % 12) + 1, ($i % 27) + 1),
                'birth_place' => 'Kota Demo',
                'address' => 'Jl. Pendaftar No. ' . ($i + 1) . ', Kota Demo',
                'phone' => '08140000' . sprintf('%04d', $i + 1),
                'email' => 'pendaftar' . ($i + 1) . '@demo.servrin.id',
                'religion' => 'Islam',
                'previous_school' => 'SMP 1 Demo',
                'previous_school_npsn' => '99990002',
                'father_name' => 'Ayah ' . $name,
                'mother_name' => 'Ibu ' . $name,
                'documents_verified' => in_array($status, ['verified', 'passed'], true),
                'submitted_at' => now()->subDays(6 - min($i, 5)),
                'announcement_at' => in_array($status, ['passed', 'failed', 'rejected'], true) ? now()->subDay() : null,
                'payment_status' => $payment,
                'payment_amount' => $paid ? 150000 : 0,
                'payment_type' => $paid ? 'registration' : null,
                'paid_at' => $paid ? now()->subDays(2) : null,
                'notes' => 'Pendaftar PPDB demo #' . ($i + 1),
            ]);
        }
    }

    /**
     * @param  list<Employee>  $teachers
     */
    private function seedKepegawaian(Institution $institution, array $teachers, User $admin): void
    {
        $byEmail = [];
        foreach ($teachers as $teacher) {
            $byEmail[(string) $teacher->email] = $teacher;
        }

        $piket = $byEmail['guru02@demo.servrin.id'] ?? $teachers[1] ?? null;
        $perpus = $byEmail['guru06@demo.servrin.id'] ?? $teachers[5] ?? null;
        $ks = $byEmail[(string) config('demo.teacher_email')] ?? $teachers[0] ?? null;
        $kurikulum = $byEmail['guru03@demo.servrin.id'] ?? $teachers[2] ?? null;
        $kesiswaan = $byEmail['kesiswaan@demo.servrin.id'] ?? null;
        $tu = $byEmail['tu@demo.servrin.id'] ?? null;

        $piketUser = User::where('email', 'guru02@demo.servrin.id')->first() ?? $admin;
        $perpusUser = User::where('email', 'guru06@demo.servrin.id')->first() ?? $admin;

        if ($piket) {
            EmployeeLeaveRequest::create([
                'institution_id' => $institution->id,
                'employee_id' => $piket->id,
                'leave_type' => 'tahunan',
                'start_date' => now()->addDays(7)->toDateString(),
                'end_date' => now()->addDays(9)->toDateString(),
                'reason' => 'Keperluan keluarga (cuti demo pending)',
                'status' => 'pending',
                'requested_by' => $piketUser->id,
            ]);
        }

        if ($perpus) {
            EmployeeLeaveRequest::create([
                'institution_id' => $institution->id,
                'employee_id' => $perpus->id,
                'leave_type' => 'sakit',
                'start_date' => now()->subDays(5)->toDateString(),
                'end_date' => now()->subDays(3)->toDateString(),
                'reason' => 'Istirahat sakit (cuti demo disetujui)',
                'status' => 'approved',
                'requested_by' => $perpusUser->id,
                'approved_by' => $admin->id,
                'approved_at' => now()->subDays(5),
            ]);
        }

        $ksDecree = null;
        if ($ks) {
            $ksDecree = EmployeeDecree::create([
                'institution_id' => $institution->id,
                'employee_id' => $ks->id,
                'decree_type' => 'jabatan',
                'number' => '800/SK-KS/2022',
                'title' => 'SK Kepala Sekolah',
                'decree_date' => '2022-07-01',
                'effective_date' => '2022-07-15',
                'description' => 'Pengangkatan Kepala Sekolah demo',
                'created_by' => $admin->id,
            ]);
        }

        $kurikulumDecree = null;
        if ($kurikulum) {
            $kurikulumDecree = EmployeeDecree::create([
                'institution_id' => $institution->id,
                'employee_id' => $kurikulum->id,
                'decree_type' => 'tugas_tambahan',
                'number' => '801/SK-WAKA/2024',
                'title' => 'SK Wakil Kepala Sekolah Kurikulum',
                'decree_date' => '2024-07-01',
                'effective_date' => '2024-07-15',
                'description' => 'Penugasan Waka Kurikulum demo',
                'created_by' => $admin->id,
            ]);
        }

        $assignments = [
            [$ks, 'kepala_sekolah', $ksDecree, '2022-07-15'],
            [$kurikulum, 'waka_kurikulum', $kurikulumDecree, '2024-07-15'],
            [$kesiswaan, 'waka_kesiswaan', null, '2023-07-15'],
            [$tu, 'kepala_tata_usaha', null, '2021-07-15'],
        ];

        foreach ($assignments as [$employee, $key, $decree, $started]) {
            if (!$employee) {
                continue;
            }
            $position = StructuralPosition::where('key', $key)->first();
            if (!$position) {
                continue;
            }
            EmployeeStructuralPosition::create([
                'institution_id' => $institution->id,
                'employee_id' => $employee->id,
                'structural_position_id' => $position->id,
                'employee_decree_id' => $decree?->id,
                'started_at' => $started,
                'decree_number' => $decree?->number,
                'notes' => 'Jabatan struktural demo',
                'created_by' => $admin->id,
            ]);
        }
    }

    /**
     * @param  list<Employee>  $teachers
     */
    private function seedEmployeeAttendance(Institution $institution, array $teachers): void
    {
        $days = [];
        $cursor = now()->startOfDay();
        while (count($days) < 5) {
            if ($cursor->isWeekday()) {
                $days[] = $cursor->copy();
            }
            $cursor->subDay();
        }

        foreach ($teachers as $ti => $teacher) {
            foreach ($days as $di => $day) {
                $status = EmployeeAttendance::STATUS_HADIR;
                if ($ti === 5 && $di === 0) {
                    $status = EmployeeAttendance::STATUS_SAKIT;
                } elseif ($ti === 1 && $di === 1) {
                    $status = EmployeeAttendance::STATUS_IZIN;
                } elseif ($ti === 4 && $di === 2) {
                    $status = EmployeeAttendance::STATUS_DINAS_LUAR;
                }

                EmployeeAttendance::create([
                    'institution_id' => $institution->id,
                    'employee_id' => $teacher->id,
                    'date' => $day->toDateString(),
                    'status' => $status,
                    'check_in_time' => $status === EmployeeAttendance::STATUS_HADIR ? '07:15' : null,
                    'check_out_time' => $status === EmployeeAttendance::STATUS_HADIR ? '14:00' : null,
                    'notes' => $status === EmployeeAttendance::STATUS_HADIR ? null : 'Absensi pegawai demo',
                ]);
            }
        }
    }

    /**
     * @param  array{masuk: list<CorrespondenceCategory>, keluar: list<CorrespondenceCategory>, internal: list<CorrespondenceCategory>}  $categories
     */
    private function seedCorrespondence(Institution $institution, array $categories, User $admin): void
    {
        $masukCat = $categories['masuk'][0] ?? null;
        $keluarCat = $categories['keluar'][0] ?? null;
        $internalCat = $categories['internal'][0] ?? null;
        $kurikulumUser = User::where('email', 'guru03@demo.servrin.id')->first();

        $incoming = Correspondence::create([
            'institution_id' => $institution->id,
            'type' => 'masuk',
            'letter_type_code' => '16',
            'letter_number' => '005/UND/DISDIK/2026',
            'reference_number' => 'UND-2026-005',
            'subject' => 'Undangan rapat dinas pendidikan',
            'from' => 'Dinas Pendidikan Kota Demo',
            'date' => now()->subDays(4)->toDateString(),
            'received_date' => now()->subDays(3)->toDateString(),
            'priority' => 'penting',
            'status' => 'approved',
            'category_id' => $masukCat?->id,
            'created_by' => $admin->id,
            'approved_by' => $admin->id,
            'approved_at' => now()->subDays(3),
            'description' => 'Undangan rapat koordinasi sekolah (surat masuk demo)',
        ]);

        Correspondence::create([
            'institution_id' => $institution->id,
            'type' => 'keluar',
            'letter_type_code' => '01',
            'letter_number' => '421/001/SMA-DEMO/2026',
            'subject' => 'Pemberitahuan kegiatan UTS',
            'to' => 'Orang tua / wali siswa',
            'date' => now()->subDays(2)->toDateString(),
            'priority' => 'biasa',
            'status' => 'sent',
            'category_id' => $keluarCat?->id,
            'created_by' => $admin->id,
            'approved_by' => $admin->id,
            'approved_at' => now()->subDays(2),
            'description' => 'Surat edaran UTS (surat keluar demo)',
        ]);

        Correspondence::create([
            'institution_id' => $institution->id,
            'type' => 'internal',
            'letter_type_code' => '16',
            'letter_number' => '422/INT/SMA-DEMO/2026',
            'subject' => 'Memo jadwal piket guru',
            'to' => 'Seluruh guru',
            'date' => now()->subDay()->toDateString(),
            'priority' => 'biasa',
            'status' => 'draft',
            'category_id' => $internalCat?->id,
            'created_by' => $admin->id,
            'description' => 'Memo internal demo',
        ]);

        CorrespondenceHistory::create([
            'correspondence_id' => $incoming->id,
            'user_id' => $admin->id,
            'action' => 'created',
            'notes' => 'Surat masuk demo dicatat',
        ]);

        if ($kurikulumUser) {
            CorrespondenceDisposition::create([
                'correspondence_id' => $incoming->id,
                'from_user_id' => $admin->id,
                'to_user_id' => $kurikulumUser->id,
                'instruction' => 'Mohon ditindaklanjuti dan siapkan undangan internal.',
                'status' => 'pending',
            ]);
        }
    }

    /**
     * @param  list<Employee>  $teachers
     */
    private function seedLabs(Institution $institution, array $teachers, User $admin, ?Building $building = null): void
    {
        $labHead = null;
        foreach ($teachers as $teacher) {
            if ($teacher->email === 'lab@demo.servrin.id') {
                $labHead = $teacher;
                break;
            }
        }

        $ipa = Room::create([
            'institution_id' => $institution->id,
            'building_id' => $building?->id,
            'name' => 'Lab IPA',
            'code' => 'R-LAB-IPA',
            'type' => 'Laboratorium',
            'lab_type' => 'IPA',
            'floor' => 2,
            'capacity' => 24,
            'condition' => 'Baik',
            'description' => 'Laboratorium IPA demo',
            'responsible_employee_id' => $labHead?->id,
        ]);
        $komputer = Room::create([
            'institution_id' => $institution->id,
            'building_id' => $building?->id,
            'name' => 'Lab Komputer',
            'code' => 'R-LAB-TIK',
            'type' => 'Laboratorium',
            'lab_type' => 'Komputer',
            'floor' => 2,
            'capacity' => 32,
            'condition' => 'Baik',
            'description' => 'Laboratorium komputer demo',
            'responsible_employee_id' => $labHead?->id,
        ]);

        $requester = $teachers[2] ?? $teachers[0] ?? null;
        $requesterUser = User::where('email', 'guru03@demo.servrin.id')->first() ?? $admin;

        LabBooking::create([
            'institution_id' => $institution->id,
            'room_id' => $ipa->id,
            'requester_employee_id' => $requester?->id,
            'requester_name' => $requester?->name ?? 'Waka Kurikulum Demo',
            'purpose' => 'Praktikum fisika kelas XI',
            'date' => now()->addDays(3)->toDateString(),
            'start_time' => '08:00',
            'end_time' => '10:00',
            'status' => 'approved',
            'approved_by' => $admin->id,
            'approved_at' => now()->subDay(),
            'created_by' => $requesterUser->id,
        ]);
        LabBooking::create([
            'institution_id' => $institution->id,
            'room_id' => $komputer->id,
            'requester_employee_id' => $requester?->id,
            'requester_name' => $requester?->name ?? 'Waka Kurikulum Demo',
            'purpose' => 'Ujian TIK kelas X',
            'date' => now()->addDays(8)->toDateString(),
            'start_time' => '10:00',
            'end_time' => '12:00',
            'status' => 'pending',
            'created_by' => $requesterUser->id,
        ]);
        LabBooking::create([
            'institution_id' => $institution->id,
            'room_id' => $ipa->id,
            'requester_employee_id' => $labHead?->id ?? $requester?->id,
            'requester_name' => $labHead?->name ?? 'Kepala Lab Demo',
            'purpose' => 'Kalibrasi alat lab',
            'date' => now()->subDays(2)->toDateString(),
            'start_time' => '13:00',
            'end_time' => '15:00',
            'status' => 'approved',
            'approved_by' => $admin->id,
            'approved_at' => now()->subDays(3),
            'created_by' => $admin->id,
        ]);
    }

    /**
     * @param  list<SchoolClass>  $classes
     */
    private function seedAlumni(
        Institution $institution,
        AcademicYear $academicYear,
        Semester $semester,
        array $classes,
        User $admin
    ): void {
        $xii = $classes[4] ?? $classes[0] ?? null;
        $yearLatest = max(2024, (int) now()->year - 1);
        $yearPrev = $yearLatest - 1;
        $yearOlder = $yearLatest - 2;

        $alumniDefs = [
            ['Rina Maharani', 'P', 'Perguruan_Tinggi', 'Universitas Demo', 'Kedokteran', $yearLatest],
            ['Yoga Firmansyah', 'L', 'Perguruan_Tinggi', 'ITB Demo', 'Teknik Informatika', $yearLatest],
            ['Putri Anggraini', 'P', 'Kerja', 'Bank Demo', 'Customer Service', $yearLatest],
            ['Andi Kurniawan', 'L', 'Wirausaha', 'Toko ATK Demo', 'Pemilik', $yearPrev],
            ['Sari Melati', 'P', 'Perguruan_Tinggi', 'UNPAD Demo', 'Psikologi', $yearPrev],
            ['Reza Hakim', 'L', 'Kerja', 'PT Demo Digital', 'Staff IT', $yearPrev],
            ['Dewi Lestari', 'P', 'Perguruan_Tinggi', 'UI Demo', 'Hukum', $yearOlder],
            ['Fajar Nugroho', 'L', 'Kerja', 'Pemda Demo', 'Staf Administrasi', $yearOlder],
            ['Nadia Putri', 'P', 'Perguruan_Tinggi', 'UGM Demo', 'Farmasi', $yearOlder],
        ];

        $alumni = [];
        foreach ($alumniDefs as $i => [$name, $gender, $destType, $destName, $program, $year]) {
            $student = Student::create([
                'institution_id' => $institution->id,
                'nik' => sprintf('320197%010d', $i + 1),
                'nis' => sprintf('A%04d', $i + 1),
                'nisn' => sprintf('00%08d', 30000000 + $i + 1),
                'name' => $name,
                'gender' => $gender,
                'birth_date' => sprintf('%04d-%02d-%02d', 2007 - ($yearLatest - $year), ($i % 12) + 1, ($i % 27) + 1),
                'birth_place' => 'Kota Demo',
                'address' => 'Jl. Alumni No. ' . ($i + 1) . ', Kota Demo',
                'religion' => 'Islam',
                'tingkat' => 12,
                'class_id' => $xii?->id,
                'academic_year' => $academicYear->code,
                'academic_year_id' => $academicYear->id,
                'semester_id' => $semester->id,
                'status' => 'Lulus',
                'graduation_year' => $year,
                'father_name' => 'Ayah ' . $name,
                'mother_name' => 'Ibu ' . $name,
            ]);

            $dest = AlumniDestination::create([
                'institution_id' => $institution->id,
                'student_id' => $student->id,
                'destination_type' => $destType,
                'destination_name' => $destName,
                'program_or_position' => $program,
                'year_entered' => $year,
                'notes' => $i === 2 ? 'Menunggu verifikasi petugas (demo pending)' : 'Destinasi alumni demo',
                'status' => $i === 2 ? AlumniDestination::STATUS_PENDING : AlumniDestination::STATUS_APPROVED,
                'source' => AlumniDestination::SOURCE_MANUAL,
                'reviewed_by' => $i === 2 ? null : $admin->id,
                'reviewed_at' => $i === 2 ? null : now()->subDays(10 + $i),
            ]);

            $alumni[] = $student;
            unset($dest);
        }

        $pickupDefs = [
            [0, true, true, true, 'Surat Keterangan Lulus, Transkrip'],
            [1, true, true, false, 'Piagam Prestasi'],
            [3, true, false, true, null],
            [6, true, true, false, 'SKHUN Cadangan, Kartu Pelajar'],
        ];

        foreach ($pickupDefs as $i => [$idx, $ijazah, $raport, $skhun, $lainnya]) {
            $student = $alumni[$idx] ?? null;
            if (! $student) {
                continue;
            }
            DocumentPickup::create([
                'institution_id' => $institution->id,
                'student_id' => $student->id,
                'pickup_date' => now()->subDays(25 - ($i * 4)),
                'taken_ijazah' => $ijazah,
                'taken_raport' => $raport,
                'taken_skhun' => $skhun,
                'nomor_ijazah' => sprintf('DN-%02d/%d', $i + 1, $student->graduation_year),
                'kode_blangko' => sprintf('BL-%04d', $i + 1),
                'dokumen_lainnya' => $lainnya,
                'received_by' => 'Petugas TU Demo',
                'notes' => 'Pengambilan ijazah demo',
                'created_by' => $admin->id,
            ]);
        }
    }

    /**
     * @param  list<Employee>  $teachers
     */
    private function seedPayroll(Institution $institution, array $teachers, User $admin): void
    {
        if (! Schema::hasTable('payroll_components')) {
            return;
        }

        app(PayrollService::class)->ensureDefaultComponents($institution->id);

        $transport = PayrollComponent::query()
            ->where('institution_id', $institution->id)
            ->where('code', PayrollComponent::CODE_TRANSPORT)
            ->first();
        if ($transport) {
            $transport->update(['default_amount' => 300000]);
        }

        $baseSalaries = [4500000, 5200000, 4800000, 5500000, 5000000];
        foreach (array_slice($teachers, 0, 5) as $i => $teacher) {
            PayrollEmployeeProfile::updateOrCreate(
                [
                    'institution_id' => $institution->id,
                    'employee_id' => $teacher->id,
                ],
                [
                    'base_salary' => $baseSalaries[$i % count($baseSalaries)],
                    'payment_method' => 'transfer',
                    'bank_name' => 'Bank Demo',
                    'bank_account' => sprintf('77%08d', $i + 1),
                    'effective_from' => now()->startOfYear()->toDateString(),
                    'notes' => 'Profil gaji demo',
                ]
            );
        }

        $month = (int) now()->month;
        $year = (int) now()->year;
        PayrollPeriod::firstOrCreate(
            [
                'institution_id' => $institution->id,
                'year' => $year,
                'month' => $month,
            ],
            [
                'label' => now()->translatedFormat('F Y'),
                'start_date' => now()->startOfMonth()->toDateString(),
                'end_date' => now()->endOfMonth()->toDateString(),
                'working_days' => 22,
                'status' => PayrollPeriod::STATUS_OPEN,
            ]
        );

        // Tunjangan jabatan struktural (modul baru penggajian)
        if (Schema::hasTable('payroll_position_allowances') && Schema::hasTable('structural_positions')) {
            $allowanceMap = [
                'kepala_sekolah' => 1500000,
                'waka_kurikulum' => 750000,
                'waka_kesiswaan' => 750000,
                'waka_sarpras' => 750000,
            ];
            foreach ($allowanceMap as $key => $amount) {
                $position = StructuralPosition::query()->where('key', $key)->active()->first();
                if (! $position) {
                    continue;
                }
                PayrollPositionAllowance::updateOrCreate(
                    [
                        'institution_id' => $institution->id,
                        'structural_position_id' => $position->id,
                    ],
                    [
                        'amount' => $amount,
                        'is_active' => true,
                    ]
                );
            }
        }
    }

    /**
     * @param  list<Student>  $students
     * @param  list<Employee>  $teachers
     */
    private function seedPklAndBkk(
        Institution $institution,
        AcademicYear $academicYear,
        array $students,
        array $teachers,
        User $admin
    ): void {
        if (! Schema::hasTable('industry_partners') || ! Schema::hasTable('pkl_periods')) {
            return;
        }

        $partnerA = IndustryPartner::create([
            'institution_id' => $institution->id,
            'name' => 'PT Demo Teknologi',
            'business_field' => 'Teknologi Informasi',
            'address' => 'Jl. Industri No. 10, Kota Demo',
            'city' => 'Kota Demo',
            'phone' => '022-5551001',
            'email' => 'hr@demoteknologi.example',
            'pic_name' => 'Budi Santoso',
            'pic_phone' => '08123456001',
            'status' => 'Aktif',
            'notes' => 'Mitra industri demo untuk PKL & BKK',
        ]);

        $partnerB = IndustryPartner::create([
            'institution_id' => $institution->id,
            'name' => 'Bank Demo Nusantara',
            'business_field' => 'Perbankan',
            'address' => 'Jl. Keuangan No. 5, Kota Demo',
            'city' => 'Kota Demo',
            'phone' => '022-5551002',
            'email' => 'rekrutmen@bankdemo.example',
            'pic_name' => 'Siti Rahayu',
            'pic_phone' => '08123456002',
            'status' => 'Aktif',
            'notes' => 'Mitra BKK demo',
        ]);

        $period = PklPeriod::create([
            'institution_id' => $institution->id,
            'academic_year_id' => $academicYear->id,
            'name' => 'PKL Semester Genap ' . $academicYear->code,
            'start_date' => now()->subWeeks(2)->toDateString(),
            'end_date' => now()->addMonths(2)->toDateString(),
            'status' => 'berlangsung',
            'notes' => 'Periode PKL demo',
        ]);

        $xiiStudents = array_values(array_filter($students, fn ($s) => (int) ($s->tingkat ?? 0) === 12));
        if ($xiiStudents === []) {
            $xiiStudents = array_slice($students, 0, 4);
        }

        $supervisor = $teachers[2] ?? $teachers[0] ?? null;
        foreach (array_slice($xiiStudents, 0, 3) as $i => $student) {
            $placement = PklPlacement::create([
                'institution_id' => $institution->id,
                'pkl_period_id' => $period->id,
                'student_id' => $student->id,
                'industry_partner_id' => $i === 2 ? $partnerB->id : $partnerA->id,
                'supervisor_employee_id' => $supervisor?->id,
                'industry_supervisor_name' => $i === 2 ? 'Siti Rahayu' : 'Budi Santoso',
                'start_date' => now()->subWeeks(2)->toDateString(),
                'end_date' => now()->addMonths(2)->toDateString(),
                'status' => 'berlangsung',
                'notes' => 'Penempatan PKL demo #' . ($i + 1),
            ]);

            if (Schema::hasTable('pkl_monitoring_logs')) {
                PklMonitoringLog::create([
                    'institution_id' => $institution->id,
                    'pkl_placement_id' => $placement->id,
                    'logged_by_employee_id' => $supervisor?->id,
                    'visit_date' => now()->subDays(5)->toDateString(),
                    'method' => 'kunjungan',
                    'notes' => 'Monitoring awal — siswa sudah beradaptasi (demo)',
                ]);
            }
        }

        if (! Schema::hasTable('bkk_vacancies')) {
            return;
        }

        $vacancy = BkkVacancy::create([
            'institution_id' => $institution->id,
            'industry_partner_id' => $partnerA->id,
            'title' => 'Staff IT Junior',
            'company_name' => $partnerA->name,
            'position' => 'Staff IT',
            'quota' => 3,
            'deadline' => now()->addDays(30)->toDateString(),
            'status' => 'buka',
            'description' => 'Lowongan BKK demo untuk alumni / siswa tingkat akhir.',
            'requirements' => "Lulus SMA/SMK\nMenguasai komputer dasar\nBersedia full-time",
        ]);

        BkkVacancy::create([
            'institution_id' => $institution->id,
            'industry_partner_id' => $partnerB->id,
            'title' => 'Customer Service',
            'company_name' => $partnerB->name,
            'position' => 'CS',
            'quota' => 2,
            'deadline' => now()->addDays(20)->toDateString(),
            'status' => 'buka',
            'description' => 'Lowongan CS demo Bank Demo Nusantara.',
            'requirements' => 'Komunikatif, siap bekerja shift',
        ]);

        $alumni = Student::query()
            ->where('institution_id', $institution->id)
            ->where('status', 'Lulus')
            ->orderBy('id')
            ->limit(2)
            ->get();

        if (Schema::hasTable('bkk_applications')) {
            foreach ($alumni as $i => $alum) {
                BkkApplication::create([
                    'institution_id' => $institution->id,
                    'bkk_vacancy_id' => $vacancy->id,
                    'student_id' => $alum->id,
                    'status' => $i === 0 ? 'seleksi' : 'diajukan',
                    'applied_at' => now()->subDays(3 - $i)->toDateString(),
                    'notes' => 'Lamaran BKK demo',
                ]);
            }
        }

        unset($admin);
    }

    /**
     * @param  list<SchoolClass>  $classes
     * @param  list<Student>  $students
     * @param  list<Employee>  $teachers
     */
    private function seedWaliNotes(
        Institution $institution,
        array $classes,
        array $students,
        array $teachers,
        User $admin
    ): void {
        if (! Schema::hasTable('wali_notes')) {
            return;
        }

        $class = $classes[0] ?? null;
        if (! $class) {
            return;
        }

        $waliEmployee = null;
        foreach ($teachers as $t) {
            if ((int) $t->id === (int) ($class->teacher_id ?? 0)) {
                $waliEmployee = $t;
                break;
            }
        }
        $waliEmployee = $waliEmployee ?? ($teachers[0] ?? null);
        $author = $waliEmployee
            ? (User::where('email', $waliEmployee->email)->first() ?? $admin)
            : $admin;

        $classStudents = array_values(array_filter(
            $students,
            fn ($s) => (int) ($s->class_id ?? 0) === (int) $class->id
        ));
        if ($classStudents === []) {
            $classStudents = array_slice($students, 0, 3);
        }

        $bodies = [
            'Siswa aktif di kelas, partisipasi diskusi baik. (catatan wali demo)',
            'Perlu pendampingan PR Matematika minggu ini. Sudah dihubungi orang tua. (demo)',
            'Prestasi baik di kegiatan kelas — dipertimbangkan untuk pengurus OSIS. (demo)',
        ];

        foreach (array_slice($classStudents, 0, 3) as $i => $student) {
            WaliNote::create([
                'institution_id' => $institution->id,
                'class_id' => $class->id,
                'student_id' => $student->id,
                'author_user_id' => $author->id,
                'body' => $bodies[$i] ?? $bodies[0],
            ]);
        }
    }

    private function seedFacility(Institution $institution): Building
    {
        $land = Land::create([
            'institution_id' => $institution->id,
            'name' => 'Lahan Utama SMA 1 Demo Servrin',
            'certificate_number' => 'SHM-99990001',
            'certificate_type' => 'SHM',
            'area' => 12500,
            'location' => 'Jl. Pendidikan No. 1, Kota Demo',
            'status' => 'Milik Sendiri',
            'acquisition_date' => '1998-07-01',
            'description' => 'Lahan kampus utama (demo)',
        ]);

        return Building::create([
            'institution_id' => $institution->id,
            'land_id' => $land->id,
            'name' => 'Gedung A',
            'code' => 'GDG-A',
            'floor_count' => 3,
            'building_area' => 2400,
            'condition' => 'Baik',
            'construction_year' => 2005,
            'description' => 'Gedung kelas, lab, dan kantor (demo)',
        ]);
    }

    /**
     * @param  list<SchoolClass>  $classes
     */
    private function seedScheduleTemplate(Institution $institution, Semester $semester, array $classes): void
    {
        $template = LessonScheduleTemplate::create([
            'institution_id' => $institution->id,
            'semester_id' => $semester->id,
            'name' => '5 JP Senin–Jumat',
            'days' => LessonScheduleTemplate::normalizeDays([
                ['day_of_week' => 1, 'periods' => 5, 'is_holiday' => false],
                ['day_of_week' => 2, 'periods' => 5, 'is_holiday' => false],
                ['day_of_week' => 3, 'periods' => 5, 'is_holiday' => false],
                ['day_of_week' => 4, 'periods' => 5, 'is_holiday' => false],
                ['day_of_week' => 5, 'periods' => 5, 'is_holiday' => false],
                ['day_of_week' => 6, 'periods' => 0, 'is_holiday' => true],
                ['day_of_week' => 7, 'periods' => 0, 'is_holiday' => true],
            ]),
        ]);

        foreach ($classes as $class) {
            $class->update(['lesson_schedule_template_id' => $template->id]);
        }
    }

    /**
     * @param  list<InventoryItem>  $items
     * @param  list<Room>  $rooms
     * @param  list<Employee>  $teachers
     * @param  list<Student>  $students
     */
    private function seedInventoryOps(
        Institution $institution,
        array $items,
        array $rooms,
        array $teachers,
        array $students,
        User $admin
    ): void {
        if ($items === []) {
            return;
        }

        $pc = $items[0];
        $proyektor = $items[1] ?? $items[0];
        $bola = $items[5] ?? $items[0];
        $from = $rooms[0] ?? null;
        $to = $rooms[1] ?? $from;

        InventoryTransaction::create([
            'institution_id' => $institution->id,
            'item_id' => $pc->id,
            'transaction_type' => 'Masuk',
            'transaction_date' => now()->subMonths(2)->toDateString(),
            'quantity' => 2,
            'reference_number' => 'INV-IN-001',
            'to_location_id' => $from?->id,
            'notes' => 'Penambahan unit komputer demo',
            'created_by' => $admin->id,
        ]);
        $pc->increment('quantity', 2);

        InventoryTransaction::create([
            'institution_id' => $institution->id,
            'item_id' => $proyektor->id,
            'transaction_type' => 'Mutasi',
            'transaction_date' => now()->subDays(5)->toDateString(),
            'quantity' => 1,
            'reference_number' => 'INV-MUT-001',
            'from_location_id' => $from?->id,
            'to_location_id' => $to?->id,
            'notes' => 'Mutasi proyektor antar ruang demo',
            'created_by' => $admin->id,
        ]);
        if ($to) {
            $proyektor->update(['room_id' => $to->id]);
        }

        $borrower = $teachers[1] ?? $teachers[0] ?? null;
        InventoryLoan::create([
            'institution_id' => $institution->id,
            'item_id' => $bola->id,
            'borrower_type' => 'Employee',
            'borrower_id' => $borrower?->id,
            'borrower_name' => $borrower?->name ?? 'Guru Piket Demo',
            'borrower_phone' => $borrower?->phone,
            'loan_date' => now()->subDays(3)->toDateString(),
            'expected_return_date' => now()->addDays(4)->toDateString(),
            'quantity' => 2,
            'purpose' => 'Latihan futsal ekskul',
            'status' => 'Dipinjam',
            'notes' => 'Peminjaman inventaris demo',
            'created_by' => $admin->id,
        ]);
        $bola->update(['status' => 'Dipinjam']);

        $student = $students[0] ?? null;
        InventoryLoan::create([
            'institution_id' => $institution->id,
            'item_id' => $proyektor->id,
            'borrower_type' => 'Student',
            'borrower_id' => $student?->id,
            'borrower_name' => $student?->name ?? 'Siswa Demo',
            'loan_date' => now()->subDays(12)->toDateString(),
            'expected_return_date' => now()->subDays(5)->toDateString(),
            'actual_return_date' => now()->subDays(6)->toDateString(),
            'quantity' => 1,
            'purpose' => 'Presentasi OSIS',
            'status' => 'Dikembalikan',
            'return_condition' => 'Baik',
            'notes' => 'Sudah dikembalikan (demo)',
            'created_by' => $admin->id,
        ]);

        InventoryMaintenance::create([
            'institution_id' => $institution->id,
            'item_id' => $pc->id,
            'maintenance_type' => 'Perawatan',
            'scheduled_date' => now()->addDays(14)->toDateString(),
            'status' => 'Terjadwal',
            'vendor' => 'Teknisi Demo',
            'description' => 'Pembersihan dan cek hardware lab',
            'created_by' => $admin->id,
        ]);
        InventoryMaintenance::create([
            'institution_id' => $institution->id,
            'item_id' => $proyektor->id,
            'maintenance_type' => 'Perbaikan',
            'scheduled_date' => now()->subDays(10)->toDateString(),
            'completed_date' => now()->subDays(8)->toDateString(),
            'cost' => 350000,
            'vendor' => 'Servis Proyektor Demo',
            'description' => 'Ganti lampu proyektor',
            'status' => 'Selesai',
            'technician_name' => 'Teknisi Demo',
            'created_by' => $admin->id,
        ]);
    }

    private function seedDigitalArchive(Institution $institution, User $admin): void
    {
        $defs = [
            ['name' => 'Tata Tertib', 'slug' => 'tata-tertib', 'title' => 'Tata Tertib Siswa', 'ref' => 'TT/001/DEMO'],
            ['name' => 'SK & Kebijakan', 'slug' => 'sk-kebijakan', 'title' => 'SK Kalender Akademik', 'ref' => 'SK/002/DEMO'],
            ['name' => 'Laporan', 'slug' => 'laporan', 'title' => 'Laporan Kegiatan Semester', 'ref' => 'LAP/003/DEMO'],
        ];

        foreach ($defs as $i => $def) {
            $category = DigitalArchiveCategory::create([
                'institution_id' => $institution->id,
                'name' => $def['name'],
                'slug' => $def['slug'],
                'description' => $def['name'] . ' arsip demo',
                'sort_order' => $i + 1,
            ]);

            $path = "digital-archives/{$institution->id}/{$def['slug']}.txt";
            Storage::disk('public')->put($path, $def['title'] . "\nDokumen dummy SMA 1 Demo Servrin.\n");

            DigitalArchive::create([
                'institution_id' => $institution->id,
                'digital_archive_category_id' => $category->id,
                'title' => $def['title'],
                'description' => $def['title'] . ' (arsip demo)',
                'file_path' => $path,
                'file_name' => $def['slug'] . '.txt',
                'file_size' => Storage::disk('public')->size($path),
                'mime_type' => 'text/plain',
                'document_date' => now()->subMonths($i + 1)->toDateString(),
                'reference_number' => $def['ref'],
                'created_by' => $admin->id,
            ]);
        }
    }

    private function seedGuestBook(Institution $institution, User $admin): void
    {
        $defs = [
            ['Budi Santoso', 'Dinas Pendidikan Kota Demo', 'Koordinasi kurikulum', true],
            ['Siti Rahma', 'SMP 2 Demo', 'Studi banding perpustakaan', true],
            ['Agus Wijaya', 'Komite Sekolah', 'Rapat komite', false],
        ];

        foreach ($defs as $i => [$name, $instansi, $tujuan, $checkedOut]) {
            $masuk = now()->subDays($i + 1)->setTime(9 + $i, 15);
            GuestVisit::create([
                'institution_id' => $institution->id,
                'nama_tamu' => $name,
                'no_identitas' => sprintf('3273%012d', $i + 1),
                'instansi_asal' => $instansi,
                'no_telepon' => '08150000' . sprintf('%04d', $i + 1),
                'tujuan_kunjungan' => $tujuan,
                'orang_ditemui' => $i === 2 ? 'Kepala Sekolah Demo' : 'Admin Demo Servrin',
                'waktu_masuk' => $masuk,
                'waktu_keluar' => $checkedOut ? $masuk->copy()->addHours(2) : null,
                'catatan' => 'Kunjungan buku tamu demo',
                'created_by' => $admin->id,
            ]);
        }
    }

    /**
     * @param  list<Employee>  $teachers
     * @param  list<Student>  $students
     * @param  list<SchoolClass>  $classes
     */
    private function seedPiketLogs(
        Institution $institution,
        array $teachers,
        array $students,
        array $classes,
        User $admin
    ): void {
        $piket = $teachers[1] ?? $teachers[0] ?? null;
        $piketUser = User::where('email', 'guru02@demo.servrin.id')->first() ?? $admin;
        if (!$piket) {
            return;
        }

        $schedule = PiketSchedule::where('institution_id', $institution->id)
            ->where('employee_id', $piket->id)
            ->orderBy('day_of_week')
            ->first();

        $dates = [];
        $cursor = now()->startOfDay();
        while (count($dates) < 2) {
            if ($cursor->isWeekday() && in_array((int) $cursor->dayOfWeekIso, [1, 2, 3, 5], true)) {
                $dates[] = $cursor->copy();
            }
            $cursor->subDay();
        }

        foreach ($dates as $i => $date) {
            $log = PiketLog::create([
                'institution_id' => $institution->id,
                'duty_date' => $date->toDateString(),
                'employee_id' => $piket->id,
                'piket_schedule_id' => $schedule?->id,
                'summary' => 'Piket berjalan lancar. Beberapa keterlambatan siswa tercatat.',
                'handoff_notes' => $i === 0 ? 'Kunci lab diserahkan ke kepala lab.' : null,
                'status' => $i === 0 ? PiketLog::STATUS_REVIEWED : PiketLog::STATUS_SUBMITTED,
                'created_by' => $piketUser->id,
                'reviewed_by' => $i === 0 ? $admin->id : null,
                'reviewed_at' => $i === 0 ? now()->subDay() : null,
                'review_notes' => $i === 0 ? 'Sudah ditinjau (demo)' : null,
            ]);

            PiketIncident::create([
                'institution_id' => $institution->id,
                'incident_date' => $date->toDateString(),
                'incident_type' => PiketIncident::TYPE_TERLAMBAT_SISWA,
                'period' => 1,
                'class_id' => $classes[0]->id ?? null,
                'student_id' => $students[0]->id ?? null,
                'piket_log_id' => $log->id,
                'detected_at' => '07:20',
                'minutes_late' => 15,
                'description' => 'Siswa terlambat masuk gerbang (insiden demo)',
                'source' => 'manual',
                'status' => $i === 0 ? PiketIncident::STATUS_RESOLVED : PiketIncident::STATUS_OPEN,
                'created_by' => $piketUser->id,
                'resolved_by' => $i === 0 ? $admin->id : null,
                'resolved_at' => $i === 0 ? now()->subDay() : null,
            ]);
        }

        PiketIncident::create([
            'institution_id' => $institution->id,
            'incident_date' => $dates[0]->toDateString(),
            'incident_type' => PiketIncident::TYPE_KELAS_KOSONG,
            'period' => 3,
            'class_id' => $classes[1]->id ?? ($classes[0]->id ?? null),
            'employee_id' => $teachers[3]->id ?? $teachers[0]->id,
            'description' => 'Guru belum hadir di jam ke-3 (insiden demo)',
            'source' => 'manual',
            'status' => PiketIncident::STATUS_CONFIRMED,
            'created_by' => $piketUser->id,
        ]);
    }

    /**
     * @param  list<Student>  $students
     * @param  list<Employee>  $teachers
     */
    private function seedChangeRequests(
        Institution $institution,
        array $students,
        array $teachers,
        User $admin
    ): void {
        $student = $students[0] ?? null;
        $studentUser = $student
            ? User::where('role', 'student')->where('login_nik', $student->nik)->first()
            : null;

        if ($student && $studentUser) {
            StudentChangeRequest::create([
                'student_id' => $student->id,
                'requested_by' => $studentUser->id,
                'field_name' => 'father_name',
                'old_value' => $student->father_name,
                'new_value' => 'Ayah Andi Pratama',
                'status' => 'pending',
            ]);
            StudentChangeRequest::create([
                'student_id' => $student->id,
                'requested_by' => $studentUser->id,
                'approved_by' => $admin->id,
                'field_name' => 'birth_place',
                'old_value' => $student->birth_place,
                'new_value' => 'Bandung',
                'status' => 'approved',
                'approved_at' => now()->subDays(2),
            ]);
        }

        $teacher = $teachers[1] ?? null;
        $teacherUser = $teacher
            ? User::where('email', $teacher->email)->first()
            : null;
        if ($teacher && $teacherUser) {
            TeacherChangeRequest::create([
                'employee_id' => $teacher->id,
                'requested_by' => $teacherUser->id,
                'field_name' => 'subject',
                'old_value' => $teacher->subject,
                'new_value' => 'Seni Budaya',
                'status' => 'pending',
            ]);
        }
    }

    /**
     * @param  list<Subject>  $subjects
     * @param  list<Student>  $students
     */
    private function seedExam(Institution $institution, array $subjects, array $students, User $admin): void
    {
        $subject = $subjects[0] ?? null;
        $bank = BankSoal::create([
            'institution_id' => $institution->id,
            'created_by_user_id' => $admin->id,
            'code' => 'BANK-MTK-DEMO',
            'name' => 'Bank Soal Matematika Demo',
            'subject_id' => $subject?->id,
            'grade' => 10,
            'keterangan' => 'Bank soal contoh untuk sekolah demo',
        ]);

        $questionDefs = [
            ['Hasil 12 + 8 adalah …', '20', ['18', '20', '21', '24']],
            ['Akar kuadrat dari 81 adalah …', '9', ['7', '8', '9', '11']],
            ['Nilai dari 5 × 6 − 4 adalah …', '26', ['20', '26', '34', '11']],
            ['Bentuk pecahan dari 0,5 adalah …', '1/2', ['1/4', '1/3', '1/2', '2/3']],
            ['Keliling persegi dengan sisi 4 cm adalah …', '16 cm', ['8 cm', '12 cm', '16 cm', '20 cm']],
        ];

        $examQuestions = [];
        foreach ($questionDefs as $i => [$body, $correct, $options]) {
            $question = QuestionBank::create([
                'institution_id' => $institution->id,
                'bank_soal_id' => $bank->id,
                'subject_id' => $subject?->id,
                'type' => QuestionBank::TYPE_PG,
                'body' => $body,
                'weight' => 1,
                'sort_order' => $i + 1,
            ]);
            foreach ($options as $oi => $optionBody) {
                $isCorrect = $optionBody === $correct;
                QuestionOption::create([
                    'question_bank_id' => $question->id,
                    'option_key' => chr(65 + $oi),
                    'body' => $optionBody,
                    'is_correct' => $isCorrect,
                    'option_weight' => $isCorrect ? 1 : 0,
                    'sort_order' => $oi + 1,
                ]);
            }
            $examQuestions[] = $question;
        }

        $exam = Exam::create([
            'institution_id' => $institution->id,
            'subject_id' => $subject?->id,
            'code' => 'UTS-MTK-DEMO',
            'name' => 'UTS Matematika Demo',
            'description' => 'Ujian contoh. PIN masuk: DEMONA. Nomor urut siswa demo = 1.',
            'duration_minutes' => 20,
            'shuffle_questions' => false,
            'shuffle_options' => false,
            'start_type' => Exam::START_TYPE_MANUAL,
            'created_by' => $admin->id,
        ]);

        foreach ($examQuestions as $i => $question) {
            ExamQuestion::create([
                'exam_id' => $exam->id,
                'question_bank_id' => $question->id,
                'sort_order' => $i + 1,
            ]);
        }

        $session = ExamSession::create([
            'exam_id' => $exam->id,
            'name' => 'Sesi pagi (demo)',
            'scheduled_start_at' => now()->subHour(),
            'scheduled_end_at' => now()->addDays(7),
            'started_at' => now()->subHour(),
            'status' => ExamSession::STATUS_STARTED,
            'entry_pin' => 'DEMONA',
            'entry_pin_updated_at' => now(),
        ]);

        foreach (array_slice($students, 0, 10) as $i => $student) {
            ExamParticipant::create([
                'exam_session_id' => $session->id,
                'student_id' => $student->id,
                'participant_order' => $i + 1,
                'login_token' => ExamParticipant::generateLoginToken(),
                'status' => ExamParticipant::STATUS_REGISTERED,
            ]);
        }
    }

    /**
     * @param  list<Student>  $students
     */
    private function seedNotifications(
        Institution $institution,
        ?AcademicCalendarEvent $event,
        User $admin,
        array $students
    ): void {
        $parent = User::where('email', config('demo.parent_email', 'ortu@demo.servrin.id'))->first();
        if ($parent && $event) {
            Notification::sendNow($parent, new AcademicCalendarParentNotification($event));
        }

        if ($event) {
            Notification::sendNow($admin, new AcademicCalendarParentNotification($event));
        }

        $student = $students[0] ?? null;
        $studentUser = $student
            ? User::where('role', 'student')->where('login_nik', $student->nik)->first()
            : null;
        if ($studentUser && $event) {
            Notification::sendNow($studentUser, new AcademicCalendarParentNotification($event));
        }
    }
}
