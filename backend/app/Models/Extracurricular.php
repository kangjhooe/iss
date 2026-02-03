<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\Auditable;

class Extracurricular extends Model
{
    use HasFactory, SoftDeletes, Auditable;

    protected $table = 'extracurriculars';

    protected $fillable = [
        'institution_id',
        'name',
        'description',
        'supervisor_employee_id',
        'academic_year_id',
        'semester_id',
        'capacity',
        'status',
        'day_of_week',
        'start_time',
        'end_time',
        'room_id',
    ];

    protected function casts(): array
    {
        return [
            'capacity' => 'integer',
            'day_of_week' => 'integer',
            'start_time' => 'datetime:H:i',
            'end_time' => 'datetime:H:i',
        ];
    }

    public function institution()
    {
        return $this->belongsTo(Institution::class);
    }

    /**
     * Guru penanggung jawab (pembina) ekskul.
     */
    public function supervisor()
    {
        return $this->belongsTo(Employee::class, 'supervisor_employee_id');
    }

    public function academicYear()
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function semester()
    {
        return $this->belongsTo(Semester::class);
    }

    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    /**
     * Peserta ekskul (many-to-many dengan pivot extracurricular_student).
     */
    public function students()
    {
        return $this->belongsToMany(Student::class, 'extracurricular_student')
            ->withPivot('academic_year_id', 'semester_id', 'joined_at', 'left_at', 'status', 'notes')
            ->withTimestamps();
    }

    /**
     * Pivot records untuk filter per semester/status.
     */
    public function extracurricularStudents()
    {
        return $this->hasMany(ExtracurricularStudent::class);
    }

    public function scopeForInstitution($query, $institutionId)
    {
        return $query->where('institution_id', $institutionId);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'Aktif');
    }
}
