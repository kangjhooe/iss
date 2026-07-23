<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubjectKkm extends Model
{
    use HasFactory;

    protected $table = 'subject_kkms';

    protected $fillable = [
        'institution_id',
        'subject_id',
        'grade',
        'semester_id',
        'kkm',
        'set_by_employee_id',
    ];

    protected function casts(): array
    {
        return [
            'grade' => 'integer',
            'kkm' => 'decimal:2',
        ];
    }

    public function institution()
    {
        return $this->belongsTo(Institution::class);
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function semester()
    {
        return $this->belongsTo(Semester::class);
    }

    public function setByEmployee()
    {
        return $this->belongsTo(Employee::class, 'set_by_employee_id');
    }

    /**
     * Predikat nilai akhir berdasarkan KKM (sama pola ekskul):
     * <KKM = D; di atas KKM dibagi C, B, A.
     */
    public static function predicateFromScore(?float $score, float|int|string|null $kkm): ?string
    {
        if ($score === null || $kkm === null || $kkm === '') {
            return null;
        }

        $kkmValue = (float) $kkm;
        if ($kkmValue < 0) {
            $kkmValue = 0;
        }
        if ($kkmValue > 100) {
            $kkmValue = 100;
        }

        if ($score < $kkmValue) {
            return 'D';
        }

        $band = (100 - $kkmValue) / 3;
        if ($band <= 0) {
            return 'A';
        }

        if ($score >= $kkmValue + (2 * $band)) {
            return 'A';
        }
        if ($score >= $kkmValue + $band) {
            return 'B';
        }

        return 'C';
    }

    public static function isTuntas(?float $score, float|int|string|null $kkm): ?bool
    {
        if ($score === null || $kkm === null || $kkm === '') {
            return null;
        }

        return (float) $score >= (float) $kkm;
    }
}
