<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\Auditable;

class SubjectCatalog extends Model
{
    use HasFactory, SoftDeletes, Auditable;

    protected $table = 'subject_catalog';

    /**
     * Prefix digit pertama kode per kelompok jenjang.
     * 1=SD/MI, 2=SMP/MTs, 3=SMA/MA, 4=SMK/MAK, 5=PAUD/TK
     */
    public const JENJANG_PREFIX = [
        'SD' => '1',
        'SMP' => '2',
        'SMA' => '3',
        'SMK' => '4',
        'PAUD' => '5',
    ];

    public const JENJANG_LABELS = [
        'SD' => 'SD/MI',
        'SMP' => 'SMP/MTs',
        'SMA' => 'SMA/MA',
        'SMK' => 'SMK/MAK',
        'PAUD' => 'PAUD/TK',
    ];

    protected $fillable = [
        'code',
        'name',
        'jenjang',
        'description',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForJenjang($query, string $jenjang)
    {
        return $query->where('jenjang', $jenjang);
    }

    public function getJenjangLabelAttribute(): string
    {
        return self::JENJANG_LABELS[$this->jenjang] ?? $this->jenjang;
    }

    public static function expectedPrefixForJenjang(string $jenjang): ?string
    {
        return self::JENJANG_PREFIX[$jenjang] ?? null;
    }

    public static function codeMatchesJenjang(string $code, string $jenjang): bool
    {
        $prefix = self::expectedPrefixForJenjang($jenjang);
        if ($prefix === null) {
            return false;
        }

        return preg_match('/^\d{4}$/', $code) === 1 && str_starts_with($code, $prefix);
    }
}
