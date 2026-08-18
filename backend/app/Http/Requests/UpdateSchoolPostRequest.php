<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSchoolPostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => 'sometimes|in:news,gallery',
            'title' => 'sometimes|string|max:255',
            'slug' => 'nullable|string|max:255',
            'body' => 'nullable|string',
            'cover' => 'nullable|image|max:5120',
            'published_at' => 'nullable|date',
            'is_published' => 'nullable|boolean',
            'sort' => 'nullable|integer|min:0',
            'gallery' => 'nullable|array',
            'gallery.*' => 'image|max:5120',
        ];
    }
}
