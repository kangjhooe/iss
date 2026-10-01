<?php

namespace App\Services;

use App\Models\LibraryBook;
use App\Models\LibraryBookCategory;
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
        'kelas',
        'tanggal_beli',
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
        $grade = $this->clean($row['kelas'] ?? null);
        $acquiredAt = $this->parseDate($row['tanggal_beli'] ?? null);
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
            'grade' => $grade,
            'acquired_at' => $acquiredAt,
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
                if (empty($payload['acquired_at']) && $existing->acquired_at) {
                    unset($payload['acquired_at']);
                }
                if (($payload['grade'] ?? null) === null && $existing->grade) {
                    unset($payload['grade']);
                }
                $existing->update($payload);
                $book = $existing;
                $outcome = 'updated';
            } else {
                $payload['institution_id'] = $institutionId;
                $payload['created_by'] = $userId;
                if (empty($payload['acquired_at'])) {
                    $payload['acquired_at'] = now()->toDateString();
                }
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

        $book->loadMissing(['category', 'institution']);
        app(LibraryInventoryNumberService::class)->createCopies($book, $toCreate);
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
                $book->grade ?? '',
                $book->acquired_at?->format('Y-m-d') ?? '',
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
        if (is_bool($value)) {
            return $value ? '1' : '0';
        }
        if (is_int($value)) {
            $value = (string) $value;
        } elseif (is_float($value)) {
            // Hindari (int) cast overflow & scientific notation untuk ISBN panjang
            $value = sprintf('%.0f', $value);
        }
        $v = trim((string) $value);
        // "9.78602E+12" dari Excel teks
        if (preg_match('/^\d+(\.\d+)?[eE][+-]?\d+$/', $v)) {
            $asFloat = (float) $v;
            if (is_finite($asFloat)) {
                $v = sprintf('%.0f', $asFloat);
            }
        }
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

    private function parseDate(mixed $value): ?string
    {
        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d');
        }
        if (is_numeric($value)) {
            // Excel serial date
            $serial = (float) $value;
            if ($serial > 20000 && $serial < 80000) {
                $unix = (int) (($serial - 25569) * 86400);
                return gmdate('Y-m-d', $unix);
            }
        }
        $v = $this->clean($value);
        if ($v === null) {
            return null;
        }
        if (preg_match('/^\d{4}-\d{2}-\d{2}/', $v)) {
            return substr($v, 0, 10);
        }
        if (preg_match('/^(\d{1,2})[\/\-.](\d{1,2})[\/\-.](\d{4})$/', $v, $m)) {
            return sprintf('%04d-%02d-%02d', (int) $m[3], (int) $m[2], (int) $m[1]);
        }
        try {
            return \Carbon\Carbon::parse($v)->format('Y-m-d');
        } catch (\Throwable) {
            return null;
        }
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
