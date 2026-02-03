<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateLibraryBookCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $category = $this->route('category');
        return [
            'code' => [
                'sometimes',
                'string',
                'max:20',
                $category ? Rule::unique('library_book_categories', 'code')
                    ->where('institution_id', $category->institution_id)
                    ->ignore($category->id) : 'sometimes|string|max:20',
            ],
            'name' => 'sometimes|string|max:100',
            'description' => 'nullable|string',
            'is_active' => 'nullable|boolean',
        ];
    }
}
