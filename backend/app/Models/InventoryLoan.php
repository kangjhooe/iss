<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\Auditable;

class InventoryLoan extends Model
{
    use HasFactory, SoftDeletes, Auditable;

    protected $table = 'inventory_loan';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'institution_id',
        'item_id',
        'borrower_type',
        'borrower_id',
        'borrower_name',
        'borrower_phone',
        'loan_date',
        'expected_return_date',
        'actual_return_date',
        'quantity',
            'purpose',
            'status',
            'notes',
            'return_condition',
            'return_item_status',
            'created_by',
            'updated_by',
        ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'loan_date' => 'date',
            'expected_return_date' => 'date',
            'actual_return_date' => 'date',
            'quantity' => 'integer',
        ];
    }

    /**
     * Get the institution that owns the loan.
     */
    public function institution()
    {
        return $this->belongsTo(Institution::class);
    }

    /**
     * Get the item for this loan.
     */
    public function item()
    {
        return $this->belongsTo(InventoryItem::class);
    }

    /**
     * Get the employee borrower (if borrower_type is Employee).
     */
    public function borrowerEmployee()
    {
        return $this->belongsTo(Employee::class, 'borrower_id');
    }

    /**
     * Get the student borrower (if borrower_type is Student).
     */
    public function borrowerStudent()
    {
        return $this->belongsTo(Student::class, 'borrower_id');
    }

    /**
     * Get the user who created this loan.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated this loan.
     */
    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Scope a query to filter by institution.
     */
    public function scopeForInstitution($query, $institutionId)
    {
        return $query->where('institution_id', $institutionId);
    }

    /**
     * Scope a query to filter by status.
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope a query to filter by borrower type.
     */
    public function scopeByBorrowerType($query, $type)
    {
        return $query->where('borrower_type', $type);
    }

    /**
     * Check if loan is overdue.
     */
    public function isOverdue(): bool
    {
        return $this->status === 'Dipinjam' && 
               $this->expected_return_date < now() && 
               $this->actual_return_date === null;
    }
}
