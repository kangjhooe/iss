<?php

namespace App\Support;

/**
 * Siapkan path gambar lokal untuk DomPDF.
 * JPEG bisa tanpa GD; PNG/GIF/WebP butuh ekstensi GD di PHP.
 */
class DomPdfImage
{
    public static function resolvePath(?string $absolutePath): ?string
    {
        if (!$absolutePath || !is_file($absolutePath)) {
            return null;
        }

        $ext = strtolower(pathinfo($absolutePath, PATHINFO_EXTENSION));

        if (in_array($ext, ['jpg', 'jpeg'], true)) {
            return $absolutePath;
        }

        if (!extension_loaded('gd')) {
            return null;
        }

        return $absolutePath;
    }

    public static function gdAvailable(): bool
    {
        return extension_loaded('gd');
    }
}
