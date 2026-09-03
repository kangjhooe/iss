<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Achievement extends Model
{
    use HasFactory;

    protected $table = 'achievements';

    public const STATUS_PENDING = 'pending';
    public const STATUS_DICATAT = 'dicatat';
    public const STATUS_DITOLAK = 'ditolak';

    public const PURPOSE_AKREDITASI = 'akreditasi';
    public const PURPOSE_APRESIASI = 'apresiasi';

    public const PURPOSES = [
        self::PURPOSE_AKREDITASI,
        self::PURPOSE_APRESIASI,
    ];

    public const LEVELS = [
        'sekolah',
        'kabupaten',
        'provinsi',
        'nasional',
        'internasional',
    ];

    public const RANKS = [
        'juara_1',
        'juara_2',
        'juara_3',
        'finalis',
        'peserta',
        'lainnya',
    ];

    public const STATUSES_COUNTING_POINTS = [
        self::STATUS_DICATAT,
    ];

    protected $fillable = [
        'institution_id',
        'student_id',
        'achievement_type_id',
        'purpose',
        'title',
        'level',
        'rank',
        'given_by',
        'achievement_date',
        'point_value',
        'notes',
        'status',
        'reviewed_by',
        'reviewed_at',
        'review_notes',
        'academic_year_id',
        'semester_id',
    ];

    protected function casts(): array
    {
        return [
            'achievement_date' => 'date',
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

    public function achievementType()
    {
        return $this->belongsTo(AchievementType::class, 'achievement_type_id');
    }

    public function giver()
    {
        return $this->belongsTo(User::class, 'given_by');
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function academicYear()
    {
        return $this->belongsTo(AcademicYear::class, 'academic_year_id');
    }

    public function semester()
    {
        return $this->belongsTo(Semester::class, 'semester_id');
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function scopeForInstitution($query, int $institutionId)
    {
        return $query->where('institution_id', $institutionId);
    }

    public function scopeForStudent($query, int $studentId)
    {
        return $query->where('student_id', $studentId);
    }

    public function scopeCountingPoints($query)
    {
        return $query->whereIn('status', self::STATUSES_COUNTING_POINTS);
    }
}
