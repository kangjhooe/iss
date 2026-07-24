<?php

namespace App\Http\Requests;

use App\Models\FeedbackTicket;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreFeedbackTicketRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->user();

        return $user
            && ($user->isInstitutionAdmin() || $user->isAdmin())
            && $user->institution_id;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<int, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'type' => ['required', Rule::in(FeedbackTicket::TYPES)],
            'title' => ['required', 'string', 'max:200'],
            'description' => ['required', 'string', 'max:5000'],
            'module' => ['nullable', 'string', 'max:100'],
            'priority' => ['nullable', Rule::in(FeedbackTicket::PRIORITIES)],
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
            'type.required' => 'Tipe laporan wajib dipilih',
            'type.in' => 'Tipe harus berupa bug atau feature',
            'title.required' => 'Judul wajib diisi',
            'title.max' => 'Judul maksimal 200 karakter',
            'description.required' => 'Deskripsi wajib diisi',
            'description.max' => 'Deskripsi maksimal 5000 karakter',
        ];
    }
}
