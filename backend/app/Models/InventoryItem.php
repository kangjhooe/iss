<?php

namespace App\Models;

use App\Support\InventoryCatalog;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class InventoryItem extends Model
{
    use HasFactory, SoftDeletes, Auditable;

    protected $table = 'inventory_item';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'institution_id',
        'category_id',
        'tracking_type',
        'identity_status',
        'code',
        'master_code',
        'legacy_code',
        'name',
        'brand',
        'model',
        'serial_number',
        'purchase_date',
        'purchase_price',
        'supplier',
        'acquisition_method',
        'funding_source',
        'ownership_type',
        'owner_name',
        'ownership_document_number',
        'ownership_date',
        'ownership_notes',
        'condition',
        'status',
        'quantity',
        'unit',
        'room_id',
        'building_id',
        'responsible_employee_id',
        'location_note',
        'warranty_expiry',
        'warranty_reminder_sent_at',
        'description',
        'disposed_at',
        'disposal_reason',
        'disposal_document_number',
        'image_path',
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
            'purchase_date' => 'date',
            'warranty_expiry' => 'date',
            'warranty_reminder_sent_at' => 'datetime',
            'disposed_at' => 'date',
        'purchase_price' => 'decimal:2',
        'additional_cost' => 'decimal:2',
        'book_value' => 'decimal:2',
        'ownership_date' => 'date',
        'quantity' => 'integer',
        ];
    }

    /**
     * Get the institution that owns the item.
     */
    public function institution()
    {
        return $this->belongsTo(Institution::class);
    }

    /**
     * Get the category of the item.
     */
    public function category()
    {
        return $this->belongsTo(InventoryCategory::class);
    }

    /**
     * Get the room where this item is located.
     */
    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    /**
     * Get the employee responsible for this item.
     */
    public function responsibleEmployee()
    {
        return $this->belongsTo(Employee::class, 'responsible_employee_id');
    }

    /**
     * Get the building where this item is located.
     */
    public function building()
    {
        return $this->belongsTo(Building::class);
    }

    /**
     * Get the transactions for this item.
     */
    public function transactions()
    {
        return $this->hasMany(InventoryTransaction::class, 'item_id');
    }

    /**
     * Get the maintenances for this item.
     */
    public function maintenances()
    {
        return $this->hasMany(InventoryMaintenance::class, 'item_id');
    }

    /**
     * Get the loans for this item.
     */
    public function loans()
    {
        return $this->hasMany(InventoryLoan::class, 'item_id');
    }

    public function assets()
    {
        return $this->hasMany(InventoryAsset::class, 'item_id');
    }

    public function activeAssets()
    {
        return $this->assets()->where('disposal_status', InventoryCatalog::DISPOSAL_ACTIVE)->whereNull('disposed_at');
    }

    public function isStockTracked(): bool
    {
        return ($this->tracking_type ?? InventoryCatalog::TRACKING_STOCK) === InventoryCatalog::TRACKING_STOCK;
    }

    public function isIndividualTracked(): bool
    {
        return ($this->tracking_type ?? InventoryCatalog::TRACKING_STOCK) === InventoryCatalog::TRACKING_INDIVIDUAL;
    }

    /**
     * Get the user who created this item.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated this item.
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
     * Scope a query to filter by category.
     */
    public function scopeByCategory($query, $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }

    /**
     * Scope a query to filter by status.
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope a query to filter by condition.
     */
    public function scopeByCondition($query, $condition)
    {
        return $query->where('condition', $condition);
    }

    /**
     * Get available quantity (total - loaned).
     */
    public function getAvailableQuantity(): int
    {
        if ($this->isIndividualTracked()) {
            return $this->activeAssets()
                ->where('status', 'Tersedia')
                ->whereDoesntHave('loans', fn ($q) => $q->whereIn('status', ['Dipinjam', 'Terlambat']))
                ->count();
        }

        $loaned = $this->loans()
            ->whereIn('status', ['Dipinjam', 'Terlambat'])
            ->sum('quantity');

        return max(0, $this->quantity - $loaned);
    }

    /**
     * Check if item is available for loan.
     */
    public function isAvailable(): bool
    {
        if ($this->isIndividualTracked()) {
            return $this->getAvailableQuantity() > 0;
        }

        return $this->status === 'Tersedia' && $this->getAvailableQuantity() > 0;
    }
}
