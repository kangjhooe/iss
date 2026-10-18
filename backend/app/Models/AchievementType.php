<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AchievementType extends Model
{
    use HasFactory;

    protected $table = 'achievement_types';

    public const PURPOSE_AKREDITASI = 'akreditasi';
    public const PURPOSE_APRESIASI = 'apresiasi';

    public const PURPOSES = [
        self::PURPOSE_AKREDITASI,
        self::PURPOSE_APRESIASI,
    ];

    protected $fillable = [
        'institution_id',
        'name',
        'code',
        'point_value',
        'level_point_values',
        'category',
        'purpose',
        'description',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'level_point_values' => 'array',
        ];
    }

    public function institution()
    {
        return $this->belongsTo(Institution::class);
    }

    public function achievements()
    {
        return $this->hasMany(Achievement::class, 'achievement_type_id');
    }

    public function scopeForInstitution($query, int $institutionId)
    {
        return $query->where('institution_id', $institutionId);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function hasLevelPointValues(): bool
    {
        if (!is_array($this->level_point_values)) {
            return false;
        }

        foreach ($this->level_point_values as $value) {
            if ($value !== null && $value !== '') {
                return true;
            }
        }

        return false;
    }

    /**
     * Prestasi akreditasi yang punya angka per tingkat wajib memilih tingkat.
     * Poin dasar hanya dipakai untuk tingkat yang kotaknya dikosongkan.
     */
    public function requiresLevel(?string $purpose = null): bool
    {
        $purpose = $purpose ?: ($this->purpose ?? self::PURPOSE_AKREDITASI);

        return $purpose === self::PURPOSE_AKREDITASI && $this->hasLevelPointValues();
    }

    /**
     * Poin prestasi: override manual > poin per tingkat > poin dasar jenis.
     */
    public function resolvePointValue(?string $level = null, ?int $override = null): int
    {
        if ($override !== null) {
            return max(0, $override);
        }

        if ($level && is_array($this->level_point_values) && array_key_exists($level, $this->level_point_values)) {
            $levelPoint = $this->level_point_values[$level];
            if ($levelPoint !== null && $levelPoint !== '') {
                return max(0, (int) $levelPoint);
            }
        }

        return max(0, (int) $this->point_value);
    }
}
