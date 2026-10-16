<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Controllers\API\Concerns\ResolvesInstitution;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class QuestionAssetController extends Controller
{
    use ResolvesInstitution;

    /**
     * Upload media for question/stimulus body (rich text) or stimulus media blocks.
     * kind: image (default) | audio | video
     */
    public function uploadImage(Request $request): JsonResponse
    {
        $kind = strtolower((string) $request->input('kind', 'image'));
        if (! in_array($kind, ['image', 'audio', 'video'], true)) {
            return response()->json(['message' => 'Jenis file tidak didukung. Gunakan image, audio, atau video.'], 422);
        }

        $rules = match ($kind) {
            'audio' => ['file' => 'required|file|mimes:mp3,wav,ogg,m4a,mpeg|max:10240'],
            'video' => ['file' => 'required|file|mimes:mp4,webm,ogg|max:51200'],
            default => ['file' => 'required|file|mimes:jpg,jpeg,png,gif,webp|max:4096'],
        };

        $request->validate(array_merge($rules, [
            'kind' => ['nullable', Rule::in(['image', 'audio', 'video'])],
        ]), [
            'file.max' => match ($kind) {
                'audio' => 'Ukuran audio maksimal 10 MB.',
                'video' => 'Ukuran video maksimal 50 MB.',
                default => 'Ukuran gambar maksimal 4 MB.',
            },
            'file.mimes' => match ($kind) {
                'audio' => 'Format audio harus MP3, WAV, OGG, atau M4A.',
                'video' => 'Format video harus MP4, WebM, atau OGG.',
                default => 'Format gambar harus JPG, PNG, GIF, atau WebP.',
            },
        ]);

        $institutionId = $this->resolveInstitutionId($request);
        if (! $institutionId) {
            return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
        }

        $file = $request->file('file');
        $path = $file->store(
            'question_assets/' . $institutionId . '/' . date('Y/m'),
            'public'
        );

        $url = Storage::url($path);
        $fullUrl = $request->getSchemeAndHttpHost() . $url;

        return response()->json([
            'location' => $fullUrl,
            'url' => $fullUrl,
            'path' => $path,
            'kind' => $kind,
            'mime' => $file->getMimeType(),
            'original_name' => $file->getClientOriginalName(),
        ]);
    }
}
