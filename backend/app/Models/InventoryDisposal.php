<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class InventoryDisposal extends Model
{
    use Auditable, HasFactory, SoftDeletes;

    protected $table = 'inventory_disposal';

    protected $fillable = [
        'institution_id',
        'item_id',
        'asset_id',
        'transaction_id',
        'quantity',
        'disposal_date',
        'status',
        'disposal_reason',
        'disposal_document_number',
        'document_path',
        'document_name',
        'document_size',
        'document_mime',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'disposal_date' => 'date',
            'quantity' => 'integer',
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

    public function asset()
    {
        return $this->belongsTo(InventoryAsset::class, 'asset_id');
    }

    public function transaction()
    {
        return $this->belongsTo(InventoryTransaction::class, 'transaction_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
