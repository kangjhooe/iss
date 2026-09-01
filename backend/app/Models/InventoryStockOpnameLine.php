<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryStockOpnameLine extends Model
{
    protected $table = 'inventory_stock_opname_line';

    protected $fillable = [
        'opname_id',
        'item_id',
        'book_quantity',
        'counted_quantity',
        'variance',
        'condition',
        'notes',
        'adjustment_transaction_id',
    ];

    protected function casts(): array
    {
        return [
            'book_quantity' => 'integer',
            'counted_quantity' => 'integer',
            'variance' => 'integer',
        ];
    }

    public function opname()
    {
        return $this->belongsTo(InventoryStockOpname::class, 'opname_id');
    }

    public function item()
    {
        return $this->belongsTo(InventoryItem::class, 'item_id');
    }

    public function adjustmentTransaction()
    {
        return $this->belongsTo(InventoryTransaction::class, 'adjustment_transaction_id');
    }
}
