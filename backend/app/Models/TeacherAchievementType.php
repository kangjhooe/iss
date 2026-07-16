<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TeacherAchievementType extends Model
{
    use HasFactory;

    protected $table = 'teacher_achievement_types';

    public const DEFAULT_MULTIPLIERS = [
        'sekolah' => 1,
        'kabupaten' => 1.5,
        'provinsi' => 2,
        'nasional' => 3,
        'internasional' => 4,
    ];

    public const CATEGORIES = [
        'akademik',
        'pengembangan',
        'pengabdian',
        'inovasi',
        'kedisiplinan',
    ];

    protected $fillable = [
        'institution_id',
        'name',
        'code',
        'point_value',
        'category',
        'level_multipliers',
        'description',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'level_multipliers' => 'array',
            'point_value' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    public function institution()
    {
        return $this->belongsTo(Institution::class);
    }

    public function achievements()
    {
        return $this->hasMany(TeacherAchievement::class, 'achievement_type_id');
    }

    public function scopeForInstitution($query, int $institutionId)
    {
        return $query->where('institution_id', $institutionId);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function getMultipliers(): array
    {
        return $this->level_multipliers ?: self::DEFAULT_MULTIPLIERS;
    }

    public function resolvePointValue(?string $level = null, ?int $override = null): int
    {
        if ($override !== null) {
            return max(0, $override);
        }

        $base = (int) $this->point_value;
        if (!$level) {
            return $base;
        }

        $multipliers = $this->getMultipliers();
        $factor = (float) ($multipliers[$level] ?? 1);

        return (int) max(0, round($base * $factor));
    }
}
