<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QuranSurah extends Model
{
    protected $table = 'quran_surahs';

    protected $primaryKey = 'number';

    public $incrementing = false;

    protected $keyType = 'int';

    protected $fillable = [
        'number',
        'name_ar',
        'name_id',
        'name_latin',
        'ayah_count',
        'revelation_order',
        'revelation_type',
    ];

    protected function casts(): array
    {
        return [
            'number' => 'integer',
            'ayah_count' => 'integer',
            'revelation_order' => 'integer',
        ];
    }

    public function ayahs(): HasMany
    {
        return $this->hasMany(QuranAyah::class, 'surah_number', 'number');
    }
}
