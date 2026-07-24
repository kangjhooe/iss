<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\DatabaseBackupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SuperAdminDatabaseBackupTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;

    protected User $schoolAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->superAdmin = User::create([
            'name' => 'Super Admin',
            'email' => 'super-backup@example.com',
            'password' => Hash::make('Password123!'),
            'role' => 'super_admin',
            'email_verified_at' => now(),
            'is_active' => true,
        ]);

        $this->schoolAdmin = User::create([
            'name' => 'Admin Sekolah',
            'email' => 'admin-backup@example.com',
            'password' => Hash::make('Password123!'),
            'role' => 'institution_admin',
            'email_verified_at' => now(),
            'is_active' => true,
        ]);
    }

    public function test_non_super_admin_cannot_list_backups(): void
    {
        Sanctum::actingAs($this->schoolAdmin);

        $this->getJson('/api/v1/super-admin/database-backups')
            ->assertStatus(403);
    }

    public function test_super_admin_can_list_create_download_and_delete_backup(): void
    {
        $filename = 'iss-db-20260724-110000.sql.gz';

        $this->mock(DatabaseBackupService::class, function ($mock) use ($filename) {
            $mock->shouldReceive('list')
                ->once()
                ->andReturn([
                    [
                        'filename' => $filename,
                        'size' => 128,
                        'created_at' => now()->toIso8601String(),
                    ],
                ]);

            $mock->shouldReceive('create')
                ->once()
                ->andReturn([
                    'filename' => $filename,
                    'size' => 128,
                    'created_at' => now()->toIso8601String(),
                    'path' => storage_path('app/private/backups/database/' . $filename),
                ]);

            $dir = storage_path('app/private/backups/database');
            if (! is_dir($dir)) {
                mkdir($dir, 0750, true);
            }
            $path = $dir . DIRECTORY_SEPARATOR . $filename;
            file_put_contents($path, 'fake-gz-content');

            $mock->shouldReceive('absolutePath')
                ->once()
                ->with($filename)
                ->andReturn($path);

            $mock->shouldReceive('delete')
                ->once()
                ->with($filename);
        });

        Sanctum::actingAs($this->superAdmin);

        $this->getJson('/api/v1/super-admin/database-backups')
            ->assertOk()
            ->assertJsonPath('data.0.filename', $filename);

        $this->postJson('/api/v1/super-admin/database-backups')
            ->assertCreated()
            ->assertJsonPath('data.filename', $filename);

        $this->get('/api/v1/super-admin/database-backups/' . $filename . '/download')
            ->assertOk()
            ->assertHeader('content-disposition');

        $this->deleteJson('/api/v1/super-admin/database-backups/' . $filename)
            ->assertOk()
            ->assertJsonPath('message', 'Backup berhasil dihapus.');
    }

    public function test_rejects_unsafe_filename(): void
    {
        Sanctum::actingAs($this->superAdmin);

        $this->get('/api/v1/super-admin/database-backups/../.env/download')
            ->assertStatus(404);

        $this->deleteJson('/api/v1/super-admin/database-backups/evil.sql')
            ->assertStatus(404);
    }
}
