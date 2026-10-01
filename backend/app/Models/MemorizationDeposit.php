<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MemorizationDeposit extends Model
{
    public const QUALITY_LANCAR = 'lancar';
    public const QUALITY_KURANG = 'kurang';
    public const QUALITY_MENGULANG = 'mengulang';

    protected $table = 'memorization_deposits';

    protected $fillable = [
        'institution_id',
        'extracurricular_id',
        'student_id',
        'session_id',
        'surah_number',
        'ayah_from',
        'ayah_to',
        'quality',
        'notes',
        'recorded_by',
        'deposited_at',
    ];

    protected function casts(): array
    {
        return [
            'surah_number' => 'integer',
            'ayah_from' => 'integer',
            'ayah_to' => 'integer',
            'deposited_at' => 'datetime',
        ];
    }

    public function extracurricular(): BelongsTo
    {
        return $this->belongsTo(Extracurricular::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(ExtracurricularSession::class, 'session_id');
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'recorded_by');
    }

    public function surah(): BelongsTo
    {
        return $this->belongsTo(QuranSurah::class, 'surah_number', 'number');
    }
}
