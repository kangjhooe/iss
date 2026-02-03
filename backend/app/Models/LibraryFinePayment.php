<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LibraryFinePayment extends Model
{
    use HasFactory;

    protected $table = 'library_fine_payments';

    protected $fillable = [
        'loan_id',
        'amount',
        'paid_at',
        'payment_method',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'paid_at' => 'datetime',
        ];
    }

    public function loan()
    {
        return $this->belongsTo(LibraryLoan::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
