<?php

namespace App\Services;

use App\Models\LibraryBook;
use App\Models\LibraryBookCategory;
use App\Models\LibraryBookCopy;
use Illuminate\Support\Facades\DB;

class LibraryBookImportService
{
    public const HEADERS = [
        'kode_kategori',
        'judul',
        'isbn',
        'pengarang',
        'penerbit',
        'tahun',
        'bahasa',
        'halaman',
        'rak',
        'deskripsi',
        'jumlah_eksemplar',
    ];

    /**
     * Import katalog dari array baris (hasil parse Excel di frontend).
     *
     * @param  array<int, array<string, mixed>>  $rows
     * @return array{success:int,failed:int,updated:int,errors:array<int,string>}
     */
    public function importFromRows(array $rows, int $institutionId, int $userId): array
    {
        $results = [
            'success' => 0,
            'updated' => 0,
            'failed' => 0,
            'errors' => [],
        ];

        if ($rows === []) {
            throw new \Exception('Tidak ada data buku untuk diimpor.');
        }

        $categoriesByCode = LibraryBookCategory::query()
            ->forInstitution($institutionId)
            ->active()
            ->get()
            ->keyBy(fn ($c) => strtoupper(trim((string) $c->code)));

        foreach ($rows as $index => $row) {
            $rowNumber = $index + 1;
            if (!is_array($row)) {
                $results['failed']++;
                $results['errors'][] = "Baris {$rowNumber}: format baris tidak valid.";
                continue;
            }

            $data = $this->normalizeRowKeys($row);
            if ($this->isEmptyAssocRow($data)) {
                continue;
            }

            try {
                $outcome = $this->importRow($data, $institutionId, $userId, $categoriesByCode);
                if ($outcome === 'created') {
                    $results['success']++;
                } elseif ($outcome === 'updated') {
                    $results['updated']++;
                }
            } catch (\Throwable $e) {
                $results['failed']++;
                $results['errors'][] = "Baris {$rowNumber}: " . $e->getMessage();
            }
        }

        return $results;
    }

    /**
     * @param  \Illuminate\Support\Collection<string, LibraryBookCategory>  $categoriesByCode
     */
    private function importRow(array $row, int $institutionId, int $userId, $categoriesByCode): string
    {
        $code = strtoupper(trim((string) ($row['kode_kategori'] ?? '')));
        $title = $this->clean($row['judul'] ?? null);

        if ($code === '') {
            throw new \Exception('kode_kategori wajib diisi.');
        }
        if ($title === null || $title === '') {
            throw new \Exception('judul wajib diisi.');
        }

        $category = $categoriesByCode->get($code);
        if (!$category) {
            throw new \Exception("kode kategori '{$code}' tidak ditemukan atau tidak aktif. Buat kategori di master terlebih dahulu.");
        }

        $isbn = $this->clean($row['isbn'] ?? null);
        $author = $this->clean($row['pengarang'] ?? null);
        $publisher = $this->clean($row['penerbit'] ?? null);
        $year = $this->parseYear($row['tahun'] ?? null);
        $language = $this->clean($row['bahasa'] ?? null);
        $pages = $this->parseInt($row['halaman'] ?? null);
        $shelf = $this->clean($row['rak'] ?? null);
        $description = $this->clean($row['deskripsi'] ?? null);
        $copiesCount = $this->parseInt($row['jumlah_eksemplar'] ?? null) ?? 0;
        if ($copiesCount < 0) {
            $copiesCount = 0;
        }
        if ($copiesCount > 100) {
            throw new \Exception('jumlah_eksemplar maksimal 100 per baris.');
        }

        $payload = [
            'category_id' => $category->id,
            'isbn' => $isbn,
            'title' => $title,
            'author' => $author,
            'publisher' => $publisher,
            'year' => $year,
            'language' => $language,
            'pages' => $pages,
            'shelf_code' => $shelf,
            'description' => $description,
            'updated_by' => $userId,
        ];

        return DB::transaction(function () use ($payload, $institutionId, $userId, $isbn, $title, $author, $copiesCount) {
            $existing = null;
            if ($isbn) {
                $existing = LibraryBook::query()
                    ->forInstitution($institutionId)
                    ->where('isbn', $isbn)
                    ->first();
            }
            if (!$existing) {
                $q = LibraryBook::query()
                    ->forInstitution($institutionId)
                    ->where('title', $title);
                if ($author) {
                    $q->where('author', $author);
                } else {
                    $q->where(function ($b) {
                        $b->whereNull('author')->orWhere('author', '');
                    });
                }
                $existing = $q->first();
            }

            if ($existing) {
                $existing->update($payload);
                $book = $existing;
                $outcome = 'updated';
            } else {
                $payload['institution_id'] = $institutionId;
                $payload['created_by'] = $userId;
                $book = LibraryBook::create($payload);
                $outcome = 'created';
            }

            if ($copiesCount > 0) {
                $this->ensureCopies($book, $copiesCount);
            }

            return $outcome;
        });
    }

    private function ensureCopies(LibraryBook $book, int $desiredTotal): void
    {
        $current = $book->copies()->count();
        $toCreate = $desiredTotal - $current;
        if ($toCreate <= 0) {
            return;
        }

        for ($i = 1; $i <= $toCreate; $i++) {
            $seq = $current + $i;
            $code = sprintf('BK-%d-%03d', $book->id, $seq);
            $n = 0;
            while (LibraryBookCopy::where('copy_code', $code)->where('book_id', $book->id)->exists()
                || LibraryBookCopy::where('copy_code', $code)->exists()) {
                $n++;
                $code = sprintf('BK-%d-%03d-%d', $book->id, $seq, $n);
            }
            LibraryBookCopy::create([
                'book_id' => $book->id,
                'copy_code' => $code,
                'status' => 'Tersedia',
                'condition' => 'Baik',
            ]);
        }
    }

    /**
     * Build CSV export of books for an institution.
     */
    public function exportCsv(int $institutionId, array $filters = []): string
    {
        $query = LibraryBook::query()
            ->forInstitution($institutionId)
            ->with(['category'])
            ->withCount('copies');

        if (!empty($filters['search'])) {
            $q = $filters['search'];
            $query->where(function ($b) use ($q) {
                $b->where('title', 'like', "%{$q}%")
                    ->orWhere('author', 'like', "%{$q}%")
                    ->orWhere('isbn', 'like', "%{$q}%");
            });
        }
        if (!empty($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        $books = $query->orderBy('title')->limit(5000)->get();

        $lines = [];
        $lines[] = $this->csvLine(self::HEADERS);
        foreach ($books as $book) {
            $lines[] = $this->csvLine([
                $book->category?->code ?? '',
                $book->title,
                $book->isbn ?? '',
                $book->author ?? '',
                $book->publisher ?? '',
                $book->year ?? '',
                $book->language ?? '',
                $book->pages ?? '',
                $book->shelf_code ?? '',
                $book->description ?? '',
                $book->copies_count ?? 0,
            ]);
        }

        return "\xEF\xBB\xBF" . implode("\n", $lines) . "\n";
    }

    private function normalizeRowKeys(array $row): array
    {
        $normalized = [];
        foreach ($row as $key => $value) {
            $normalized[$this->normalizeHeader((string) $key)] = $value;
        }
        return $normalized;
    }

    private function csvLine(array $fields): string
    {
        return implode(',', array_map(function ($value) {
            $value = (string) ($value ?? '');
            $value = str_replace(["\r\n", "\r", "\n"], ' ', $value);
            if (str_contains($value, ',') || str_contains($value, '"') || str_contains($value, ';')) {
                return '"' . str_replace('"', '""', $value) . '"';
            }
            return $value;
        }, $fields));
    }

    private function normalizeHeader(?string $header): string
    {
        $h = strtolower(trim((string) $header));
        $h = str_replace([' ', '-'], '_', $h);
        return $h;
    }

    private function clean(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }
        if (is_float($value) || is_int($value)) {
            if (is_float($value) && floor($value) == $value) {
                $value = (int) $value;
            }
            $value = (string) $value;
        }
        $v = trim((string) $value);
        return $v === '' ? null : $v;
    }

    private function parseYear(mixed $value): ?int
    {
        $v = $this->clean($value);
        if ($v === null || !preg_match('/^\d{4}$/', $v)) {
            return null;
        }
        $y = (int) $v;
        if ($y < 1000 || $y > 2100) {
            return null;
        }
        return $y;
    }

    private function parseInt(mixed $value): ?int
    {
        $v = $this->clean($value);
        if ($v === null || !is_numeric($v)) {
            return null;
        }
        return (int) $v;
    }

    private function isEmptyAssocRow(array $row): bool
    {
        foreach ($row as $cell) {
            if ($this->clean($cell) !== null) {
                return false;
            }
        }
        return true;
    }
}
