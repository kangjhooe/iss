<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Institution extends Model
{
    use Auditable, HasFactory, SoftDeletes;

    protected $table = 'institution';

    public const TEACHER_APPRECIATION_LEADERBOARD_GURU_ONLY = 'guru_only';

    public const TEACHER_APPRECIATION_LEADERBOARD_COMBINED = 'combined';

    public const TEACHER_APPRECIATION_LEADERBOARD_SEPARATED = 'separated';

    public const TEACHER_APPRECIATION_LEADERBOARD_MODES = [
        self::TEACHER_APPRECIATION_LEADERBOARD_GURU_ONLY,
        self::TEACHER_APPRECIATION_LEADERBOARD_COMBINED,
        self::TEACHER_APPRECIATION_LEADERBOARD_SEPARATED,
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'foundation_name',
        'npsn',
        'nss',
        'level',
        'type',
        'address',
        'village',
        'sub_district',
        'district',
        'district_code',
        'province',
        'province_code',
        'postal_code',
        'wilayah_province_code',
        'wilayah_regency_code',
        'wilayah_district_code',
        'wilayah_village_code',
        'phone',
        'email',
        'website',
        'principal_name',
        'principal_nip',
        'description',
        'vision',
        'mission',
        'logo',
        'cover_image',
        'is_active',
        'is_demo',
        'storage_quota_mb',
        'storage_addon_mb',
        'active_academic_year_id',
        'active_semester_id',
        'latitude',
        'longitude',
        'location_radius',
        'teacher_appreciation_leaderboard_mode',
        'admission_label',
        'nis_numbering',
        'hidden_module_keys',
    ];

    public const ADMISSION_LABEL_DEFAULT = 'PPDB';

    public const ADMISSION_LABEL_PRESETS = [
        'PPDB',
        'SPMB',
        'PSB',
        'PMB',
    ];

    /**
     * Label publik untuk penerimaan (PPDB / SPMB / kustom).
     */
    public function resolvedAdmissionLabel(): string
    {
        $label = trim((string) ($this->admission_label ?? ''));

        return $label !== '' ? $label : self::ADMISSION_LABEL_DEFAULT;
    }

    /**
     * Apakah ada periode PPDB yang sedang dibuka (status open + dalam rentang tanggal).
     */
    public function hasOpenAdmissionPeriod(): bool
    {
        $today = now()->toDateString();

        return $this->ppdbPeriods()
            ->where('status', 'open')
            ->whereDate('open_date', '<=', $today)
            ->whereDate('close_date', '>=', $today)
            ->exists();
    }

    public function ppdbPeriods()
    {
        return $this->hasMany(PpdbPeriod::class, 'institution_id');
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_demo' => 'boolean',
            'storage_quota_mb' => 'integer',
            'storage_addon_mb' => 'integer',
            'latitude' => 'decimal:8',
            'longitude' => 'decimal:8',
            'location_radius' => 'integer',
            'nis_numbering' => 'array',
            'hidden_module_keys' => 'array',
        ];
    }

    public function subscription()
    {
        return $this->hasOne(InstitutionSubscription::class);
    }

    public function addonGrants()
    {
        return $this->hasMany(InstitutionAddonGrant::class);
    }

    /**
     * Get the users for the institution.
     */
    public function users()
    {
        return $this->hasMany(User::class);
    }

    /**
     * Get the students for the institution.
     */
    public function students()
    {
        return $this->hasMany(Student::class);
    }

    /**
     * Get the teachers for the institution.
     * Note: Using Employee model as teacher table has been renamed to employee
     */
    public function teachers()
    {
        return $this->hasMany(Employee::class)->where('type', 'Guru');
    }

    /**
     * Get the employees for the institution.
     */
    public function employees()
    {
        return $this->hasMany(Employee::class);
    }

    /**
     * Get the employee institution assignments (non-induk) for this institution.
     */
    public function employeeInstitutionAssignments()
    {
        return $this->hasMany(EmployeeInstitutionAssignment::class, 'institution_id');
    }

    /**
     * Get the change requests for the institution.
     */
    public function changeRequests()
    {
        return $this->hasMany(InstitutionChangeRequest::class);
    }

    /**
     * Get the classes for the institution.
     */
    public function classes()
    {
        return $this->hasMany(SchoolClass::class);
    }

    /**
     * Get the lands for the institution.
     */
    public function lands()
    {
        return $this->hasMany(Land::class);
    }

    /**
     * Get the buildings for the institution.
     */
    public function buildings()
    {
        return $this->hasMany(Building::class);
    }

    /**
     * Get the rooms for the institution.
     */
    public function rooms()
    {
        return $this->hasMany(Room::class);
    }

    /**
     * Get the active academic year for the institution.
     */
    public function activeAcademicYear()
    {
        return $this->belongsTo(AcademicYear::class, 'active_academic_year_id');
    }

    /**
     * Get the active semester for the institution.
     */
    public function activeSemester()
    {
        return $this->belongsTo(Semester::class, 'active_semester_id');
    }

    /**
     * Get the correspondence for the institution.
     */
    public function correspondence()
    {
        return $this->hasMany(Correspondence::class);
    }

    /**
     * Get the surat (editor) for the institution.
     */
    public function surat()
    {
        return $this->hasMany(Surat::class);
    }

    /**
     * Get the template surat for the institution.
     */
    public function templateSurat()
    {
        return $this->hasMany(TemplateSurat::class);
    }

    /**
     * Get the kop surat for the institution.
     */
    public function kopSurat()
    {
        return $this->hasMany(KopSurat::class);
    }

    /**
     * Get the signature/stamp assets for the institution.
     */
    public function asetTandaTangan()
    {
        return $this->hasMany(AsetTandaTangan::class);
    }

    /**
     * Get the correspondence categories for the institution.
     */
    public function correspondenceCategories()
    {
        return $this->hasMany(CorrespondenceCategory::class);
    }

    /**
     * Get the inventory categories for the institution.
     */
    public function inventoryCategories()
    {
        return $this->hasMany(InventoryCategory::class);
    }

    /**
     * Get the inventory items for the institution.
     */
    public function inventoryItems()
    {
        return $this->hasMany(InventoryItem::class);
    }

    /**
     * Get the inventory transactions for the institution.
     */
    public function inventoryTransactions()
    {
        return $this->hasMany(InventoryTransaction::class);
    }

    /**
     * Get the inventory maintenances for the institution.
     */
    public function inventoryMaintenances()
    {
        return $this->hasMany(InventoryMaintenance::class);
    }

    /**
     * Get the inventory loans for the institution.
     */
    public function inventoryLoans()
    {
        return $this->hasMany(InventoryLoan::class);
    }

    /**
     * Student mutations where this institution is the origin.
     */
    public function studentMutationsAsOrigin()
    {
        return $this->hasMany(StudentMutation::class, 'origin_institution_id');
    }

    /**
     * Student mutations where this institution is the target.
     */
    public function studentMutationsAsTarget()
    {
        return $this->hasMany(StudentMutation::class, 'target_institution_id');
    }

    /**
     * Violations (pelanggaran) for students in this institution.
     */
    public function violations()
    {
        return $this->hasMany(Violation::class);
    }

    /**
     * Violation types (jenis pelanggaran) for this institution.
     */
    public function violationTypes()
    {
        return $this->hasMany(ViolationType::class);
    }

    /**
     * Achievements (prestasi) for students in this institution.
     */
    public function achievements()
    {
        return $this->hasMany(Achievement::class);
    }

    /**
     * Achievement types (jenis prestasi) for this institution.
     */
    public function achievementTypes()
    {
        return $this->hasMany(AchievementType::class);
    }

    /**
     * Point thresholds (aturan tindakan) for this institution.
     */
    public function pointThresholds()
    {
        return $this->hasMany(PointThreshold::class);
    }

    /**
     * Student action logs (catatan tindakan) for this institution.
     */
    public function studentActionLogs()
    {
        return $this->hasMany(StudentActionLog::class);
    }

    /**
     * Counseling types (jenis bimbingan) for this institution.
     */
    public function counselingTypes()
    {
        return $this->hasMany(CounselingType::class);
    }

    /**
     * Counseling sessions for this institution.
     */
    public function counselingSessions()
    {
        return $this->hasMany(CounselingSession::class);
    }

    /**
     * Subjects (mata pelajaran) for this institution.
     */
    public function subjects()
    {
        return $this->hasMany(Subject::class);
    }

    /**
     * Lesson schedules (jadwal pelajaran) for this institution.
     */
    public function lessonSchedules()
    {
        return $this->hasMany(LessonSchedule::class);
    }

    /**
     * Grades (nilai) for this institution.
     */
    public function grades()
    {
        return $this->hasMany(Grade::class);
    }

    /**
     * Teaching journals (jurnal mengajar) for this institution.
     */
    public function teachingJournals()
    {
        return $this->hasMany(TeachingJournal::class);
    }

    /**
     * Guest visits (buku tamu) for this institution.
     */
    public function guestVisits()
    {
        return $this->hasMany(GuestVisit::class);
    }

    /**
     * Digital archive categories for this institution.
     */
    public function digitalArchiveCategories()
    {
        return $this->hasMany(DigitalArchiveCategory::class);
    }

    /**
     * Digital archives for this institution.
     */
    public function digitalArchives()
    {
        return $this->hasMany(DigitalArchive::class);
    }

    /**
     * Student attendances for this institution.
     */
    public function studentAttendances()
    {
        return $this->hasMany(StudentAttendance::class);
    }

    /**
     * Employee attendances for this institution.
     */
    public function employeeAttendances()
    {
        return $this->hasMany(EmployeeAttendance::class);
    }

    /**
     * Academic calendar events for this institution.
     */
    public function academicCalendarEvents()
    {
        return $this->hasMany(AcademicCalendarEvent::class);
    }

    /**
     * Audit logs for this institution.
     */
    public function auditLogs()
    {
        return $this->hasMany(AuditLog::class);
    }

    /**
     * Exams (ujian) for this institution.
     */
    public function exams()
    {
        return $this->hasMany(Exam::class);
    }

    /**
     * Bank soal (kumpulan soal) for this institution.
     */
    public function bankSoal()
    {
        return $this->hasMany(BankSoal::class);
    }

    /**
     * Question bank items (soal) for this institution.
     */
    public function questionBanks()
    {
        return $this->hasMany(QuestionBank::class);
    }

    /**
     * Question stimuli (stimulus soal) for this institution.
     */
    public function questionStimuli()
    {
        return $this->hasMany(QuestionStimulus::class);
    }

    /**
     * Scope a query to only include active institutions.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to filter by level.
     */
    public function scopeByLevel($query, string $level)
    {
        return $query->where('level', $level);
    }

    /**
     * Jenjang kejuruan (PKL, BKK, program keahlian).
     */
    public const VOCATIONAL_LEVELS = ['SMK', 'MAK'];

    public function isVocational(): bool
    {
        return \App\Support\VocationalAccess::isVocationalLevel($this->level);
    }

    /**
     * Get mutasi level group: mutasi hanya antar jenjang dalam kelompok yang sama.
     * SD-MI, SMP-MTs, SMA-MA-SMK-MAK, PAUD-TK.
     */
    public static function getMutasiLevelGroup(?string $level): ?string
    {
        if ($level === null) {
            return null;
        }
        $level = strtoupper($level);

        return match ($level) {
            'SD', 'MI' => 'dasar',
            'SMP', 'MTs' => 'menengah',
            'SMA', 'MA', 'SMK', 'MAK' => 'atas',
            'PAUD', 'TK' => 'paud',
            default => null,
        };
    }

    /**
     * Check if this institution can mutate to/from another (same jenjang group).
     */
    public function canMutateWith(Institution $other): bool
    {
        $my = self::getMutasiLevelGroup($this->level);
        $their = self::getMutasiLevelGroup($other->level);

        return $my !== null && $my === $their;
    }

    /**
     * Jenjang sebelumnya yang alumni-nya boleh ditarik sebagai siswa baru.
     * SD/MI ← PAUD/TK, SMP/MTs ← SD/MI, SMA/MA/SMK/MAK ← SMP/MTs.
     */
    public function canPullAlumniFrom(Institution $origin): bool
    {
        $target = self::getMutasiLevelGroup($this->level);
        $from = self::getMutasiLevelGroup($origin->level);

        return match ($target) {
            'dasar' => $from === 'paud',
            'menengah' => $from === 'dasar',
            'atas' => $from === 'menengah',
            default => false,
        };
    }

    public function defaultEntryGrade(): ?int
    {
        return match (self::getMutasiLevelGroup($this->level)) {
            'dasar' => 1,
            'menengah' => 7,
            'atas' => 10,
            default => null,
        };
    }

    /**
     * Kode jenjang 1 digit untuk nomor peserta: SD/MI=1, SMP/MTs=2, SMA/MA/SMK/MAK=3, PAUD/TK=4.
     */
    public function getJenjangCodeAttribute(): string
    {
        if ($this->level === null) {
            return '0';
        }

        return match (strtoupper($this->level)) {
            'SD', 'MI' => '1',
            'SMP', 'MTs' => '2',
            'SMA', 'MA', 'SMK', 'MAK' => '3',
            'PAUD', 'TK' => '4',
            default => '0',
        };
    }

    /**
     * Build nomor peserta format: YY-PP-KK-J-SSSS-NNN
     * (tahun 2, provinsi 2, kabupaten 2, jenjang 1, kode sekolah 4, nomor urut 3 digit).
     *
     * @param  int  $participantOrder  1-based nomor urut dalam sesi
     * @param  \DateTimeInterface|null  $yearSource  Tanggal untuk tahun (default: now)
     */
    public function buildNomorPeserta(int $participantOrder, ?\DateTimeInterface $yearSource = null): string
    {
        $date = $yearSource ?? now();
        $yy = $date->format('y');
        $pp = str_pad((string) ($this->province_code ?? '0'), 2, '0', STR_PAD_LEFT);
        $kk = str_pad((string) ($this->district_code ?? '0'), 2, '0', STR_PAD_LEFT);
        $j = $this->jenjang_code;
        $ssss = $this->npsn ? str_pad(substr((string) $this->npsn, -4), 4, '0', STR_PAD_LEFT) : '0000';
        $nnn = str_pad((string) max(1, $participantOrder), 3, '0', STR_PAD_LEFT);

        return "{$yy}-{$pp}-{$kk}-{$j}-{$ssss}-{$nnn}";
    }

    /**
     * Apakah jenjang termasuk madrasah (MI, MTs, MA, MAK).
     */
    public static function isMadrasahLevel(?string $level): bool
    {
        if ($level === null || $level === '') {
            return false;
        }

        return in_array(strtoupper($level), ['MI', 'MTS', 'MA', 'MAK'], true);
    }

    /**
     * Jabatan penandatangan laporan: satu jabatan saja.
     * Madrasah → "Kepala Madrasah", selain itu → "Kepala Sekolah".
     */
    public static function principalTitleForLevel(?string $level): string
    {
        return self::isMadrasahLevel($level) ? 'Kepala Madrasah' : 'Kepala Sekolah';
    }

    /**
     * Jabatan penandatangan untuk institusi ini.
     */
    public function getPrincipalTitleAttribute(): string
    {
        return self::principalTitleForLevel($this->level);
    }

    /**
     * Label nomor statistik: madrasah → NSM, selain itu → NSS.
     */
    public static function nssLabelForLevel(?string $level): string
    {
        return self::isMadrasahLevel($level) ? 'NSM' : 'NSS';
    }

    /**
     * Label nomor statistik untuk institusi ini.
     */
    public function getNssLabelAttribute(): string
    {
        return self::nssLabelForLevel($this->level);
    }

    /**
     * Scope a query to filter by type.
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Get statistics for the institution.
     */
    public function getStatistics(): array
    {
        return [
            'users_count' => $this->users()->count(),
            'students_count' => $this->students()->count(),
            'teachers_count' => $this->teachers()->count(),
            'active_students_count' => $this->students()->where('status', 'Aktif')->count(),
            'active_teachers_count' => $this->teachers()->where('status', 'Aktif')->count(),
            'pending_change_requests_count' => $this->changeRequests()->where('status', 'pending')->count(),
        ];
    }
}
