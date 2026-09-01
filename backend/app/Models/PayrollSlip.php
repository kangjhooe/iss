<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PayrollSlip extends Model
{
    protected $table = 'payroll_slips';

    public const STATUS_DRAFT = 'draft';
    public const STATUS_FINAL = 'final';
    public const STATUS_PAID = 'paid';

    protected $fillable = [
        'institution_id',
        'run_id',
        'period_id',
        'employee_id',
        'gross',
        'total_deductions',
        'net',
        'attendance_snapshot',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'gross' => 'decimal:2',
            'total_deductions' => 'decimal:2',
            'net' => 'decimal:2',
            'attendance_snapshot' => 'array',
        ];
    }

    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class);
    }

    public function run(): BelongsTo
    {
        return $this->belongsTo(PayrollRun::class, 'run_id');
    }

    public function period(): BelongsTo
    {
        return $this->belongsTo(PayrollPeriod::class, 'period_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(PayrollSlipLine::class, 'slip_id')->orderBy('sort_order');
    }

    public function scopeForInstitution($query, int $institutionId)
    {
        return $query->where('institution_id', $institutionId);
    }
}
