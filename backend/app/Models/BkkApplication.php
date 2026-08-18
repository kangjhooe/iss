<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BkkApplication extends Model
{
    use SoftDeletes;

    public const STATUSES = ['diajukan', 'seleksi', 'diterima', 'ditolak'];

    protected $fillable = [
        'institution_id',
        'bkk_vacancy_id',
        'student_id',
        'status',
        'applied_at',
        'alumni_destination_id',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'applied_at' => 'date',
        ];
    }

    public function institution()
    {
        return $this->belongsTo(Institution::class);
    }

    public function vacancy()
    {
        return $this->belongsTo(BkkVacancy::class, 'bkk_vacancy_id');
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function alumniDestination()
    {
        return $this->belongsTo(AlumniDestination::class);
    }

    public function scopeForInstitution($query, int $institutionId)
    {
        return $query->where('institution_id', $institutionId);
    }
}
