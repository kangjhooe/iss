<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Student extends Model
{
    use HasFactory, SoftDeletes;

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
        'residence_type',
        'class',
        'academic_year',
        'academic_year_id',
        'status',
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
     */
    public function class()
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
     * Get the user account associated with this student (by email).
     */
    public function userAccount()
    {
        return $this->belongsTo(User::class, 'email', 'email');
    }

    /**
     * Scope a query to only include active students.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'Aktif');
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
     * Get the academic year for this student.
     */
    public function academicYear()
    {
        return $this->belongsTo(AcademicYear::class, 'academic_year_id');
    }

    /**
     * Check if student has user account.
     */
    public function hasUserAccount(): bool
    {
        return $this->email && User::where('email', $this->email)->exists();
    }
}
