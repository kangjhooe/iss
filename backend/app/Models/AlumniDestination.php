<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AlumniDestination extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'alumni_destinations';

    public const DESTINATION_TYPES = [
        'Sekolah' => 'Lanjut Sekolah (SMA/SMK/dll)',
        'Perguruan_Tinggi' => 'Perguruan Tinggi',
        'Kerja' => 'Bekerja',
        'Wirausaha' => 'Wirausaha',
        'Lainnya' => 'Lainnya',
    ];

    protected $fillable = [
        'institution_id',
        'student_id',
        'destination_type',
        'destination_name',
        'program_or_position',
        'year_entered',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'year_entered' => 'integer',
        ];
    }

    public function institution()
    {
        return $this->belongsTo(Institution::class);
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function scopeForInstitution($query, int $institutionId)
    {
        return $query->where('institution_id', $institutionId);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('destination_type', $type);
    }
}
