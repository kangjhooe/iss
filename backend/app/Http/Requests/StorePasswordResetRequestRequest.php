<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePasswordResetRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'email' => strtolower(trim((string) $this->input('email', ''))),
            'npsn' => preg_replace('/\D+/', '', (string) $this->input('npsn', '')) ?: $this->input('npsn'),
            'contact_phone' => $this->filled('contact_phone')
                ? preg_replace('/\D+/', '', (string) $this->input('contact_phone'))
                : null,
            'note' => $this->filled('note') ? trim((string) $this->input('note')) : null,
        ]);
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'email', 'max:255'],
            'npsn' => ['required', 'string', 'regex:/^[0-9]{8}$/'],
            'contact_phone' => ['nullable', 'string', 'max:20'],
            'note' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => 'Email wajib diisi',
            'email.email' => 'Format email tidak valid',
            'npsn.required' => 'NPSN wajib diisi',
            'npsn.regex' => 'NPSN harus terdiri dari 8 digit angka',
        ];
    }
}
