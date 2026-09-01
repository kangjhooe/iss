<?php

namespace App\Console\Commands;

use App\Models\InventoryItem;
use App\Models\User;
use App\Notifications\InventoryNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

class SendInventoryWarrantyReminders extends Command
{
    protected $signature = 'inventory:send-warranty-reminders';

    protected $description = 'Kirim notifikasi garansi barang inventaris yang akan habis (30 hari ke depan)';

    public function handle(): int
    {
        $today = now()->toDateString();
        $horizon = now()->addDays(30)->toDateString();

        $items = InventoryItem::with(['responsibleEmployee.userAccount'])
            ->whereNull('disposed_at')
            ->whereNotNull('warranty_expiry')
            ->whereDate('warranty_expiry', '>=', $today)
            ->whereDate('warranty_expiry', '<=', $horizon)
            ->whereNull('warranty_reminder_sent_at')
            ->get();

        $sent = 0;

        foreach ($items as $item) {
            $expiry = $item->warranty_expiry?->format('d/m/Y') ?? '-';
            $message = "Garansi \"{$item->name}\" ({$item->code}) akan habis pada {$expiry}.";

            $count = $this->notifyWarrantyExpiring($item, $message);
            if ($count > 0) {
                $item->update(['warranty_reminder_sent_at' => now()]);
                $sent += $count;
            }
        }

        $this->info("Notifikasi garansi terkirim ke {$sent} penerima ({$items->count()} barang).");

        return self::SUCCESS;
    }

    protected function notifyWarrantyExpiring(InventoryItem $item, string $message): int
    {
        $recipients = collect();
        $extra = [
            'item_id' => $item->id,
            'item_code' => $item->code,
            'item_name' => $item->name,
            'warranty_expiry' => $item->warranty_expiry?->format('Y-m-d'),
        ];

        $responsible = $item->responsibleEmployee?->userAccount;
        if ($responsible) {
            $responsible->notify(new InventoryNotification('warranty_expiring', $message, $extra));
            $recipients->push($responsible->id);
        }

        foreach ($this->inventoryManagers((int) $item->institution_id) as $user) {
            if (! $recipients->contains($user->id)) {
                $user->notify(new InventoryNotification('warranty_expiring', $message, $extra));
                $recipients->push($user->id);
            }
        }

        return $recipients->count();
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
