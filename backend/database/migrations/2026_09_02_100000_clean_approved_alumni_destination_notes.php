<?php

use App\Models\AlumniDestination;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        AlumniDestination::query()
            ->where(function ($q) {
                $q->where('status', AlumniDestination::STATUS_APPROVED)
                    ->orWhereNull('status');
            })
            ->whereNot('status', AlumniDestination::STATUS_PENDING)
            ->whereNot('status', AlumniDestination::STATUS_REJECTED)
            ->whereNotNull('notes')
            ->where('notes', 'like', '%Menunggu persetujuan admin%')
            ->orderBy('id')
            ->chunkById(100, function ($destinations) {
                foreach ($destinations as $destination) {
                    $cleaned = $destination->cleanApprovedNotes();
                    $destination->update(['notes' => $cleaned]);
                }
            });
    }

    public function down(): void
    {
        // Data cleanup is not reversible.
    }
};
