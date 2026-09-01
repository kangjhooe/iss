<?php

namespace App\Console\Commands;

use App\Models\InventoryItem;
use App\Models\InventoryLoan;
use App\Models\User;
use App\Notifications\InventoryNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

class SendInventoryLoanReminders extends Command
{
    protected $signature = 'inventory:send-loan-reminders';

    protected $description = 'Kirim pengingat peminjaman inventaris yang jatuh tempo besok';

    public function handle(): int
    {
        $tomorrow = now()->addDay()->toDateString();

        $loans = InventoryLoan::with([
            'item.responsibleEmployee.userAccount',
            'borrowerEmployee.userAccount',
        ])
            ->where('status', 'Dipinjam')
            ->whereNull('actual_return_date')
            ->whereDate('expected_return_date', $tomorrow)
            ->whereNull('due_reminder_sent_at')
            ->get();

        $sent = 0;

        foreach ($loans as $loan) {
            $itemName = $loan->item?->name ?? 'Barang inventaris';
            $borrower = $loan->borrower_name ?? 'Peminjam';
            $message = "Pengingat: peminjaman \"{$itemName}\" ({$borrower}) jatuh tempo besok.";

            $count = $this->notifyLoanReminder($loan, $message);
            if ($count > 0) {
                $loan->update(['due_reminder_sent_at' => now()]);
                $sent += $count;
            }
        }

        $this->info("Pengingat jatuh tempo besok terkirim ke {$sent} penerima ({$loans->count()} peminjaman).");

        return self::SUCCESS;
    }

    protected function notifyLoanReminder(InventoryLoan $loan, string $message): int
    {
        $recipients = collect();
        $extra = [
            'loan_id' => $loan->id,
            'item_id' => $loan->item_id,
            'item_name' => $loan->item?->name,
            'expected_return_date' => $loan->expected_return_date?->format('Y-m-d'),
        ];

        $borrowerUser = $loan->borrowerEmployee?->userAccount;
        if ($borrowerUser) {
            $borrowerUser->notify(new InventoryNotification('loan_due_reminder', $message, $extra));
            $recipients->push($borrowerUser->id);
        }

        $responsible = $loan->item?->responsibleEmployee?->userAccount;
        if ($responsible && ! $recipients->contains($responsible->id)) {
            $responsible->notify(new InventoryNotification('loan_due_reminder', $message, $extra));
            $recipients->push($responsible->id);
        }

        return $recipients->count();
    }
}
