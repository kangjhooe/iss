<?php

namespace App\Http\Rules;

use App\Services\NpsnValidationService;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class NpsnReferensiRule implements ValidationRule
{
    public function __construct(
        protected ?NpsnValidationService $service = null
    ) {
        $this->service = $this->service ?? NpsnValidationService::fromConfig();
    }

    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || trim($value) === '') {
            return; // Biarkan required/nullable menangani kosong
        }

        $normalized = $this->service->normalizeNpsn($value);
        if ($normalized === null) {
            return; // Bukan 8 digit: skip (untuk field opsional) atau gunakan rule size:8 untuk wajib
        }

        if (! $this->service->isValid($normalized)) {
            $fail('NPSN tidak terdaftar di data referensi Kemendikbud. Pastikan NPSN benar dan sekolah masih aktif.');
        }
    }
}
