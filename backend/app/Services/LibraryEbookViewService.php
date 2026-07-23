<?php

namespace App\Services;

use App\Models\LibraryBook;
use App\Models\LibraryEbookView;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LibraryEbookViewService
{
    public const DEDUP_MINUTES = 30;

    /**
     * Catat pembukaan ebook. Dedup: 1 buku + 1 visitor dalam 30 menit = 1 hitungan.
     *
     * @return bool true jika tercatat (bukan duplikat)
     */
    public function recordView(LibraryBook $book, Request $request, string $source): bool
    {
        $source = in_array($source, ['student', 'staff', 'public'], true) ? $source : 'public';
        $visitorKey = $this->visitorKey($book, $request);

        $recent = LibraryEbookView::query()
            ->where('book_id', $book->id)
            ->where('visitor_key', $visitorKey)
            ->where('viewed_at', '>=', now()->subMinutes(self::DEDUP_MINUTES))
            ->exists();

        if ($recent) {
            return false;
        }

        DB::transaction(function () use ($book, $request, $source, $visitorKey) {
            LibraryEbookView::create([
                'institution_id' => $book->institution_id,
                'book_id' => $book->id,
                'user_id' => $request->user()?->id,
                'source' => $source,
                'visitor_key' => $visitorKey,
                'ip_address' => $request->ip(),
                'user_agent' => substr((string) $request->userAgent(), 0, 512) ?: null,
                'viewed_at' => now(),
            ]);

            $book->increment('ebook_view_count');
        });

        return true;
    }

    private function visitorKey(LibraryBook $book, Request $request): string
    {
        $user = $request->user();
        if ($user) {
            return 'u:' . $user->id;
        }

        $raw = implode('|', [
            (string) $request->ip(),
            (string) $request->userAgent(),
            (string) $book->id,
        ]);

        return 'a:' . hash('sha256', $raw);
    }
}
