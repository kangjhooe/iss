<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TeacherViolationType extends Model
{
    use HasFactory;

    protected $table = 'teacher_violation_types';

    public const CATEGORIES = [
        'kehadiran',
        'kedisiplinan',
        'administrasi',
        'lainnya',
    ];

    protected $fillable = [
        'institution_id',
        'name',
        'code',
        'point_weight',
        'category',
        'default_sanction',
        'description',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'point_weight' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    public function institution()
    {
        return $this->belongsTo(Institution::class);
    }

    public function violations()
    {
        return $this->hasMany(TeacherViolation::class, 'violation_type_id');
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
