<?php

namespace App\Support;

use Illuminate\Support\Facades\Log;
use Throwable;

class SafeNotify
{
    /**
     * Kirim notifikasi segera. Gagal kirim tidak boleh menggagalkan aksi utama
     * (mis. meluluskan PPDB) jika tabel antrian/email belum siap.
     */
    public static function send(mixed $notifiable, object $notification): void
    {
        try {
            $notifiable->notifyNow($notification);
        } catch (Throwable $e) {
            Log::warning('Gagal mengirim notifikasi', [
                'notification' => $notification::class,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
