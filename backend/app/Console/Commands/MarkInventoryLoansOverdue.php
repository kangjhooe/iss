<?php

namespace App\Console\Commands;

use App\Models\InventoryLoan;
use Illuminate\Console\Command;

class MarkInventoryLoansOverdue extends Command
{
    protected $signature = 'inventory:mark-loans-overdue';

    protected $description = 'Tandai peminjaman inventaris yang lewat jatuh tempo sebagai Terlambat';

    public function handle(): int
    {
        $loans = InventoryLoan::with(['item.room.responsibleEmployee'])
            ->where('status', 'Dipinjam')
            ->whereNull('actual_return_date')
            ->whereDate('expected_return_date', '<', now()->toDateString())
            ->get();

        $count = 0;
        foreach ($loans as $loan) {
            $loan->update(['status' => 'Terlambat']);
            $count++;

            $room = $loan->item?->room;
            if ($room && $room->type === 'Laboratorium') {
                $pj = $room->responsibleEmployee?->userAccount;
                if ($pj) {
                    $pj->notify(new \App\Notifications\LabNotification(
                        'loan_overdue',
                        "Peminjaman alat lab {$room->name} ({$loan->item?->name}) sudah terlambat.",
                        ['room_id' => $room->id, 'loan_id' => $loan->id]
                    ));
                }
            }
        }

        $this->info("Ditandai {$count} peminjaman sebagai Terlambat.");

        return self::SUCCESS;
    }
}
