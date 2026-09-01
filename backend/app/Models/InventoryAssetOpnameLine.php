<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryAssetOpnameLine extends Model
{
    protected $table = 'inventory_asset_opname_line';

    protected $fillable = [
        'opname_id',
        'asset_id',
        'book_status',
        'book_condition',
        'found',
        'counted_condition',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'found' => 'boolean',
        ];
    }

    public function opname()
    {
        return $this->belongsTo(InventoryStockOpname::class, 'opname_id');
    }

    public function asset()
    {
        return $this->belongsTo(InventoryAsset::class, 'asset_id');
    }
}
