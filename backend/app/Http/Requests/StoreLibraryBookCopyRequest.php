<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreLibraryBookCopyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'book_id' => 'required|exists:library_books,id',
            'copy_code' => 'nullable|string|max:50',
            'quantity' => 'nullable|integer|min:1|max:100',
            'status' => 'nullable|in:Tersedia,Dipinjam,Rusak,Hilang',
            'condition' => 'nullable|in:Baik,Rusak Ringan,Rusak Berat',
            'notes' => 'nullable|string',
        ];
    }
}
