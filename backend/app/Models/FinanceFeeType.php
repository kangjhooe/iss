<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FinanceFeeType extends Model
{
    protected $table = 'finance_fee_types';

    public const FREQUENCIES = ['monthly', 'yearly', 'one_time', 'as_needed'];
    public const SCOPES = ['school', 'class', 'student'];

    protected $fillable = [
        'institution_id',
        'code',
        'name',
        'description',
        'frequency',
        'scope',
        'default_amount',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'default_amount' => 'decimal:2',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(FinanceInvoice::class, 'fee_type_id');
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
