<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\Auditable;

class Employee extends Model
{
    use HasFactory, SoftDeletes, Auditable;

    protected $table = 'employee';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'institution_id',
        'nik',
        'type',
        'nip',
        'nuptk',
        'name',
        'gender',
        'birth_date',
        'birth_place',
        'address',
        'village',
        'sub_district',
        'district',
        'province',
        'postal_code',
        'wilayah_province_code',
        'wilayah_regency_code',
        'wilayah_district_code',
        'wilayah_village_code',
        'phone',
        'email',
        'religion',
        'employment_status',
        'education_level',
        'major',
        'subject',
        'status',
        'join_date',
        'notes',
        'certification_status',
        'certification_date',
        'teacher_registration_number',
        'certification_number',
        'certification_issuing_authority',
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
            'join_date' => 'date',
            'certification_date' => 'date',
        ];
    }

    /**
     * Get the institution that owns the employee.
     */
    public function institution()
    {
        return $this->belongsTo(Institution::class);
    }

    /**
     * Get the non-induk assignments for this employee.
     */
    public function assignments()
    {
        return $this->hasMany(EmployeeInstitutionAssignment::class, 'employee_id');
    }

    /**
     * Get the mutation records (mutasi guru) for this employee.
     */
    public function mutations()
    {
        return $this->hasMany(TeacherMutation::class, 'employee_id');
    }

    /**
     * Get the change requests (perubahan data) for this employee.
     */
    public function teacherChangeRequests()
    {
        return $this->hasMany(TeacherChangeRequest::class, 'employee_id');
    }

    /**
     * Get the user account associated with this employee (by email).
     */
    public function userAccount()
    {
        return $this->belongsTo(User::class, 'email', 'email');
    }

    /**
     * Scope a query to only include active employees.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'Aktif');
    }

    /**
     * Scope a query to filter by institution (induk + approved non-induk).
     */
    public function scopeForInstitution($query, $institutionId)
    {
        return \App\Support\InstitutionContext::scopeEmployeesForInstitution($query, (int) $institutionId);
    }

    /**
     * Scope a query to filter by type.
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope a query to filter by employment status.
     */
    public function scopeByEmploymentStatus($query, string $status)
    {
        return $query->where('employment_status', $status);
    }

    /**
     * Scope a query to filter by education level.
     */
    public function scopeByEducationLevel($query, string $level)
    {
        return $query->where('education_level', $level);
    }

    /**
     * Scope a query to filter by subject.
     */
    public function scopeBySubject($query, string $subject)
    {
        return $query->where('subject', $subject);
    }

    /**
     * Get the classes where this employee is the wali kelas (only for teachers).
     */
    public function classes()
    {
        return $this->hasMany(SchoolClass::class, 'teacher_id');
    }

    /**
     * Get the lesson schedules (jadwal mengajar) for this employee.
     */
    public function lessonSchedules()
    {
        return $this->hasMany(LessonSchedule::class);
    }

    /**
     * Get the educations for this employee.
     */
    public function educations()
    {
        return $this->hasMany(EmployeeEducation::class)->orderBy('order', 'asc');
    }

    /**
     * Get the documents for this employee.
     */
    public function documents()
    {
        return $this->hasMany(EmployeeDocument::class)->orderBy('created_at', 'desc');
    }

    /**
     * Get the employee attendances (per day).
     */
    public function employeeAttendances()
    {
        return $this->hasMany(EmployeeAttendance::class);
    }

    /**
     * Get the teaching journals (jurnal mengajar) for this employee.
     */
    public function teachingJournals()
    {
        return $this->hasMany(TeachingJournal::class, 'employee_id');
    }

    /**
     * Get the grades (nilai) given by this employee.
     */
    public function grades()
    {
        return $this->hasMany(Grade::class, 'employee_id');
    }

    /**
     * Get the extracurriculars (ekskul) where this employee is the supervisor (pembina).
     */
    public function supervisedExtracurriculars()
    {
        return $this->hasMany(Extracurricular::class, 'supervisor_employee_id');
    }

    /**
     * Get the additional duties (tugas tambahan) for this employee.
     * One employee can have multiple additional duties.
     */
    public function additionalDuties()
    {
        return $this->belongsToMany(AdditionalDuty::class, 'employee_additional_duties')
            ->withPivot(['started_at', 'ended_at'])
            ->withTimestamps();
    }

    public function activeAdditionalDuties()
    {
        return $this->additionalDuties()
            ->where(function ($query) {
                $query->whereNull('employee_additional_duties.ended_at')
                    ->orWhere('employee_additional_duties.ended_at', '>', now());
            });
    }

    /**
     * Program keahlian yang diampu sebagai Kaprog.
     */
    public function programKeahlians()
    {
        return $this->belongsToMany(ProgramKeahlian::class, 'employee_program_keahlian', 'employee_id', 'program_keahlian_id')
            ->withTimestamps();
    }

    public function leaveRequests()
    {
        return $this->hasMany(EmployeeLeaveRequest::class);
    }

    public function decrees()
    {
        return $this->hasMany(EmployeeDecree::class);
    }

    public function structuralPositions()
    {
        return $this->hasMany(EmployeeStructuralPosition::class);
    }

    /**
     * Get the library loans where this employee is the borrower.
     */
    public function libraryLoans()
    {
        return $this->hasMany(LibraryLoan::class, 'borrower_id')
            ->where('library_loans.borrower_type', 'Employee');
    }

    /**
     * Check if employee has user account.
     */
    public function hasUserAccount(): bool
    {
        return $this->email && User::where('email', $this->email)->exists();
    }

    /**
     * Check if employee is a teacher.
     */
    public function isTeacher(): bool
    {
        return $this->type === 'Guru';
    }
}
