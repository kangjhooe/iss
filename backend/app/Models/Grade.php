<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\Auditable;

class Grade extends Model
{
    use HasFactory, Auditable;

    protected $table = 'grades';

    public const TYPE_UTS = 'uts';
    public const TYPE_UAS = 'uas';
    public const TYPE_NILAI_AKHIR = 'nilai_akhir';

    /** Legacy (setelah migrasi diganti penilaian_*) */
    public const TYPE_UH = 'uh';
    public const TYPE_TUGAS = 'tugas';

    public const TYPES = [
        self::TYPE_UTS => 'UTS',
        self::TYPE_UAS => 'UAS',
        self::TYPE_NILAI_AKHIR => 'Nilai Akhir',
    ];

    protected $fillable = [
        'institution_id',
        'academic_year_id',
        'semester_id',
        'class_id',
        'subject_id',
        'student_id',
        'employee_id',
        'grade_type',
        'value',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'decimal:2',
        ];
    }

    public static function penilaianType(int $n): string
    {
        return 'penilaian_' . max(1, $n);
    }

    public static function isPenilaianType(string $type): bool
    {
        return (bool) preg_match('/^penilaian_[1-9]\d*$/', $type);
    }

    public static function penilaianIndex(string $type): ?int
    {
        if (!preg_match('/^penilaian_([1-9]\d*)$/', $type, $m)) {
            return null;
        }

        return (int) $m[1];
    }

    public static function isValidType(string $type): bool
    {
        return in_array($type, [self::TYPE_UTS, self::TYPE_UAS, self::TYPE_NILAI_AKHIR], true)
            || self::isPenilaianType($type);
    }

    public static function getTypeLabel(string $type): string
    {
        if (self::isPenilaianType($type)) {
            $n = self::penilaianIndex($type);

            return 'Penilaian ' . $n;
        }

        return self::TYPES[$type] ?? $type;
    }

    public function institution()
    {
        return $this->belongsTo(Institution::class);
    }

    public function academicYear()
    {
        return $this->belongsTo(AcademicYear::class, 'academic_year_id');
    }

    public function semester()
    {
        return $this->belongsTo(Semester::class);
    }

    public function schoolClass()
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function scopeForInstitution($query, int $institutionId)
    {
        return $query->where('institution_id', $institutionId);
    }

    public function scopeForSemester($query, int $semesterId)
    {
        return $query->where('semester_id', $semesterId);
    }

    public function scopeForClass($query, int $classId)
    {
        return $query->where('class_id', $classId);
    }

    public function scopeForSubject($query, int $subjectId)
    {
        return $query->where('subject_id', $subjectId);
    }

    public function scopeForStudent($query, int $studentId)
    {
        return $query->where('student_id', $studentId);
    }
}
