<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MemorizationAyahProgress extends Model
{
    public const STATUS_DEPOSITED = 'deposited';
    public const STATUS_REVISED = 'revised';

    protected $table = 'memorization_ayah_progress';

    protected $fillable = [
        'extracurricular_id',
        'student_id',
        'surah_number',
        'ayah_number',
        'status',
        'last_deposit_id',
        'deposited_at',
    ];

    protected function casts(): array
    {
        return [
            'surah_number' => 'integer',
            'ayah_number' => 'integer',
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

    public function lastDeposit(): BelongsTo
    {
        return $this->belongsTo(MemorizationDeposit::class, 'last_deposit_id');
    }
}
