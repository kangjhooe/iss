<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\Auditable;

class InventoryTransaction extends Model
{
    use HasFactory, SoftDeletes, Auditable;

    protected $table = 'inventory_transaction';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'institution_id',
        'item_id',
        'transaction_type',
        'transaction_date',
        'quantity',
        'reference_number',
        'from_location_id',
        'to_location_id',
        'notes',
        'created_by',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'transaction_date' => 'date',
            'quantity' => 'integer',
        ];
    }

    /**
     * Get the institution that owns the transaction.
     */
    public function institution()
    {
        return $this->belongsTo(Institution::class);
    }

    /**
     * Get the item for this transaction.
     */
    public function item()
    {
        return $this->belongsTo(InventoryItem::class);
    }

    /**
     * Get the room where item is moved from (for mutasi).
     */
    public function fromLocation()
    {
        return $this->belongsTo(Room::class, 'from_location_id');
    }

    /**
     * Get the room where item is moved to (for mutasi).
     */
    public function toLocation()
    {
        return $this->belongsTo(Room::class, 'to_location_id');
    }

    /**
     * Get the user who created this transaction.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Scope a query to filter by institution.
     */
    public function scopeForInstitution($query, $institutionId)
    {
        return $query->where('institution_id', $institutionId);
    }

    /**
     * Scope a query to filter by transaction type.
     */
    public function scopeByType($query, $type)
    {
        return $query->where('transaction_type', $type);
    }
}
