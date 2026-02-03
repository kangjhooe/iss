<?php

namespace App\Http\Requests;

use App\Helpers\FileUploadRules;
use Illuminate\Foundation\Http\FormRequest;

class StoreLibraryBookRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'category_id' => 'required|exists:library_book_categories,id',
            'isbn' => 'nullable|string|max:30',
            'title' => 'required|string|max:255',
            'author' => 'nullable|string|max:255',
            'publisher' => 'nullable|string|max:255',
            'year' => 'nullable|integer|min:1000|max:2100',
            'language' => 'nullable|string|max:50',
            'pages' => 'nullable|integer|min:0',
            'shelf_code' => 'nullable|string|max:50',
            'description' => 'nullable|string',
        ];
        return array_merge($rules, FileUploadRules::rules(FileUploadRules::TYPE_IMAGE_ONLY, 2048, false, 'cover'));
    }
}
