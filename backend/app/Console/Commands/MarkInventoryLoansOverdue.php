<?php

namespace App\Console\Commands;

use App\Models\InventoryLoan;
use App\Models\User;
use App\Notifications\InventoryNotification;
use App\Notifications\LabNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

class MarkInventoryLoansOverdue extends Command
{
    protected $signature = 'inventory:mark-loans-overdue';

    protected $description = 'Tandai peminjaman inventaris yang lewat jatuh tempo sebagai Terlambat dan kirim notifikasi';

    public function handle(): int
    {
        $loans = InventoryLoan::with([
            'item.room.responsibleEmployee.userAccount',
            'item.responsibleEmployee.userAccount',
            'asset.room.responsibleEmployee.userAccount',
            'borrowerEmployee.userAccount',
        ])
            ->where('status', 'Dipinjam')
            ->whereNull('actual_return_date')
            ->whereDate('expected_return_date', '<', now()->toDateString())
            ->get();

        $count = 0;
        $notified = 0;

        foreach ($loans as $loan) {
            $loan->update(['status' => 'Terlambat']);
            $count++;

            if ($loan->overdue_notified_at) {
                continue;
            }

            $itemName = $loan->item?->name ?? 'Barang inventaris';
            $borrower = $loan->borrower_name ?? 'Peminjam';
            $dueDate = $loan->expected_return_date?->format('d/m/Y') ?? '-';
            $message = "Peminjaman \"{$itemName}\" ({$borrower}) sudah melewati jatuh tempo ({$dueDate}).";

            $notifiedUsers = $this->notifyOverdueLoan($loan, $message);
            if ($notifiedUsers > 0) {
                $loan->update(['overdue_notified_at' => now()]);
                $notified += $notifiedUsers;
            }
        }

        $this->info("Ditandai {$count} peminjaman sebagai Terlambat. Notifikasi terkirim ke {$notified} penerima.");

        return self::SUCCESS;
    }

    protected function notifyOverdueLoan(InventoryLoan $loan, string $message): int
    {
        $recipients = collect();
        $room = $loan->asset?->room ?? $loan->item?->room;

        if ($room && $room->type === 'Laboratorium') {
            $pj = $room->responsibleEmployee?->userAccount;
            if ($pj) {
                $pj->notify(new LabNotification(
                    'loan_overdue',
                    $message,
                    ['room_id' => $room->id, 'loan_id' => $loan->id]
                ));
                $recipients->push($pj->id);
            }
        }

        $responsible = $loan->item?->responsibleEmployee?->userAccount;
        if ($responsible && ! $recipients->contains($responsible->id)) {
            $this->sendInventoryNotification($responsible, 'loan_overdue', $message, $loan);
            $recipients->push($responsible->id);
        }

        foreach ($this->inventoryManagers($loan->institution_id) as $user) {
            if (! $recipients->contains($user->id)) {
                $this->sendInventoryNotification($user, 'loan_overdue', $message, $loan);
                $recipients->push($user->id);
            }
        }

        return $recipients->count();
    }

    protected function sendInventoryNotification(User $user, string $action, string $message, InventoryLoan $loan): void
    {
        $user->notify(new InventoryNotification($action, $message, [
            'loan_id' => $loan->id,
            'item_id' => $loan->item_id,
            'item_name' => $loan->item?->name,
            'expected_return_date' => $loan->expected_return_date?->format('Y-m-d'),
        ]));
    }

    /**
     * @return Collection<int, User>
     */
    protected function inventoryManagers(int $institutionId): Collection
    {
        return User::query()
            ->where('institution_id', $institutionId)
            ->where('is_active', true)
            ->get()
            ->filter(fn (User $user) => $user->hasModuleAccess('inventory'))
            ->values();
    }
}
