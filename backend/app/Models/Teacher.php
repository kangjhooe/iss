<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\Auditable;

class Teacher extends Model
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
     * Get the institution that owns the teacher.
     */
    public function institution()
    {
        return $this->belongsTo(Institution::class);
    }

    /**
     * Get the non-induk assignments for this teacher.
     */
    public function assignments()
    {
        return $this->hasMany(EmployeeInstitutionAssignment::class, 'employee_id');
    }

    /**
     * Get the user account associated with this teacher (by email).
     */
    public function userAccount()
    {
        return $this->belongsTo(User::class, 'email', 'email');
    }

    /**
     * Scope a query to only include active teachers.
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
     * Get the classes where this teacher is the wali kelas.
     */
    public function classes()
    {
        return $this->hasMany(SchoolClass::class, 'teacher_id');
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        // Always filter by type = 'Guru' for Teacher model
        static::addGlobalScope('teacher', function ($builder) {
            $builder->where('type', 'Guru');
        });

        // Boot Auditable trait
        static::bootAuditable();
    }

    /**
     * Check if teacher has user account.
     */
    public function hasUserAccount(): bool
    {
        return $this->email && User::where('email', $this->email)->exists();
    }
}
