<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FinanceExpense extends Model
{
    protected $table = 'finance_expenses';

    public const CATEGORY_PAYROLL = 'payroll';
    public const CATEGORY_OTHER = 'other';

    public const SOURCE_AUTO = 'auto';
    public const SOURCE_MANUAL = 'manual';

    public const METHODS = ['cash', 'transfer', 'other'];

    protected $fillable = [
        'institution_id',
        'category',
        'title',
        'amount',
        'expense_date',
        'method',
        'reference',
        'notes',
        'source',
        'payroll_run_id',
        'recorded_by',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'expense_date' => 'datetime',
        ];
    }

    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class);
    }

    public function payrollRun(): BelongsTo
    {
        return $this->belongsTo(PayrollRun::class, 'payroll_run_id');
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function scopeForInstitution($query, int $institutionId)
    {
        return $query->where('institution_id', $institutionId);
    }
}
