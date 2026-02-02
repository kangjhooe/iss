<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PointThreshold extends Model
{
    use HasFactory;

    protected $table = 'point_thresholds';

    protected $fillable = [
        'institution_id',
        'point_min',
        'point_max',
        'action_name',
        'description',
        'sort_order',
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

    /**
     * Get the student action logs that used this threshold.
     */
    public function studentActionLogs()
    {
        return $this->hasMany(StudentActionLog::class, 'point_threshold_id');
    }

    public function scopeForInstitution($query, int $institutionId)
    {
        return $query->where('institution_id', $institutionId);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /** Check if given total point falls within this threshold (point_min <= total <= point_max). */
    public function containsPoint(int $totalPoint): bool
    {
        return $totalPoint >= $this->point_min && $totalPoint <= $this->point_max;
    }
}
