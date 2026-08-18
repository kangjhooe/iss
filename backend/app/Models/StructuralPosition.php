<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StructuralPosition extends Model
{
    protected $table = 'structural_positions';

    protected $fillable = [
        'key',
        'label',
        'description',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(EmployeeStructuralPosition::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
