<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class PayrollRun extends Model
{
    protected $table = 'payroll_runs';

    public const STATUS_DRAFT = 'draft';
    public const STATUS_FINALIZED = 'finalized';
    public const STATUS_PAID = 'paid';

    protected $fillable = [
        'institution_id',
        'period_id',
        'batch_key',
        'label',
        'status',
        'employee_filter',
        'generated_at',
        'finalized_at',
        'paid_at',
        'created_by',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'employee_filter' => 'array',
            'generated_at' => 'datetime',
            'finalized_at' => 'datetime',
            'paid_at' => 'datetime',
        ];
    }

    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class);
    }

    public function period(): BelongsTo
    {
        return $this->belongsTo(PayrollPeriod::class, 'period_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function slips(): HasMany
    {
        return $this->hasMany(PayrollSlip::class, 'run_id');
    }

    public function financeExpense(): HasOne
    {
        return $this->hasOne(FinanceExpense::class, 'payroll_run_id');
    }

    public function scopeForInstitution($query, int $institutionId)
    {
        return $query->where('institution_id', $institutionId);
    }

    public function isEditable(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }
}
