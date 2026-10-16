<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreQuestionStimulusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $isCreate = $this->isMethod('POST');

        return [
            'bank_soal_id' => $isCreate ? 'required|exists:bank_soal,id' : 'prohibited',
            'title' => 'nullable|string|max:255',
            'content' => 'nullable|string|max:50000',
            'type' => 'nullable|in:text,image,audio,video,youtube,mixed',
            'attachment_path' => 'nullable|string|max:500',
            'media' => 'nullable|array|max:20',
            'media.*.type' => 'required_with:media|in:image,audio,video,youtube',
            'media.*.url' => 'required_with:media|string|max:2000',
            'media.*.caption' => 'nullable|string|max:255',
            'media.*.video_id' => 'nullable|string|max:32',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $content = trim((string) $this->input('content', ''));
            $media = $this->input('media', []);
            $hasMedia = is_array($media) && count($media) > 0;
            if ($content === '' && ! $hasMedia) {
                $validator->errors()->add('content', 'Konten atau media stimulus wajib diisi.');
            }
        });
    }
}
