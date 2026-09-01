<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class ProfilePhotoService
{
    public const DISK = 'public';

    public const MAX_WIDTH = 600;

    public const MAX_HEIGHT = 800;

    public const JPEG_QUALITY = 80;

    public function store(UploadedFile $file, string $directory): string
    {
        $binary = $this->encodeJpeg($file);
        $path = trim($directory, '/').'/photo.jpg';

        Storage::disk(self::DISK)->put($path, $binary);

        return $path;
    }

    public function deleteDirectory(string $directory): void
    {
        $directory = trim($directory, '/');
        if ($directory === '') {
            return;
        }

        Storage::disk(self::DISK)->deleteDirectory($directory);
    }

    public function deletePath(?string $path): void
    {
        if (! is_string($path) || trim($path) === '') {
            return;
        }

        if (Storage::disk(self::DISK)->exists($path)) {
            Storage::disk(self::DISK)->delete($path);
        }
    }

    protected function encodeJpeg(UploadedFile $file): string
    {
        if (! function_exists('imagecreatetruecolor') || ! function_exists('imagejpeg')) {
            throw ValidationException::withMessages([
                'photo' => 'Pemrosesan gambar tidak tersedia di server.',
            ]);
        }

        $realPath = $file->getRealPath();
        if (! is_string($realPath) || $realPath === '') {
            throw ValidationException::withMessages([
                'photo' => 'Berkas gambar tidak valid atau rusak.',
            ]);
        }

        $info = @getimagesize($realPath);
        if ($info === false) {
            throw ValidationException::withMessages([
                'photo' => 'Berkas gambar tidak valid atau rusak.',
            ]);
        }

        $src = match ($info[2]) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($realPath),
            IMAGETYPE_PNG => @imagecreatefrompng($realPath),
            default => null,
        };

        if ($src === false || $src === null) {
            throw ValidationException::withMessages([
                'photo' => 'Berkas gambar tidak valid atau rusak.',
            ]);
        }

        $oriented = $this->applyExifOrientation($src, $realPath, (int) $info[2]);
        if ($oriented !== $src) {
            imagedestroy($src);
            $src = $oriented;
        }

        $width = imagesx($src);
        $height = imagesy($src);
        if ($width < 1 || $height < 1) {
            imagedestroy($src);
            throw ValidationException::withMessages([
                'photo' => 'Berkas gambar tidak valid atau rusak.',
            ]);
        }

        $scale = min(self::MAX_WIDTH / $width, self::MAX_HEIGHT / $height, 1.0);
        $newWidth = max(1, (int) round($width * $scale));
        $newHeight = max(1, (int) round($height * $scale));

        $dst = imagecreatetruecolor($newWidth, $newHeight);
        if ($dst === false) {
            imagedestroy($src);
            throw ValidationException::withMessages([
                'photo' => 'Gagal memproses foto.',
            ]);
        }

        $white = imagecolorallocate($dst, 255, 255, 255);
        imagefill($dst, 0, 0, $white);
        imagecopyresampled($dst, $src, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
        imagedestroy($src);

        ob_start();
        $ok = imagejpeg($dst, null, self::JPEG_QUALITY);
        $binary = ob_get_clean();
        imagedestroy($dst);

        if (! $ok || ! is_string($binary) || $binary === '') {
            throw ValidationException::withMessages([
                'photo' => 'Gagal memproses foto.',
            ]);
        }

        return $binary;
    }

    protected function applyExifOrientation($src, string $path, int $type)
    {
        if ($type !== IMAGETYPE_JPEG || ! function_exists('exif_read_data')) {
            return $src;
        }

        $exif = @exif_read_data($path);
        $orientation = (int) ($exif['Orientation'] ?? 1);

        $rotated = match ($orientation) {
            3 => imagerotate($src, 180, 0),
            6 => imagerotate($src, -90, 0),
            8 => imagerotate($src, 90, 0),
            default => false,
        };

        return $rotated === false ? $src : $rotated;
    }
}
