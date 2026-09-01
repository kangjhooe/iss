<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_disposal', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institution_id')->constrained('institution')->cascadeOnDelete();
            $table->foreignId('item_id')->constrained('inventory_item')->cascadeOnDelete();
            $table->foreignId('transaction_id')->nullable()->constrained('inventory_transaction')->nullOnDelete();
            $table->unsignedInteger('quantity');
            $table->date('disposal_date');
            $table->enum('status', ['Dijual', 'Hilang', 'Rusak']);
            $table->text('disposal_reason');
            $table->string('disposal_document_number', 100)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('user')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('user')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('institution_id');
            $table->index('item_id');
            $table->index('disposal_date');
        });

        // Backfill dari item yang sudah punya disposed_at (penghapusan penuh sebelumnya)
        if (Schema::hasTable('inventory_item') && Schema::hasColumn('inventory_item', 'disposed_at')) {
            $items = DB::table('inventory_item')
                ->whereNotNull('disposed_at')
                ->get(['id', 'institution_id', 'disposal_reason', 'disposal_document_number', 'disposed_at', 'status', 'created_by']);

            foreach ($items as $item) {
                $qty = 1;
                $txId = DB::table('inventory_transaction')
                    ->where('item_id', $item->id)
                    ->where('transaction_type', 'Keluar')
                    ->where('notes', 'like', 'Penghapusan:%')
                    ->orderByDesc('id')
                    ->value('id');

                if ($txId) {
                    $qty = (int) (DB::table('inventory_transaction')->where('id', $txId)->value('quantity') ?? 1);
                }

                DB::table('inventory_disposal')->insert([
                    'institution_id' => $item->institution_id,
                    'item_id' => $item->id,
                    'transaction_id' => $txId,
                    'quantity' => max(1, $qty),
                    'disposal_date' => $item->disposed_at,
                    'status' => in_array($item->status, ['Dijual', 'Hilang', 'Rusak'], true) ? $item->status : 'Rusak',
                    'disposal_reason' => $item->disposal_reason ?? 'Penghapusan (data lama)',
                    'disposal_document_number' => $item->disposal_document_number,
                    'created_by' => $item->created_by,
                    'updated_by' => $item->created_by,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_disposal');
    }
};
