<?php

namespace Tests\Feature;

use App\Models\Institution;
use App\Models\LibraryBook;
use App\Models\LibraryBookCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class LibraryBookImportTest extends TestCase
{
    use RefreshDatabase;

    protected Institution $institution;

    protected User $admin;

    protected LibraryBookCategory $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->institution = Institution::create([
            'name' => 'SMP Library Import',
            'npsn' => '50505050',
            'level' => 'SMP',
            'is_active' => true,
        ]);

        $this->admin = User::create([
            'name' => 'Admin Perpus',
            'email' => 'admin-library-import@example.com',
            'password' => Hash::make('Password123!'),
            'role' => 'institution_admin',
            'institution_id' => $this->institution->id,
            'email_verified_at' => now(),
            'is_active' => true,
        ]);

        $this->category = LibraryBookCategory::create([
            'institution_id' => $this->institution->id,
            'code' => 'FKS',
            'name' => 'Fiksi',
            'is_active' => true,
        ]);
    }

    public function test_import_accepts_numeric_isbn_and_year_from_excel_json(): void
    {
        Sanctum::actingAs($this->admin);

        $response = $this->withHeader('X-Institution-Id', (string) $this->institution->id)
            ->postJson('/api/v1/library/books/import', [
                'books' => [
                    [
                        // Nilai seperti hasil SheetJS dari sel angka Excel
                        'kode_kategori' => 'FKS',
                        'judul' => 'Buku Angka ISBN',
                        'isbn' => 9786020000000,
                        'pengarang' => 'Penulis',
                        'penerbit' => 'Penerbit',
                        'tahun' => 2024,
                        'halaman' => 120,
                        'jumlah_eksemplar' => 2,
                    ],
                ],
            ]);

        $response->assertOk()
            ->assertJsonPath('data.success', 1)
            ->assertJsonPath('data.failed', 0);

        $book = LibraryBook::query()->where('title', 'Buku Angka ISBN')->first();
        $this->assertNotNull($book);
        $this->assertSame('9786020000000', $book->isbn);
        $this->assertSame(2024, (int) $book->year);
        $this->assertSame(2, $book->copies()->count());
    }

    public function test_import_rejects_empty_payload_like_student_style(): void
    {
        Sanctum::actingAs($this->admin);

        $response = $this->withHeader('X-Institution-Id', (string) $this->institution->id)
            ->postJson('/api/v1/library/books/import', [
                'books' => [],
            ]);

        $response->assertStatus(400);
    }

    public function test_import_reports_unknown_category_per_row(): void
    {
        Sanctum::actingAs($this->admin);

        $response = $this->withHeader('X-Institution-Id', (string) $this->institution->id)
            ->postJson('/api/v1/library/books/import', [
                'books' => [
                    [
                        'kode_kategori' => 'XYZ',
                        'judul' => 'Judul Tanpa Kategori',
                    ],
                ],
            ]);

        $response->assertOk()
            ->assertJsonPath('data.success', 0)
            ->assertJsonPath('data.failed', 1);

        $this->assertStringContainsString('kode kategori', $response->json('data.errors.0'));
    }
}
