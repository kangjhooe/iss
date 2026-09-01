<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class InventoryStockOpname extends Model
{
    use Auditable, SoftDeletes;

    protected $table = 'inventory_stock_opname';

    protected $fillable = [
        'institution_id',
        'opname_number',
        'opname_date',
        'room_id',
        'building_id',
        'status',
        'opname_type',
        'notes',
        'finalized_at',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'opname_date' => 'date',
            'finalized_at' => 'datetime',
        ];
    }

    public function institution()
    {
        return $this->belongsTo(Institution::class);
    }

    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    public function building()
    {
        return $this->belongsTo(Building::class);
    }

    public function lines()
    {
        return $this->hasMany(InventoryStockOpnameLine::class, 'opname_id');
    }

    public function assetLines()
    {
        return $this->hasMany(InventoryAssetOpnameLine::class, 'opname_id');
    }

    public function isAssetOpname(): bool
    {
        return $this->opname_type === 'asset';
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function isEditable(): bool
    {
        return in_array($this->status, ['draft', 'in_progress'], true);
    }
}
