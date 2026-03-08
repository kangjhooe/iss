<?php

namespace App\Console\Commands;

use App\Models\Institution;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class DeleteUsersFromDeletedInstitutions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'users:delete-from-deleted-institutions
                            {--email= : Hapus hanya user dengan email ini (opsional)}
                            {--dry-run : Tampilkan saja, jangan hapus}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Hapus user yang institusinya sudah dihapus (soft-deleted), agar email bisa dipakai daftar lagi';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $email = $this->option('email');
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->warn('Mode dry-run: tidak ada data yang dihapus.');
        }

        // User yang institusinya sudah dihapus (hanya yang soft-deleted)
        $query = User::query()
            ->whereNotNull('institution_id')
            ->whereHas('institution', function ($q) {
                $q->onlyTrashed();
            });

        if ($email !== null && $email !== '') {
            $query->where('email', $email);
        }

        $users = $query->get();

        if ($users->isEmpty()) {
            $this->info('Tidak ada user yang institusinya sudah dihapus.');
            if ($email) {
                $this->line("Untuk email: {$email}");
            }
            return 0;
        }

        $this->table(
            ['ID', 'Email', 'Nama', 'Institution ID (deleted)'],
            $users->map(fn (User $u) => [$u->id, $u->email, $u->name, $u->institution_id])
        );

        $count = $users->count();
        if (!$dryRun && !$this->confirm("Hapus {$count} user di atas? Token login mereka juga akan dihapus.", true)) {
            $this->info('Dibatalkan.');
            return 0;
        }

        if ($dryRun) {
            $this->info("Dry-run: {$count} user akan dihapus jika dijalankan tanpa --dry-run.");
            return 0;
        }

        $deleted = 0;
        foreach ($users as $user) {
            try {
                DB::transaction(function () use ($user) {
                    // Hapus token Sanctum
                    DB::table('personal_access_tokens')
                        ->where('tokenable_type', 'App\\Models\\User')
                        ->where('tokenable_id', $user->id)
                        ->delete();
                    $user->delete();
                });
                $deleted++;
                $this->line("Dihapus: {$user->email} (id: {$user->id})");
            } catch (\Throwable $e) {
                $this->error("Gagal hapus user {$user->email}: " . $e->getMessage());
            }
        }

        $this->info("Selesai. {$deleted} user dihapus.");
        return 0;
    }
}
