<?php

namespace App\Helpers;

use Illuminate\Http\UploadedFile;

/**
 * Helper untuk sanitasi nama file saat upload (mencegah path traversal & karakter berbahaya).
 */
class FileUploadHelper
{
    /**
     * Daftar ekstensi yang diizinkan untuk import (whitelist).
     */
    public const ALLOWED_IMPORT_EXTENSIONS = ['csv', 'txt', 'xlsx', 'xls'];

    /**
     * Generate nama file aman untuk disimpan (tanpa path traversal, karakter aneh).
     */
    public static function safeStorageName(UploadedFile $file, ?string $prefix = null): string
    {
        $originalName = $file->getClientOriginalName();
        $extension = $file->getClientOriginalExtension();
        $safeFilename = preg_replace('/[^a-zA-Z0-9._-]/', '_', pathinfo($originalName, PATHINFO_FILENAME));
        $safeExtension = preg_replace('/[^a-zA-Z0-9]/', '', strtolower($extension ?: 'bin'));
        $base = ($prefix ? $prefix . '_' : '') . time() . '_' . $safeFilename;
        return $base . '.' . $safeExtension;
    }

    /**
     * Nama file aman untuk import (hanya ekstensi whitelist).
     */
    public static function safeImportFileName(UploadedFile $file, string $prefix = 'import'): string
    {
        $ext = strtolower($file->getClientOriginalExtension());
        if (!in_array($ext, self::ALLOWED_IMPORT_EXTENSIONS, true)) {
            $ext = 'csv';
        }
        return $prefix . '_' . time() . '_' . uniqid() . '.' . $ext;
    }
}
