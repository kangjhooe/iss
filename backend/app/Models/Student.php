<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\Auditable;

class Student extends Model
{
    use HasFactory, SoftDeletes, Auditable;

    protected $table = 'student';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'institution_id',
        'nik',
        'nis',
        'nisn',
        'name',
        'gender',
        'birth_date',
        'birth_place',
        'address',
        'phone',
        'email',
        'religion',
        'no_kk',
        'aspiration',
        'hobby',
        'disability',
        'height',
        'weight',
        'previous_school',
        'previous_school_npsn',
        'previous_school_address',
        'residence_type',
        'tingkat',
        'class',
        'academic_year',
        'academic_year_id',
        'semester_id',
        'status',
        'graduation_year',
        'father_name',
        'father_status',
        'father_nik',
        'father_birth_place',
        'father_birth_date',
        'father_education',
        'father_occupation',
        'father_income',
        'mother_name',
        'mother_status',
        'mother_nik',
        'mother_birth_place',
        'mother_birth_date',
        'mother_education',
        'mother_occupation',
        'mother_income',
        'guardian_name',
        'guardian_phone',
        'guardian_type',
        'guardian_status',
        'guardian_nik',
        'guardian_birth_place',
        'guardian_birth_date',
        'guardian_education',
        'guardian_occupation',
        'guardian_income',
        'notes',
        'class_id',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'tingkat' => 'integer',
            'graduation_year' => 'integer',
            'father_birth_date' => 'date',
            'mother_birth_date' => 'date',
            'guardian_birth_date' => 'date',
            'father_income' => 'decimal:2',
            'mother_income' => 'decimal:2',
            'guardian_income' => 'decimal:2',
        ];
    }

    /**
     * Get the institution that owns the student.
     */
    public function institution()
    {
        return $this->belongsTo(Institution::class);
    }

    /**
     * Get the class that the student belongs to.
     * Note: column `class` (legacy string) also exists — prefer schoolClass() when eager-loading.
     */
    public function class()
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    /**
     * Alias for class() — used by auth/user payloads (avoids clash with legacy `class` attribute).
     */
    public function schoolClass()
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    /**
     * Get the class student history records.
     */
    public function classHistory()
    {
        return $this->hasMany(ClassStudentHistory::class);
    }

    /**
     * Get the user account associated with this student (by NIK login).
     */
    public function userAccount()
    {
        return $this->belongsTo(User::class, 'nik', 'login_nik');
    }

    /**
     * Get the documents for this student.
     */
    public function documents()
    {
        return $this->hasMany(StudentDocument::class);
    }

    /**
     * Get the violations for this student.
     */
    public function violations()
    {
        return $this->hasMany(Violation::class);
    }

    /**
     * Get the achievements (prestasi) for this student.
     */
    public function achievements()
    {
        return $this->hasMany(Achievement::class);
    }

    /**
     * Get the action logs (tindakan yang sudah dilaksanakan) for this student.
     */
    public function actionLogs()
    {
        return $this->hasMany(StudentActionLog::class);
    }

    /**
     * Get the surat (editor) for this student.
     */
    public function surat()
    {
        return $this->hasMany(Surat::class);
    }

    /**
     * Get the student mutations (mutasi) for this student.
     */
    public function studentMutations()
    {
        return $this->hasMany(StudentMutation::class);
    }

    /**
     * Get the counseling sessions for this student.
     */
    public function counselingSessions()
    {
        return $this->hasMany(CounselingSession::class);
    }

    public function uksVisits()
    {
        return $this->hasMany(UksVisit::class);
    }

    /**
     * Get the student attendances (per lesson session).
     */
    public function studentAttendances()
    {
        return $this->hasMany(StudentAttendance::class);
    }

    /**
     * Get the grades (nilai) for this student.
     */
    public function grades()
    {
        return $this->hasMany(Grade::class);
    }

    /**
     * Get the document pickups (pengambilan ijazah) for this student (alumni).
     */
    public function documentPickups()
    {
        return $this->hasMany(DocumentPickup::class);
    }

    /**
     * Get the alumni destinations (tracking lanjut kemana setelah lulus).
     */
    public function alumniDestinations()
    {
        return $this->hasMany(AlumniDestination::class);
    }

    /**
     * Get exam participants (peserta ujian) for this student.
     */
    public function examParticipants()
    {
        return $this->hasMany(ExamParticipant::class);
    }

    /**
     * Get the extracurricular enrollments (peserta ekskul) for this student.
     */
    public function extracurricularEnrollments()
    {
        return $this->hasMany(ExtracurricularStudent::class);
    }

    /**
     * Get the extracurriculars (ekskul) this student participates in.
     */
    public function extracurriculars()
    {
        return $this->belongsToMany(Extracurricular::class, 'extracurricular_student')
            ->withPivot('academic_year_id', 'semester_id', 'joined_at', 'left_at', 'status', 'notes')
            ->withTimestamps();
    }

    /**
     * Get the change requests (perubahan data) for this student.
     */
    public function studentChangeRequests()
    {
        return $this->hasMany(StudentChangeRequest::class);
    }

    /**
     * Get the latest/current alumni destination (one record, most recent by year_entered or created_at).
     */
    public function currentAlumniDestination()
    {
        return $this->hasOne(AlumniDestination::class)->latest('year_entered')->latest('id');
    }

    /**
     * Get library loans where this student is the borrower.
     */
    public function libraryLoans()
    {
        return $this->hasMany(LibraryLoan::class, 'borrower_id')->where('library_loans.borrower_type', 'Student');
    }

    /**
     * Scope a query to only include active students.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'Aktif');
    }

    /**
     * Scope a query to only include alumni (lulus).
     */
    public function scopeAlumni($query)
    {
        return $query->where('status', 'Lulus');
    }

    /**
     * Scope a query to filter by graduation year.
     */
    public function scopeByGraduationYear($query, int $year)
    {
        return $query->where('graduation_year', $year);
    }

    /**
     * Scope a query to filter by institution.
     */
    public function scopeForInstitution($query, $institutionId)
    {
        return $query->where('institution_id', $institutionId);
    }

    /**
     * Scope a query to filter by class.
     */
    public function scopeByClass($query, string $class)
    {
        return $query->where('class', $class);
    }

    /**
     * Scope a query to filter by gender.
     */
    public function scopeByGender($query, string $gender)
    {
        return $query->where('gender', $gender);
    }

    /**
     * Scope a query to filter by academic year.
     */
    public function scopeByAcademicYear($query, string $academicYear)
    {
        return $query->where('academic_year', $academicYear);
    }

    /**
     * Scope a query to filter by academic year ID.
     */
    public function scopeByAcademicYearId($query, int $academicYearId)
    {
        return $query->where('academic_year_id', $academicYearId);
    }

    /**
     * Scope a query to filter by semester ID.
     */
    public function scopeBySemesterId($query, int $semesterId)
    {
        return $query->where('semester_id', $semesterId);
    }

    /**
     * Get the academic year for this student.
     */
    public function academicYear()
    {
        return $this->belongsTo(AcademicYear::class, 'academic_year_id');
    }

    /**
     * Get the semester for this student.
     */
    public function semester()
    {
        return $this->belongsTo(Semester::class, 'semester_id');
    }

    /**
     * Check if student has user account.
     */
    public function hasUserAccount(): bool
    {
        if ($this->relationLoaded('userAccount')) {
            $account = $this->getRelation('userAccount');

            return $account instanceof User && $account->role === 'student';
        }

        if ($this->nik && User::where('login_nik', $this->nik)->where('role', 'student')->exists()) {
            return true;
        }

        if ($this->email && User::where('email', $this->email)->where('role', 'student')->exists()) {
            return true;
        }

        return false;
    }

    /**
     * Student is eligible for auto login account (valid NIK + birth date).
     */
    public function isEligibleForLoginAccount(): bool
    {
        $nik = trim((string) ($this->nik ?? ''));

        return preg_match('/^\d{16}$/', $nik) === 1 && !empty($this->birth_date);
    }
}
