<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Employee extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'employee';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'institution_id',
        'type',
        'nip',
        'nuptk',
        'name',
        'gender',
        'birth_date',
        'birth_place',
        'address',
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
     * Scope a query to filter by institution.
     */
    public function scopeForInstitution($query, $institutionId)
    {
        return $query->where('institution_id', $institutionId);
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
