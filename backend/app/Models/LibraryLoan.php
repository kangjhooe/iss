<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LibraryLoan extends Model
{
    use HasFactory;

    protected $table = 'library_loans';

    protected $fillable = [
        'institution_id',
        'copy_id',
        'borrower_type',
        'borrower_id',
        'borrower_name',
        'borrower_identifier',
        'loan_date',
        'due_date',
        'returned_at',
        'status',
        'fine_amount',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'loan_date' => 'date',
            'due_date' => 'date',
            'returned_at' => 'datetime',
            'fine_amount' => 'decimal:2',
        ];
    }

    public function institution()
    {
        return $this->belongsTo(Institution::class);
    }

    public function copy()
    {
        return $this->belongsTo(LibraryBookCopy::class, 'copy_id');
    }

    public function borrowerStudent()
    {
        return $this->belongsTo(Student::class, 'borrower_id');
    }

    public function borrowerEmployee()
    {
        return $this->belongsTo(Employee::class, 'borrower_id');
    }

    public function finePayments()
    {
        return $this->hasMany(LibraryFinePayment::class, 'loan_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeForInstitution($query, $institutionId)
    {
        return $query->where('institution_id', $institutionId);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function getPaidAmount(): float
    {
        return (float) $this->finePayments->sum('amount');
    }

    public function getRemainingFine(): float
    {
        return max(0, (float) $this->fine_amount - $this->getPaidAmount());
    }

    public function isOverdue(): bool
    {
        return in_array($this->status, ['Dipinjam', 'Terlambat'], true)
            && $this->due_date->isPast()
            && !$this->returned_at;
    }
}
