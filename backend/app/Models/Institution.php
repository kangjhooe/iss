<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Institution extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'institution';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'npsn',
        'nss',
        'level',
        'type',
        'address',
        'village',
        'sub_district',
        'district',
        'province',
        'postal_code',
        'phone',
        'email',
        'website',
        'principal_name',
        'principal_nip',
        'description',
        'is_active',
        'active_academic_year_id',
        'active_semester_id',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
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
     */
    public function teachers()
    {
        return $this->hasMany(Teacher::class);
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
