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

    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    public const SOURCE_MANUAL = 'manual';

    public const SOURCE_AUTO_ENROLLMENT = 'auto_enrollment';

    protected $attributes = [
        'status' => self::STATUS_APPROVED,
        'source' => self::SOURCE_MANUAL,
    ];

    protected $fillable = [
        'institution_id',
        'student_id',
        'destination_type',
        'destination_name',
        'program_or_position',
        'year_entered',
        'notes',
        'status',
        'source',
        'related_student_id',
        'related_institution_id',
        'reviewed_by',
        'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'year_entered' => 'integer',
            'reviewed_at' => 'datetime',
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

    public function relatedStudent()
    {
        return $this->belongsTo(Student::class, 'related_student_id');
    }

    public function relatedInstitution()
    {
        return $this->belongsTo(Institution::class, 'related_institution_id');
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isAutoEnrollment(): bool
    {
        return $this->source === self::SOURCE_AUTO_ENROLLMENT;
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
