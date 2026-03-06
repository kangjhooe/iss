<?php

namespace App\Http\Requests;

use App\Http\Rules\NpsnReferensiRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'npsn' => [
                'required',
                'string',
                'size:8',
                'regex:/^[0-9]{8}$/',
                'unique:institution,npsn',
                new NpsnReferensiRule(),
            ],
            'institution_name' => 'required|string|max:255',
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:user,email',
            'phone' => 'required|string|max:20',
            'password' => [
                'required',
                'string',
                'confirmed',
                Password::min(8)
                    ->letters()
                    ->mixedCase()
                    ->numbers()
                    ->symbols(),
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'npsn.required' => 'NPSN wajib diisi',
            'npsn.size' => 'NPSN harus terdiri dari 8 digit',
            'npsn.regex' => 'NPSN harus berupa angka 8 digit',
            'npsn.unique' => 'NPSN sudah terdaftar',
            'institution_name.required' => 'Nama institusi wajib diisi',
            'name.required' => 'Nama lengkap wajib diisi',
            'email.required' => 'Email wajib diisi',
            'email.email' => 'Format email tidak valid',
            'email.unique' => 'Email sudah terdaftar',
            'phone.required' => 'Nomor telepon wajib diisi',
            'password.required' => 'Password wajib diisi',
            'password.confirmed' => 'Konfirmasi password tidak cocok',
        ];
    }
}
