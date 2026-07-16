<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PiketIncident extends Model
{
    use SoftDeletes;

    protected $table = 'piket_incidents';

    public const TYPE_KELAS_KOSONG = 'kelas_kosong';
    public const TYPE_TERLAMBAT_GURU = 'terlambat_guru';
    public const TYPE_TERLAMBAT_SISWA = 'terlambat_siswa';
    public const TYPE_LAINNYA = 'lainnya';

    public const TYPES = [
        self::TYPE_KELAS_KOSONG => 'Kelas Kosong',
        self::TYPE_TERLAMBAT_GURU => 'Keterlambatan Guru',
        self::TYPE_TERLAMBAT_SISWA => 'Keterlambatan Siswa',
        self::TYPE_LAINNYA => 'Lainnya',
    ];

    public const STATUS_OPEN = 'open';
    public const STATUS_CONFIRMED = 'confirmed';
    public const STATUS_RESOLVED = 'resolved';
    public const STATUS_DISMISSED = 'dismissed';

    public const STATUSES = [
        self::STATUS_OPEN => 'Terbuka',
        self::STATUS_CONFIRMED => 'Dikonfirmasi',
        self::STATUS_RESOLVED => 'Selesai',
        self::STATUS_DISMISSED => 'Diabaikan',
    ];

    protected $fillable = [
        'institution_id',
        'incident_date',
        'incident_type',
        'period',
        'class_id',
        'subject_id',
        'employee_id',
        'student_id',
        'lesson_schedule_id',
        'piket_log_id',
        'detected_at',
        'minutes_late',
        'description',
        'source',
        'status',
        'created_by',
        'resolved_by',
        'resolved_at',
    ];

    protected function casts(): array
    {
        return [
            'incident_date' => 'date',
            'period' => 'integer',
            'minutes_late' => 'integer',
            'detected_at' => 'datetime:H:i',
            'resolved_at' => 'datetime',
        ];
    }

    public function institution()
    {
        return $this->belongsTo(Institution::class);
    }

    public function schoolClass()
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function lessonSchedule()
    {
        return $this->belongsTo(LessonSchedule::class);
    }

    public function piketLog()
    {
        return $this->belongsTo(PiketLog::class, 'piket_log_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function resolver()
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    public function violation()
    {
        return $this->hasOne(Violation::class, 'piket_incident_id');
    }

    public function teacherViolation()
    {
        return $this->hasOne(TeacherViolation::class, 'piket_incident_id');
    }

    public function isTeacherRelated(): bool
    {
        return in_array($this->incident_type, [
            self::TYPE_TERLAMBAT_GURU,
            self::TYPE_KELAS_KOSONG,
        ], true);
    }

    public function scopeForInstitution($query, int $institutionId)
    {
        return $query->where('institution_id', $institutionId);
    }

    public function getTypeLabelAttribute(): string
    {
        return self::TYPES[$this->incident_type] ?? $this->incident_type;
    }
}
