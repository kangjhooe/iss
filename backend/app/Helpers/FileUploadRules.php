<?php

namespace App\Helpers;

/**
 * Helper class untuk standarisasi validasi file upload
 */
class FileUploadRules
{
    /**
     * File type constants
     */
    public const TYPE_DOCUMENT = 'document';      // PDF, DOC, DOCX
    public const TYPE_IMAGE = 'image';            // JPG, JPEG, PNG
    public const TYPE_MIXED = 'mixed';            // PDF, DOC, DOCX, JPG, JPEG, PNG
    public const TYPE_PDF_ONLY = 'pdf_only';     // PDF only
    public const TYPE_IMAGE_ONLY = 'image_only'; // JPG, JPEG, PNG only
    public const TYPE_IMAGE_LOGO = 'image_logo'; // JPG, JPEG, PNG, GIF (untuk logo institusi)
    public const TYPE_FAVICON = 'favicon';       // ICO, PNG, SVG (untuk favicon aplikasi)

    /**
     * Size constants (in KB)
     */
    public const SIZE_SMALL = 2048;   // 2MB
    public const SIZE_MEDIUM = 5120;  // 5MB
    public const SIZE_LARGE = 10240; // 10MB

    /**
     * Get validation rules for file upload based on type and size
     *
     * @param string $type File type (document, image, mixed, pdf_only, image_only)
     * @param int $maxSize Maximum size in KB (default: 5120 = 5MB)
     * @param bool $required Whether file is required
     * @param string $fieldName Field name (default: 'file')
     * @return array Validation rules
     */
    public static function rules(
        string $type = self::TYPE_DOCUMENT,
        int $maxSize = self::SIZE_MEDIUM,
        bool $required = false,
        string $fieldName = 'file'
    ): array {
        $mimeTypes = self::getMimeTypes($type);
        $requiredRule = $required ? 'required' : 'nullable';

        return [
            $fieldName => $requiredRule . '|file|mimes:' . implode(',', $mimeTypes) . '|max:' . $maxSize,
        ];
    }

    /**
     * Get validation rules for multiple file uploads
     *
     * @param string $type File type
     * @param int $maxSize Maximum size in KB
     * @param int $maxFiles Maximum number of files
     * @param string $fieldName Field name (default: 'files')
     * @return array Validation rules
     */
    public static function multipleRules(
        string $type = self::TYPE_MIXED,
        int $maxSize = self::SIZE_LARGE,
        int $maxFiles = 10,
        string $fieldName = 'files'
    ): array {
        $mimeTypes = self::getMimeTypes($type);

        return [
            $fieldName => 'required|array|min:1|max:' . $maxFiles,
            $fieldName . '.*' => 'required|file|mimes:' . implode(',', $mimeTypes) . '|max:' . $maxSize,
        ];
    }

    /**
     * Get custom validation messages for file upload
     *
     * @param string $type File type
     * @param int $maxSize Maximum size in KB
     * @param string $fieldName Field name
     * @param bool $multiple Whether multiple files
     * @return array Validation messages
     */
    public static function messages(
        string $type = self::TYPE_DOCUMENT,
        int $maxSize = self::SIZE_MEDIUM,
        string $fieldName = 'file',
        bool $multiple = false
    ): array {
        $mimeTypes = self::getMimeTypes($type);
        $allowedTypes = self::getAllowedTypesLabel($type);
        $maxSizeMB = round($maxSize / 1024, 1);

        if ($multiple) {
            return [
                $fieldName . '.required' => 'Minimal 1 file harus diunggah',
                $fieldName . '.array' => 'Format file tidak valid',
                $fieldName . '.min' => 'Minimal 1 file harus diunggah',
                $fieldName . '.max' => 'Maksimal :max file dapat diunggah sekaligus',
                $fieldName . '.*.required' => 'File wajib diisi',
                $fieldName . '.*.file' => 'File tidak valid',
                $fieldName . '.*.max' => 'Ukuran file maksimal ' . $maxSizeMB . 'MB',
                $fieldName . '.*.mimes' => 'Format file harus ' . $allowedTypes,
            ];
        }

        return [
            $fieldName . '.required' => 'File wajib diunggah',
            $fieldName . '.file' => 'File tidak valid',
            $fieldName . '.max' => 'Ukuran file maksimal ' . $maxSizeMB . 'MB',
            $fieldName . '.mimes' => 'Format file harus ' . $allowedTypes,
        ];
    }

    /**
     * Get MIME types based on file type
     *
     * @param string $type File type
     * @return array MIME types
     */
    private static function getMimeTypes(string $type): array
    {
        return match ($type) {
            self::TYPE_DOCUMENT => ['pdf', 'doc', 'docx'],
            self::TYPE_IMAGE => ['jpg', 'jpeg', 'png'],
            self::TYPE_MIXED => ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png'],
            self::TYPE_PDF_ONLY => ['pdf'],
            self::TYPE_IMAGE_ONLY => ['jpg', 'jpeg', 'png'],
            self::TYPE_IMAGE_LOGO => ['jpg', 'jpeg', 'png', 'gif'],
            self::TYPE_FAVICON => ['ico', 'png', 'svg'],
            default => ['pdf', 'doc', 'docx'],
        };
    }

    /**
     * Get human-readable allowed types label
     *
     * @param string $type File type
     * @return string Label
     */
    private static function getAllowedTypesLabel(string $type): string
    {
        return match ($type) {
            self::TYPE_DOCUMENT => 'PDF, DOC, atau DOCX',
            self::TYPE_IMAGE => 'JPG, JPEG, atau PNG',
            self::TYPE_MIXED => 'PDF, DOC, DOCX, JPG, JPEG, atau PNG',
            self::TYPE_PDF_ONLY => 'PDF',
            self::TYPE_IMAGE_ONLY => 'JPG, JPEG, atau PNG',
            self::TYPE_IMAGE_LOGO => 'JPG, JPEG, PNG, atau GIF',
            self::TYPE_FAVICON => 'ICO, PNG, atau SVG',
            default => 'PDF, DOC, atau DOCX',
        };
    }

    /**
     * Get validation rules for correspondence file upload
     *
     * @param bool $required Whether file is required
     * @return array Validation rules
     */
    public static function correspondenceFile(bool $required = false): array
    {
        return self::rules(self::TYPE_PDF_ONLY, self::SIZE_MEDIUM, $required);
    }

    /**
     * Get validation rules for correspondence attachments
     *
     * @return array Validation rules
     */
    public static function correspondenceAttachments(): array
    {
        return self::multipleRules(self::TYPE_MIXED, self::SIZE_LARGE, 10);
    }

    /**
     * Get validation rules for student document upload
     *
     * @return array Validation rules
     */
    public static function studentDocument(): array
    {
        return self::rules(self::TYPE_MIXED, self::SIZE_SMALL, true);
    }

    /**
     * Get validation rules for employee document upload
     *
     * @return array Validation rules
     */
    public static function employeeDocument(): array
    {
        return self::rules(self::TYPE_PDF_ONLY, self::SIZE_SMALL, true);
    }

    /**
     * Get validation rules for inventory image upload
     *
     * @return array Validation rules
     */
    public static function inventoryImage(): array
    {
        return self::rules(self::TYPE_IMAGE_ONLY, self::SIZE_SMALL, false, 'image');
    }

    /**
     * Get validation rules for institution logo upload
     *
     * @return array Validation rules
     */
    public static function institutionLogo(): array
    {
        return self::rules(self::TYPE_IMAGE_LOGO, self::SIZE_SMALL, true, 'logo');
    }

    /**
     * Get validation rules for app logo upload (super admin).
     */
    public static function appLogo(): array
    {
        return self::rules(self::TYPE_IMAGE_LOGO, self::SIZE_SMALL, true, 'logo');
    }

    /**
     * Get validation rules for favicon upload (super admin).
     */
    public static function appFavicon(): array
    {
        return self::rules(self::TYPE_FAVICON, 512, true, 'favicon'); // 512 KB
    }
}
