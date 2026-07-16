<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TeacherPointReward extends Model
{
    use HasFactory;

    protected $table = 'teacher_point_rewards';

    protected $fillable = [
        'institution_id',
        'point_min',
        'point_max',
        'reward_name',
        'description',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'point_min' => 'integer',
            'point_max' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    public function institution()
    {
        return $this->belongsTo(Institution::class);
    }

    public function rewardLogs()
    {
        return $this->hasMany(TeacherRewardLog::class, 'teacher_point_reward_id');
    }

    public function scopeForInstitution($query, int $institutionId)
    {
        return $query->where('institution_id', $institutionId);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function containsPoint(int $totalPoint): bool
    {
        return $totalPoint >= $this->point_min && $totalPoint <= $this->point_max;
    }
}
