<?php

namespace App\Http\Requests;

use App\Helpers\FileUploadRules;
use App\Support\InstitutionContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateLibraryBookRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $user = $this->user();
        $institutionId = $user
            ? InstitutionContext::resolveForUser($user, $this, $this->get('institution_id'))
            : null;

        $rules = [
            'category_id' => [
                'sometimes',
                $institutionId
                    ? Rule::exists('library_book_categories', 'id')->where('institution_id', $institutionId)
                    : 'exists:library_book_categories,id',
            ],
            'isbn' => 'nullable|string|max:30',
            'title' => 'sometimes|string|max:255',
            'author' => 'nullable|string|max:255',
            'publisher' => 'nullable|string|max:255',
            'year' => 'nullable|integer|min:1000|max:2100',
            'language' => 'nullable|string|max:50',
            'pages' => 'nullable|integer|min:0',
            'shelf_code' => 'nullable|string|max:50',
            'description' => 'nullable|string',
            'remove_ebook' => 'nullable|boolean',
            'is_public_ebook' => 'nullable|boolean',
        ];
        return array_merge(
            $rules,
            FileUploadRules::rules(FileUploadRules::TYPE_IMAGE_ONLY, 2048, false, 'cover'),
            FileUploadRules::rules(FileUploadRules::TYPE_PDF_ONLY, 20480, false, 'ebook')
        );
    }

    public function messages(): array
    {
        return array_merge(
            FileUploadRules::messages(FileUploadRules::TYPE_IMAGE_ONLY, 2048, 'cover'),
            FileUploadRules::messages(FileUploadRules::TYPE_PDF_ONLY, 20480, 'ebook')
        );
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('remove_ebook')) {
            $this->merge([
                'remove_ebook' => filter_var($this->input('remove_ebook'), FILTER_VALIDATE_BOOLEAN),
            ]);
        }
        if ($this->has('is_public_ebook')) {
            $this->merge([
                'is_public_ebook' => filter_var($this->input('is_public_ebook'), FILTER_VALIDATE_BOOLEAN),
            ]);
        }
    }
}
