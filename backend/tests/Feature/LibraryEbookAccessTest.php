<?php

namespace Tests\Feature;

use App\Models\Institution;
use App\Models\LibraryBook;
use App\Models\LibraryBookCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class LibraryEbookAccessTest extends TestCase
{
    use RefreshDatabase;

    protected Institution $institution;

    protected Institution $otherInstitution;

    protected User $admin;

    protected User $otherAdmin;

    protected LibraryBookCategory $category;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');

        $this->institution = Institution::create([
            'name' => 'SMP Ebook A',
            'npsn' => '80808080',
            'level' => 'SMP',
            'is_active' => true,
        ]);

        $this->otherInstitution = Institution::create([
            'name' => 'SMP Ebook B',
            'npsn' => '90909090',
            'level' => 'SMP',
            'is_active' => true,
        ]);

        $this->admin = User::create([
            'name' => 'Admin Ebook A',
            'email' => 'admin-ebook-a@example.com',
            'password' => Hash::make('Password123!'),
            'role' => 'institution_admin',
            'institution_id' => $this->institution->id,
            'email_verified_at' => now(),
            'is_active' => true,
        ]);

        $this->otherAdmin = User::create([
            'name' => 'Admin Ebook B',
            'email' => 'admin-ebook-b@example.com',
            'password' => Hash::make('Password123!'),
            'role' => 'institution_admin',
            'institution_id' => $this->otherInstitution->id,
            'email_verified_at' => now(),
            'is_active' => true,
        ]);

        $this->category = LibraryBookCategory::create([
            'institution_id' => $this->institution->id,
            'code' => 'DIG',
            'name' => 'Digital',
            'is_active' => true,
        ]);
    }

    private function putEbook(string $relativePath): string
    {
        Storage::disk('local')->put($relativePath, '%PDF-1.4 fake ebook content');

        return $relativePath;
    }

    private function makeBook(array $overrides = []): LibraryBook
    {
        $path = $overrides['ebook_path']
            ?? $this->putEbook('library/ebooks/' . $this->institution->id . '/sample.pdf');

        return LibraryBook::create(array_merge([
            'institution_id' => $this->institution->id,
            'category_id' => $this->category->id,
            'title' => 'Buku Digital',
            'author' => 'Penulis',
            'ebook_path' => $path,
            'is_public_ebook' => false,
            'ebook_view_count' => 0,
            'created_by' => $this->admin->id,
        ], $overrides));
    }

    public function test_auth_user_can_stream_own_institution_ebook_and_counts_view_once(): void
    {
        $book = $this->makeBook();

        Sanctum::actingAs($this->admin);

        $first = $this->get("/api/v1/library/books/{$book->id}/ebook");
        $first->assertOk();
        $this->assertStringContainsString('pdf', strtolower((string) $first->headers->get('content-type')));

        $book->refresh();
        $this->assertSame(1, (int) $book->ebook_view_count);

        $second = $this->get("/api/v1/library/books/{$book->id}/ebook");
        $second->assertOk();
        $book->refresh();
        $this->assertSame(1, (int) $book->ebook_view_count);
    }

    public function test_other_institution_cannot_stream_private_ebook(): void
    {
        $book = $this->makeBook(['is_public_ebook' => false]);

        Sanctum::actingAs($this->otherAdmin);

        $this->getJson("/api/v1/library/books/{$book->id}/ebook")
            ->assertForbidden()
            ->assertJsonPath('message', 'Akses ditolak.');
    }

    public function test_public_catalog_only_lists_public_ebooks(): void
    {
        $public = $this->makeBook([
            'title' => 'Ebook Publik',
            'is_public_ebook' => true,
            'ebook_path' => $this->putEbook('library/ebooks/' . $this->institution->id . '/public.pdf'),
        ]);
        $this->makeBook([
            'title' => 'Ebook Privat',
            'is_public_ebook' => false,
            'ebook_path' => $this->putEbook('library/ebooks/' . $this->institution->id . '/private.pdf'),
        ]);

        $list = $this->getJson('/api/v1/public/library/ebooks?npsn=' . $this->institution->npsn);
        $list->assertOk();

        $titles = collect($list->json('data'))->pluck('title')->all();
        $this->assertContains('Ebook Publik', $titles);
        $this->assertNotContains('Ebook Privat', $titles);
        $this->assertSame($public->id, collect($list->json('data'))->firstWhere('title', 'Ebook Publik')['id'] ?? null);
    }

    public function test_public_viewer_issues_token_and_stream_works(): void
    {
        $book = $this->makeBook([
            'is_public_ebook' => true,
            'ebook_path' => $this->putEbook('library/ebooks/' . $this->institution->id . '/open.pdf'),
        ]);

        $viewer = $this->getJson(
            "/api/v1/public/library/books/{$book->id}/viewer?npsn=" . $this->institution->npsn
        );
        $viewer->assertOk()
            ->assertJsonStructure(['data' => ['stream_path', 'expires_at', 'watermark']]);

        $book->refresh();
        $this->assertSame(1, (int) $book->ebook_view_count);

        $streamPath = $viewer->json('data.stream_path');
        $this->assertNotEmpty($streamPath);

        $stream = $this->get($streamPath);
        $stream->assertOk();

        // Stream itself must not increment again (viewer already recorded).
        $book->refresh();
        $this->assertSame(1, (int) $book->ebook_view_count);
    }

    public function test_public_stream_without_token_is_forbidden(): void
    {
        $book = $this->makeBook([
            'is_public_ebook' => true,
            'ebook_path' => $this->putEbook('library/ebooks/' . $this->institution->id . '/token.pdf'),
        ]);

        $this->getJson(
            "/api/v1/public/library/books/{$book->id}/ebook?npsn=" . $this->institution->npsn
        )->assertForbidden();
    }

    public function test_non_public_ebook_cannot_be_opened_via_public_viewer(): void
    {
        $book = $this->makeBook(['is_public_ebook' => false]);

        $this->getJson(
            "/api/v1/public/library/books/{$book->id}/viewer?npsn=" . $this->institution->npsn
        )->assertNotFound();
    }
}
