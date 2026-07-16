<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Violation extends Model
{
    use HasFactory, Auditable;

    protected $table = 'violations';

    public const STATUS_PENDING = 'pending';
    public const STATUS_DICATAT = 'dicatat';
    public const STATUS_SANKSI = 'sanksi_diberikan';
    public const STATUS_FOLLOW_UP = 'follow_up';
    public const STATUS_SELESAI = 'selesai';
    public const STATUS_DITOLAK = 'ditolak';

    /** Status yang dihitung ke skor poin siswa. */
    public const STATUSES_COUNTING_POINTS = [
        self::STATUS_DICATAT,
        self::STATUS_SANKSI,
        self::STATUS_FOLLOW_UP,
        self::STATUS_SELESAI,
    ];

    public const STATUSES_WORKFLOW = [
        self::STATUS_DICATAT,
        self::STATUS_SANKSI,
        self::STATUS_FOLLOW_UP,
        self::STATUS_SELESAI,
    ];

    protected $fillable = [
        'institution_id',
        'student_id',
        'violation_type_id',
        'reported_by',
        'reviewed_by',
        'reviewed_at',
        'violation_date',
        'sanction',
        'status',
        'description',
        'follow_up_notes',
        'review_notes',
        'academic_year_id',
        'semester_id',
        'class_id',
        'piket_incident_id',
    ];

    protected function casts(): array
    {
        return [
            'violation_date' => 'date',
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

    public function violationType()
    {
        return $this->belongsTo(ViolationType::class, 'violation_type_id');
    }

    public function reporter()
    {
        return $this->belongsTo(User::class, 'reported_by');
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

    public function schoolClass()
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    public function piketIncident()
    {
        return $this->belongsTo(PiketIncident::class, 'piket_incident_id');
    }

    public function scopeForInstitution($query, int $institutionId)
    {
        return $query->where('institution_id', $institutionId);
    }

    public function scopeForStudent($query, int $studentId)
    {
        return $query->where('student_id', $studentId);
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeCountingPoints($query)
    {
        return $query->whereIn('status', self::STATUSES_COUNTING_POINTS);
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }
}
