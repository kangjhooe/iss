<?php

namespace App\Services;

use App\Models\Institution;
use App\Models\LibraryBook;
use App\Models\LibraryBookCopy;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class LibraryInventoryNumberService
{
    /**
     * Format: {NPSN}/{YYYY}/{MM}/{KODE}/{KELAS}/{SEQ}
     * Contoh: 10816663/2026/09/SKI/8/001
     */
    public function buildPrefix(LibraryBook $book, ?Carbon $acquiredAt = null): string
    {
        $book->loadMissing(['category', 'institution']);

        $institution = $book->institution ?? Institution::find($book->institution_id);
        $npsn = preg_replace('/\D+/', '', (string) ($institution?->npsn ?? '')) ?: '00000000';

        $date = $acquiredAt
            ?? ($book->acquired_at ? Carbon::parse($book->acquired_at) : now());

        $categoryCode = strtoupper(trim((string) ($book->category?->code ?? 'UNK')));
        $categoryCode = $categoryCode !== '' ? $categoryCode : 'UNK';

        $grade = trim((string) ($book->grade ?? ''));
        $grade = $grade !== '' ? $grade : 'U';

        return sprintf(
            '%s/%s/%s/%s/%s',
            $npsn,
            $date->format('Y'),
            $date->format('m'),
            $categoryCode,
            $grade
        );
    }

    public function nextSequence(int $institutionId, string $prefix): int
    {
        $escaped = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $prefix);
        $pattern = $escaped . '/%';

        $maxSeq = LibraryBookCopy::query()
            ->whereHas('book', fn ($q) => $q->where('institution_id', $institutionId))
            ->where('copy_code', 'like', $pattern)
            ->get(['copy_code'])
            ->map(function (LibraryBookCopy $copy) use ($prefix) {
                if (!str_starts_with($copy->copy_code, $prefix . '/')) {
                    return 0;
                }
                $tail = substr($copy->copy_code, strlen($prefix) + 1);
                return ctype_digit($tail) ? (int) $tail : 0;
            })
            ->max() ?? 0;

        return (int) $maxSeq + 1;
    }

    public function formatCode(string $prefix, int $seq): string
    {
        return sprintf('%s/%03d', $prefix, $seq);
    }

    /**
     * Generate satu atau lebih nomor inventaris unik untuk buku.
     *
     * @return list<string>
     */
    public function generateCodes(LibraryBook $book, int $count = 1): array
    {
        if ($count < 1) {
            return [];
        }

        return DB::transaction(function () use ($book, $count) {
            $prefix = $this->buildPrefix($book);
            $escaped = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $prefix);
            $pattern = $escaped . '/%';

            // Kunci baris yang cocok agar seq tidak bentrok
            LibraryBookCopy::query()
                ->whereHas('book', fn ($q) => $q->where('institution_id', (int) $book->institution_id))
                ->where('copy_code', 'like', $pattern)
                ->lockForUpdate()
                ->get(['id', 'copy_code']);

            $seq = $this->nextSequence((int) $book->institution_id, $prefix);
            $codes = [];
            $institutionId = (int) $book->institution_id;

            for ($i = 0; $i < $count; $i++) {
                do {
                    $code = $this->formatCode($prefix, $seq);
                    $seq++;
                } while ($this->codeExists($institutionId, $code) || in_array($code, $codes, true));
                $codes[] = $code;
            }

            return $codes;
        });
    }

    public function generateOne(LibraryBook $book): string
    {
        return $this->generateCodes($book, 1)[0];
    }

    public function codeExists(int $institutionId, string $code, ?int $ignoreCopyId = null): bool
    {
        $query = LibraryBookCopy::query()
            ->where('copy_code', $code)
            ->whereHas('book', fn ($q) => $q->where('institution_id', $institutionId));

        if ($ignoreCopyId) {
            $query->where('id', '!=', $ignoreCopyId);
        }

        return $query->exists();
    }

    /**
     * Buat N eksemplar baru dengan nomor inventaris otomatis.
     *
     * @return list<LibraryBookCopy>
     */
    public function createCopies(LibraryBook $book, int $count, array $defaults = []): array
    {
        if ($count < 1) {
            return [];
        }

        return DB::transaction(function () use ($book, $count, $defaults) {
            $codes = $this->generateCodes($book, $count);
            $created = [];
            foreach ($codes as $code) {
                $created[] = LibraryBookCopy::create([
                    'book_id' => $book->id,
                    'copy_code' => $code,
                    'status' => $defaults['status'] ?? 'Tersedia',
                    'condition' => $defaults['condition'] ?? 'Baik',
                    'notes' => $defaults['notes'] ?? null,
                ]);
            }
            return $created;
        });
    }
}
