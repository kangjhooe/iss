<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('inventory_disposal') || !Schema::hasTable('inventory_transaction')) {
            return;
        }

        $existingTxIds = DB::table('inventory_disposal')->whereNotNull('transaction_id')->pluck('transaction_id')->all();

        $transactions = DB::table('inventory_transaction')
            ->where('transaction_type', 'Keluar')
            ->where('notes', 'like', 'Penghapusan:%')
            ->when(count($existingTxIds) > 0, fn ($q) => $q->whereNotIn('id', $existingTxIds))
            ->orderBy('id')
            ->get();

        foreach ($transactions as $tx) {
            $item = DB::table('inventory_item')->where('id', $tx->item_id)->first();
            if (!$item) {
                continue;
            }

            $reason = trim(str_replace('Penghapusan:', '', (string) $tx->notes)) ?: 'Penghapusan';

            DB::table('inventory_disposal')->insert([
                'institution_id' => $tx->institution_id,
                'item_id' => $tx->item_id,
                'transaction_id' => $tx->id,
                'quantity' => max(1, (int) $tx->quantity),
                'disposal_date' => $tx->transaction_date,
                'status' => in_array($item->status, ['Dijual', 'Hilang', 'Rusak'], true) ? $item->status : 'Rusak',
                'disposal_reason' => $reason,
                'disposal_document_number' => $tx->reference_number,
                'created_by' => $tx->created_by,
                'updated_by' => $tx->created_by,
                'created_at' => $tx->created_at ?? now(),
                'updated_at' => $tx->updated_at ?? now(),
            ]);
        }
    }

    public function down(): void
    {
        // no-op
    }
};
