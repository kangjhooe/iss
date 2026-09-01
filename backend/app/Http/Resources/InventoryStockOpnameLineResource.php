<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InventoryStockOpnameLineResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'opname_id' => $this->opname_id,
            'item_id' => $this->item_id,
            'book_quantity' => $this->book_quantity,
            'counted_quantity' => $this->counted_quantity,
            'variance' => $this->variance,
            'condition' => $this->condition,
            'notes' => $this->notes,
            'adjustment_transaction_id' => $this->adjustment_transaction_id,
            'item' => $this->whenLoaded('item', fn () => $this->item ? [
                'id' => $this->item->id,
                'code' => $this->item->code,
                'name' => $this->item->name,
                'unit' => $this->item->unit,
                'room' => $this->item->room ? ['id' => $this->item->room->id, 'name' => $this->item->room->name] : null,
            ] : null),
        ];
    }
}
