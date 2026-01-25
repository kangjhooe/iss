<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Cek apakah super admin sudah ada
        $existingSuperAdmin = User::where('email', 'superadmin@iss.id')->first();
        
        if (!$existingSuperAdmin) {
            User::create([
                'institution_id' => null,
                'name' => 'Super Admin',
                'email' => 'superadmin@iss.id',
                'password' => Hash::make('admin123'),
                'role' => 'super_admin',
                'email_verified_at' => now(),
            ]);
            
            $this->command->info('Super Admin berhasil dibuat!');
            $this->command->info('Email: superadmin@iss.id');
            $this->command->info('Password: admin123');
        } else {
            $existingSuperAdmin->update([
                'password' => Hash::make('admin123'),
                'role' => 'super_admin',
                'email_verified_at' => $existingSuperAdmin->email_verified_at ?? now(),
                'failed_login_attempts' => 0,
                'locked_until' => null,
            ]);

            $this->command->warn('Super Admin sudah ada. Password direset ke default.');
            $this->command->info('Email: superadmin@iss.id');
            $this->command->info('Password: admin123');
        }
    }
}
