<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UksMedicineTransaction extends Model
{
    use HasFactory;

    public const TYPES = [
        'masuk' => 'Masuk',
        'keluar' => 'Keluar',
        'penyesuaian' => 'Penyesuaian',
    ];

    protected $table = 'uks_medicine_transactions';

    protected $fillable = [
        'institution_id',
        'uks_medicine_id',
        'type',
        'quantity',
        'transaction_date',
        'notes',
        'uks_visit_id',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'transaction_date' => 'date',
        ];
    }

    public function institution()
    {
        return $this->belongsTo(Institution::class);
    }

    public function medicine()
    {
        return $this->belongsTo(UksMedicine::class, 'uks_medicine_id');
    }

    public function visit()
    {
        return $this->belongsTo(UksVisit::class, 'uks_visit_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeForInstitution($query, int $institutionId)
    {
        return $query->where('institution_id', $institutionId);
    }
}
