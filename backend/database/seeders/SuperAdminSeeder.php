<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class SuperAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $email = trim((string) config('super_admin.email'));
        $name = trim((string) config('super_admin.name')) ?: 'Super Admin';
        $password = (string) config('super_admin.password');
        $resetPassword = (bool) config('super_admin.reset_password');

        if ($email === '') {
            $this->command->error('SUPER_ADMIN_EMAIL belum di-set di .env.');

            return;
        }

        $existing = User::where('email', $email)->first();

        if (! $existing) {
            if ($password === '') {
                $this->command->error('SUPER_ADMIN_PASSWORD wajib di-set di .env untuk membuat Super Admin.');

                return;
            }

            User::create([
                'institution_id' => null,
                'name' => $name,
                'email' => $email,
                'password' => $password,
                'role' => 'super_admin',
                'email_verified_at' => now(),
            ]);

            $this->command->info("Super Admin berhasil dibuat ({$email}).");

            return;
        }

        if ($existing->role !== 'super_admin') {
            $this->command->warn("User {$email} sudah ada dengan role {$existing->role}. Tidak diubah.");

            return;
        }

        $updates = [];

        if ($existing->name !== $name) {
            $updates['name'] = $name;
        }

        if (! $existing->email_verified_at) {
            $updates['email_verified_at'] = now();
        }

        if ($resetPassword) {
            if ($password === '') {
                $this->command->error('SUPER_ADMIN_RESET_PASSWORD=true membutuhkan SUPER_ADMIN_PASSWORD di .env.');

                return;
            }

            $updates['password'] = $password;
            $updates['failed_login_attempts'] = 0;
            $updates['locked_until'] = null;
        }

        if ($updates !== []) {
            $existing->forceFill($updates)->save();
        }

        if ($resetPassword) {
            $this->command->warn("Password Super Admin ({$email}) direset dari SUPER_ADMIN_PASSWORD.");
        } else {
            $this->command->info("Super Admin sudah ada ({$email}). Password tidak diubah.");
        }
    }
}
