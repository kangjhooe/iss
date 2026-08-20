<?php

namespace App\Http\Requests;

use App\Services\LocalNisService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateNisNumberingRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        if (!$user) {
            return false;
        }

        return $user->isAdminOrSuperAdmin()
            || $user->isInstitutionAdmin()
            || $user->hasModuleAccess('student');
    }

    public function rules(): array
    {
        return [
            'preset' => ['required', 'string', Rule::in(array_keys(LocalNisService::PRESET_PATTERNS))],
            'prefix' => ['nullable', 'string', 'max:12'],
            'seq_digits' => ['required', 'integer', 'min:3', 'max:6'],
            'reset' => ['required', 'string', Rule::in([LocalNisService::RESET_YEARLY, LocalNisService::RESET_NEVER])],
            'pattern' => ['nullable', 'string', 'max:50'],
        ];
    }

    public function messages(): array
    {
        return [
            'preset.required' => 'Format NIS wajib dipilih.',
            'preset.in' => 'Format NIS tidak dikenali.',
            'seq_digits.min' => 'Digit nomor urut minimal 3.',
            'seq_digits.max' => 'Digit nomor urut maksimal 6.',
            'reset.in' => 'Mode reset nomor urut tidak valid.',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $preset = (string) $this->input('preset');
            $prefix = trim((string) $this->input('prefix', ''));
            $pattern = trim((string) $this->input('pattern', ''));

            if (in_array($preset, [LocalNisService::PRESET_PREFIX_TAHUN_URUT, LocalNisService::PRESET_PREFIX_TAHUN2_URUT], true)
                && $prefix === '') {
                $validator->errors()->add('prefix', 'Kode sekolah (prefix) wajib diisi untuk format ini.');
            }

            if ($preset === LocalNisService::PRESET_CUSTOM) {
                if ($pattern === '' || !preg_match('/\{SEQ(?::[1-8])?\}/', $pattern)) {
                    $validator->errors()->add('pattern', 'Pola kustom harus berisi {SEQ} atau {SEQ:n}.');
                }
            }
        });
    }
}
