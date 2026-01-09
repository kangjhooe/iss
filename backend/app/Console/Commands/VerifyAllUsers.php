<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class VerifyAllUsers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'users:verify-all {--force : Force verification even in production}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Verify all unverified users (useful for development)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Safety check: only allow in local/development unless --force is used
        if (!in_array(config('app.env'), ['local', 'development']) && !$this->option('force')) {
            $this->error('This command can only be run in local/development environment.');
            $this->info('Use --force flag to override this safety check.');
            return 1;
        }

        $unverifiedUsers = User::whereNull('email_verified_at')->get();
        
        if ($unverifiedUsers->isEmpty()) {
            $this->info('All users are already verified.');
            return 0;
        }

        $this->info("Found {$unverifiedUsers->count()} unverified user(s).");

        if (!$this->confirm('Do you want to verify all unverified users?', true)) {
            $this->info('Operation cancelled.');
            return 0;
        }

        $verified = 0;
        foreach ($unverifiedUsers as $user) {
            $user->update(['email_verified_at' => now()]);
            $verified++;
            $this->line("✓ Verified: {$user->email} ({$user->name})");
        }

        $this->info("Successfully verified {$verified} user(s).");
        return 0;
    }
}
