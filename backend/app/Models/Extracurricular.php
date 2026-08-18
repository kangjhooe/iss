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

    public const DAY_NAMES = [
        1 => 'Senin',
        2 => 'Selasa',
        3 => 'Rabu',
        4 => 'Kamis',
        5 => 'Jumat',
        6 => 'Sabtu',
    ];

    protected $fillable = [
        'institution_id',
        'name',
        'description',
        'supervisor_employee_id',
        'academic_year_id',
        'semester_id',
        'capacity',
        'kkm',
        'status',
        'is_pramuka',
        'days_of_week',
        'start_time',
        'end_time',
        'room_id',
        'is_outdoor',
        'location_note',
    ];

    protected function casts(): array
    {
        return [
            'capacity' => 'integer',
            'kkm' => 'decimal:2',
            'days_of_week' => 'array',
            'is_pramuka' => 'boolean',
            'is_outdoor' => 'boolean',
            'start_time' => 'datetime:H:i',
            'end_time' => 'datetime:H:i',
        ];
    }

    public function getKkmValueAttribute(): float
    {
        $kkm = $this->kkm;

        return $kkm !== null ? (float) $kkm : 75.0;
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

    public function sessions()
    {
        return $this->hasMany(ExtracurricularSession::class);
    }

    public function grades()
    {
        return $this->hasMany(ExtracurricularGrade::class);
    }

    public function scopeForInstitution($query, $institutionId)
    {
        return $query->where('institution_id', $institutionId);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'Aktif');
    }

    public function getDayLabelsAttribute(): array
    {
        $days = $this->days_of_week ?? [];
        if (!is_array($days)) {
            return [];
        }
        sort($days);

        return array_values(array_filter(array_map(
            fn ($d) => self::DAY_NAMES[(int) $d] ?? null,
            $days
        )));
    }

    public function getLocationLabelAttribute(): ?string
    {
        if ($this->is_outdoor) {
            return $this->location_note
                ? 'Di luar ruangan (' . $this->location_note . ')'
                : 'Di luar ruangan';
        }
        if ($this->relationLoaded('room') && $this->room) {
            return $this->room->name;
        }

        return null;
    }
}
