<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Store PPDB applicant documents on the private local disk.
 * Falls back to the legacy public disk for files uploaded before the hardening.
 */
class PpdbDocumentStorage
{
    public const DISK = 'local';

    public const LEGACY_DISK = 'public';

    public static function store(UploadedFile $file, int $applicantId, string $fileName): string
    {
        return $file->storeAs('ppdb_applicant_documents/' . $applicantId, $fileName, self::DISK);
    }

    public static function exists(string $path): bool
    {
        return Storage::disk(self::DISK)->exists($path)
            || Storage::disk(self::LEGACY_DISK)->exists($path);
    }

    public static function diskFor(string $path): string
    {
        if (Storage::disk(self::DISK)->exists($path)) {
            return self::DISK;
        }

        return self::LEGACY_DISK;
    }

    public static function delete(string $path): void
    {
        foreach ([self::DISK, self::LEGACY_DISK] as $disk) {
            if (Storage::disk($disk)->exists($path)) {
                Storage::disk($disk)->delete($path);
            }
        }
    }

    public static function download(string $path, ?string $name = null): StreamedResponse
    {
        $disk = self::diskFor($path);

        return Storage::disk($disk)->download($path, $name);
    }

    /**
     * Copy a PPDB document into student_documents on the public disk
     * (StudentController still serves from public).
     */
    public static function copyToStudentDocuments(string $sourcePath, string $destPath): bool
    {
        if (!self::exists($sourcePath)) {
            return false;
        }

        $contents = Storage::disk(self::diskFor($sourcePath))->get($sourcePath);
        Storage::disk(self::LEGACY_DISK)->put($destPath, $contents);

        return true;
    }
}
