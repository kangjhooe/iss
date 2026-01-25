<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\Auditable;

class AcademicYear extends Model
{
    use HasFactory, SoftDeletes, Auditable;

    protected $table = 'academic_years';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'code',
        'name',
        'start_date',
        'end_date',
        'status',
        'description',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }

    /**
     * Get the semesters for this academic year.
     */
    public function semesters()
    {
        return $this->hasMany(Semester::class);
    }

    /**
     * Get the classes for this academic year.
     */
    public function classes()
    {
        return $this->hasMany(SchoolClass::class, 'academic_year_id');
    }

    /**
     * Get the students for this academic year.
     */
    public function students()
    {
        return $this->hasMany(Student::class, 'academic_year_id');
    }

    /**
     * Get the class student history for this academic year.
     */
    public function classStudentHistory()
    {
        return $this->hasMany(ClassStudentHistory::class, 'academic_year_id');
    }

    /**
     * Scope a query to only include active academic years.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'Aktif');
    }

    /**
     * Scope a query to filter by status.
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Get the current active academic year.
     */
    public static function getCurrent()
    {
        return static::active()->first();
    }

    /**
     * Check if this academic year is active.
     */
    public function isActive(): bool
    {
        return $this->status === 'Aktif';
    }

    /**
     * Check if this academic year is current (based on date).
     */
    public function isCurrent(): bool
    {
        $now = now();
        return $now->between($this->start_date, $this->end_date);
    }
}
