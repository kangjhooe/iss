<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreDispositionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization handled in controller
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'to_user_id' => 'required|exists:user,id',
            'instruction' => 'required|string|max:1000',
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
            'to_user_id.required' => 'Penerima disposisi wajib diisi',
            'to_user_id.exists' => 'User penerima tidak ditemukan',
            'instruction.required' => 'Instruksi disposisi wajib diisi',
            'instruction.max' => 'Instruksi maksimal 1000 karakter',
        ];
    }
}
