<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FinanceInvoice extends Model
{
    protected $table = 'finance_invoices';

    public const STATUSES = ['unpaid', 'partial', 'paid', 'cancelled'];

    protected $fillable = [
        'institution_id',
        'fee_type_id',
        'student_id',
        'class_id',
        'academic_year_id',
        'title',
        'period_label',
        'amount',
        'amount_paid',
        'due_date',
        'status',
        'notes',
        'batch_key',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'amount_paid' => 'decimal:2',
            'due_date' => 'date',
        ];
    }

    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class);
    }

    public function feeType(): BelongsTo
    {
        return $this->belongsTo(FinanceFeeType::class, 'fee_type_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function schoolClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class, 'academic_year_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(FinancePayment::class, 'invoice_id');
    }

    public function scopeForInstitution($query, int $institutionId)
    {
        return $query->where('institution_id', $institutionId);
    }

    public function scopeOutstanding($query)
    {
        return $query->whereIn('status', ['unpaid', 'partial']);
    }

    public function getRemainingAttribute(): float
    {
        return max(0, (float) $this->amount - (float) $this->amount_paid);
    }

    public function refreshPaymentStatus(): void
    {
        if ($this->status === 'cancelled') {
            return;
        }

        $paid = (float) $this->payments()->sum('amount');
        $amount = (float) $this->amount;
        $status = 'unpaid';
        if ($paid <= 0) {
            $status = 'unpaid';
        } elseif ($paid + 0.0001 >= $amount) {
            $status = 'paid';
            $paid = $amount;
        } else {
            $status = 'partial';
        }

        $this->forceFill([
            'amount_paid' => $paid,
            'status' => $status,
        ])->save();
    }
}
