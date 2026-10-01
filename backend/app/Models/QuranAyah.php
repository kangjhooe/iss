<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuranAyah extends Model
{
    protected $table = 'quran_ayahs';

    protected $fillable = [
        'surah_number',
        'ayah_number',
        'text_ar',
        'text_id',
    ];

    protected function casts(): array
    {
        return [
            'surah_number' => 'integer',
            'ayah_number' => 'integer',
        ];
    }

    public function surah(): BelongsTo
    {
        return $this->belongsTo(QuranSurah::class, 'surah_number', 'number');
    }
}
