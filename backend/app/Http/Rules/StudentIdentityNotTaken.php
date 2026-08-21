<?php

namespace App\Http\Rules;

use App\Support\StudentIdentity;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class StudentIdentityNotTaken implements ValidationRule
{
    public function __construct(
        protected string $field = 'nisn',
        protected ?int $ignoreStudentId = null,
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $value = is_string($value) ? trim($value) : (string) $value;
        if ($value === '') {
            return;
        }

        $column = $this->field === 'nik' ? 'nik' : 'nisn';
        if (! StudentIdentity::isTakenByActive($column, $value, $this->ignoreStudentId)) {
            return;
        }

        $fail($column === 'nik'
            ? 'NIK sudah terdaftar sebagai siswa aktif. Pendaftaran baru tidak dapat dilanjutkan.'
            : 'NISN sudah terdaftar sebagai siswa aktif. Pendaftaran baru tidak dapat dilanjutkan.');
    }
}
