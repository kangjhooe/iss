<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeStructuralPosition extends Model
{
    protected $table = 'employee_structural_positions';

    protected $fillable = [
        'institution_id',
        'employee_id',
        'structural_position_id',
        'employee_decree_id',
        'started_at',
        'ended_at',
        'decree_number',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'date',
            'ended_at' => 'date',
        ];
    }

    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function position(): BelongsTo
    {
        return $this->belongsTo(StructuralPosition::class, 'structural_position_id');
    }

    public function decree(): BelongsTo
    {
        return $this->belongsTo(EmployeeDecree::class, 'employee_decree_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeActive($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('ended_at')->orWhere('ended_at', '>=', now()->toDateString());
        });
    }

    public function getIsActiveAttribute(): bool
    {
        return $this->ended_at === null || $this->ended_at->gte(now()->startOfDay());
    }
}
