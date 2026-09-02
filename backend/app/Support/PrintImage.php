<?php

namespace App\Support;

use App\Models\Student;

/**
 * Gambar aman untuk DomPDF (hindari PNG/GIF tanpa ekstensi GD).
 */
class PrintImage
{
    public static function safeDataUri(?string $uri): ?string
    {
        if (! is_string($uri) || ! preg_match('#^data:image/(jpeg|jpg|pjpeg|png|gif|webp);base64,#i', $uri)) {
            return null;
        }

        if (preg_match('#^data:image/(png|gif|webp)#i', $uri) && ! extension_loaded('gd')) {
            return null;
        }

        return $uri;
    }

    public static function studentPhoto(Student $student): ?string
    {
        try {
            return self::safeDataUri($student->resolvePrintPhotoDataUri());
        } catch (\Throwable $e) {
            return null;
        }
    }
}
