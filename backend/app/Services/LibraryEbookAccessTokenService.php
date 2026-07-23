<?php

namespace App\Services;

use App\Models\Institution;
use App\Models\LibraryBook;
use Illuminate\Support\Carbon;

class LibraryEbookAccessTokenService
{
    public const TTL_MINUTES = 10;

    /**
     * Buat token akses stream PDF (berlaku singkat).
     *
     * @return array{token: string, expires: int}
     */
    public function issue(LibraryBook $book, Institution $institution): array
    {
        $expires = now()->addMinutes(self::TTL_MINUTES)->getTimestamp();
        $token = $this->sign((int) $book->id, (int) $institution->id, (string) $institution->npsn, $expires);

        return [
            'token' => $token,
            'expires' => $expires,
        ];
    }

    public function validate(
        LibraryBook $book,
        Institution $institution,
        ?string $token,
        $expires
    ): bool {
        if (!$token || !$expires) {
            return false;
        }

        $expires = (int) $expires;
        if ($expires < now()->getTimestamp()) {
            return false;
        }

        $expected = $this->sign((int) $book->id, (int) $institution->id, (string) $institution->npsn, $expires);

        return hash_equals($expected, $token);
    }

    public function watermarkText(Institution $institution): string
    {
        $date = Carbon::now()->locale('id')->isoFormat('D MMM YYYY');
        $parts = array_filter([
            $institution->name,
            $institution->npsn ? 'NPSN ' . $institution->npsn : null,
            $date,
        ]);

        return implode(' · ', $parts);
    }

    private function sign(int $bookId, int $institutionId, string $npsn, int $expires): string
    {
        $payload = implode('|', [$bookId, $institutionId, $npsn, $expires]);

        return hash_hmac('sha256', $payload, (string) config('app.key'));
    }
}
