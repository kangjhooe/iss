<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AchievementType extends Model
{
    use HasFactory;

    protected $table = 'achievement_types';

    protected $fillable = [
        'institution_id',
        'name',
        'code',
        'point_value',
        'category',
        'description',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
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
}
