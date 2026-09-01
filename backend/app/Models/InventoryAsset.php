<?php

namespace App\Models;

use App\Support\InventoryCatalog;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class InventoryAsset extends Model
{
    use Auditable, HasFactory, SoftDeletes;

    protected $table = 'inventory_asset';

    protected $fillable = [
        'institution_id',
        'item_id',
        'asset_number',
        'inventory_number',
        'serial_number',
        'registration_number',
        'qr_token',
        'condition',
        'status',
        'disposal_status',
        'room_id',
        'building_id',
        'responsible_employee_id',
        'location_start_date',
        'responsible_start_date',
        'purchase_date',
        'purchase_price',
        'location_note',
        'description',
        'disposed_at',
        'disposal_reason',
        'disposal_document_number',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'location_start_date' => 'date',
            'responsible_start_date' => 'date',
            'purchase_date' => 'date',
            'disposed_at' => 'date',
            'purchase_price' => 'decimal:2',
        ];
    }

    public function institution()
    {
        return $this->belongsTo(Institution::class);
    }

    public function item()
    {
        return $this->belongsTo(InventoryItem::class, 'item_id');
    }

    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    public function building()
    {
        return $this->belongsTo(Building::class);
    }

    public function responsibleEmployee()
    {
        return $this->belongsTo(Employee::class, 'responsible_employee_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function loans()
    {
        return $this->hasMany(InventoryLoan::class, 'asset_id');
    }

    public function maintenances()
    {
        return $this->hasMany(InventoryMaintenance::class, 'asset_id');
    }

    public function movements()
    {
        return $this->hasMany(InventoryAssetMovement::class, 'asset_id');
    }

    public function scopeForInstitution($query, int $institutionId)
    {
        return $query->where('institution_id', $institutionId);
    }

    public function scopeActive($query)
    {
        return $query->where('disposal_status', InventoryCatalog::DISPOSAL_ACTIVE)
            ->whereNull('disposed_at');
    }

    public function isAvailableForLoan(): bool
    {
        if ($this->disposal_status !== InventoryCatalog::DISPOSAL_ACTIVE || $this->disposed_at) {
            return false;
        }

        if (! in_array($this->status, ['Tersedia'], true)) {
            return false;
        }

        return ! $this->loans()
            ->whereIn('status', ['Dipinjam', 'Terlambat'])
            ->exists();
    }
}
