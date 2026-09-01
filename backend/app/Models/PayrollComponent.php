<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PayrollComponent extends Model
{
    protected $table = 'payroll_components';

    public const TYPE_EARNING = 'earning';
    public const TYPE_DEDUCTION = 'deduction';

    public const CALC_FIXED = 'fixed';
    public const CALC_PER_ALPHA_DAY = 'per_alpha_day';
    public const CALC_MANUAL = 'manual';
    public const CALC_STRUCTURAL_POSITION = 'structural_position';
    public const CALC_THR = 'thr';

    public const CODE_BASE_SALARY = 'GAJI_POKOK';
    public const CODE_TRANSPORT = 'TUNJ_TRANSPORT';
    public const CODE_STRUCTURAL = 'TUNJ_JABATAN';
    public const CODE_THR = 'THR';
    public const CODE_ALPHA = 'POT_ALPHA';

    protected $fillable = [
        'institution_id',
        'code',
        'name',
        'description',
        'type',
        'calc_mode',
        'default_amount',
        'is_system',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'default_amount' => 'decimal:2',
            'is_system' => 'boolean',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class);
    }

    public function employeeComponents(): HasMany
    {
        return $this->hasMany(PayrollEmployeeComponent::class, 'component_id');
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
