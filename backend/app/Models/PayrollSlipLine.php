<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayrollSlipLine extends Model
{
    protected $table = 'payroll_slip_lines';

    public const SOURCE_RULE = 'rule';
    public const SOURCE_ATTENDANCE = 'attendance';
    public const SOURCE_MANUAL = 'manual';
    public const SOURCE_AD_HOC = 'ad_hoc';
    public const SOURCE_STRUCTURAL = 'structural';
    public const SOURCE_THR = 'thr';

    protected $fillable = [
        'slip_id',
        'component_id',
        'label',
        'type',
        'amount',
        'is_manual_override',
        'source',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'is_manual_override' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function slip(): BelongsTo
    {
        return $this->belongsTo(PayrollSlip::class, 'slip_id');
    }

    public function component(): BelongsTo
    {
        return $this->belongsTo(PayrollComponent::class, 'component_id');
    }
}
