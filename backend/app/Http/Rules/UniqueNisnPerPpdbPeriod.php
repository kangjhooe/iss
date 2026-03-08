<?php

namespace App\Http\Rules;

use App\Models\PpdbApplicant;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * NISN unik per periode PPDB: dalam satu periode, satu NISN hanya boleh dipakai satu calon.
 * Kosong/blank tidak divalidasi.
 */
class UniqueNisnPerPpdbPeriod implements ValidationRule
{
    public function __construct(
        protected ?int $periodId = null,
        protected ?int $ignoreApplicantId = null
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $value = is_string($value) ? trim($value) : '';
        if ($value === '') {
            return;
        }

        if (!$this->periodId) {
            return;
        }

        $query = PpdbApplicant::query()
            ->where('ppdb_period_id', $this->periodId)
            ->where('nisn', $value);

        if ($this->ignoreApplicantId !== null) {
            $query->where('id', '!=', $this->ignoreApplicantId);
        }

        if ($query->exists()) {
            $fail('NISN ini sudah terdaftar pada periode PPDB yang sama.');
        }
    }
}
