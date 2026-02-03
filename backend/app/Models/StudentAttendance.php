<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\Auditable;

class StudentAttendance extends Model
{
    use HasFactory, SoftDeletes, Auditable;

    protected $table = 'student_attendances';

    public const STATUS_HADIR = 'hadir';
    public const STATUS_ALPHA = 'alpha';
    public const STATUS_IZIN = 'izin';
    public const STATUS_SAKIT = 'sakit';
    public const STATUS_DINAS_LUAR = 'dinas_luar';

    public const STATUSES = [
        self::STATUS_HADIR => 'Hadir',
        self::STATUS_ALPHA => 'Alpha',
        self::STATUS_IZIN => 'Izin',
        self::STATUS_SAKIT => 'Sakit',
        self::STATUS_DINAS_LUAR => 'Dinas Luar',
    ];

    protected $fillable = [
        'institution_id',
        'teaching_journal_id',
        'student_id',
        'status',
        'notes',
    ];

    public function institution()
    {
        return $this->belongsTo(Institution::class);
    }

    public function teachingJournal()
    {
        return $this->belongsTo(TeachingJournal::class);
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function scopeForInstitution($query, int $institutionId)
    {
        return $query->where('institution_id', $institutionId);
    }

    public function scopeForTeachingJournal($query, int $teachingJournalId)
    {
        return $query->where('teaching_journal_id', $teachingJournalId);
    }

    public function scopeForStudent($query, int $studentId)
    {
        return $query->where('student_id', $studentId);
    }
}
