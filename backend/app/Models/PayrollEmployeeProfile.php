<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PayrollEmployeeProfile extends Model
{
    protected $table = 'payroll_employee_profiles';

    protected $fillable = [
        'institution_id',
        'employee_id',
        'base_salary',
        'payment_method',
        'bank_name',
        'bank_account',
        'effective_from',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'base_salary' => 'decimal:2',
            'effective_from' => 'date',
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

    public function components(): HasMany
    {
        return $this->hasMany(PayrollEmployeeComponent::class, 'employee_id', 'employee_id')
            ->whereColumn('payroll_employee_components.institution_id', 'payroll_employee_profiles.institution_id');
    }

    public function scopeForInstitution($query, int $institutionId)
    {
        return $query->where('institution_id', $institutionId);
    }
}
