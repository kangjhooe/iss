<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreLibraryLoanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'copy_id' => 'required|exists:library_book_copies,id',
            'borrower_type' => 'required|in:Student,Employee,External',
            'borrower_id' => 'nullable|integer',
            'borrower_name' => 'required|string|max:255',
            'borrower_identifier' => 'nullable|string|max:100',
            'loan_date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:loan_date',
            'notes' => 'nullable|string',
        ];
    }
}
