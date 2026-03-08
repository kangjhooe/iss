<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

/**
 * Sanitasi HTML untuk soal/stimulus. Pakai Mews\Purifier jika terpasang, fallback strip_tags.
 * Aman dipanggil saat paket mews/purifier tidak terpasang (tanpa Fatal Error).
 */
class HtmlPurifier
{
    private const ALLOWED_TAGS = '<p><br><strong><b><em><i><u><s><sub><sup><span><ul><ol><li><a><img><table><thead><tbody><tr><th><td><div><h2><h3><h4>';

    public static function sanitizeQuestion(string $html, string $profile = 'question'): string
    {
        $html = trim($html);
        if ($html === '') {
            return $html;
        }

        if (class_exists(\Mews\Purifier\Facades\Purifier::class)) {
            try {
                return \Mews\Purifier\Facades\Purifier::clean($html, $profile);
            } catch (\Throwable $e) {
                Log::warning('HtmlPurifier: Purifier::clean failed', ['error' => $e->getMessage()]);
            }
        }

        return strip_tags($html, self::ALLOWED_TAGS);
    }
}
