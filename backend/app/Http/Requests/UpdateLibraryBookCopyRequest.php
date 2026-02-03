<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateLibraryBookCopyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'copy_code' => 'sometimes|string|max:50',
            'status' => 'nullable|in:Tersedia,Dipinjam,Rusak,Hilang',
            'condition' => 'nullable|in:Baik,Rusak Ringan,Rusak Berat',
            'notes' => 'nullable|string',
        ];
    }
}
