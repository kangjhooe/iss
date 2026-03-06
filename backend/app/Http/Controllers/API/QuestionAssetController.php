<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Controllers\API\Concerns\ResolvesInstitution;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class QuestionAssetController extends Controller
{
    use ResolvesInstitution;

    /**
     * Upload image for question/option body (rich text). Returns URL for TinyMCE/Quill.
     */
    public function uploadImage(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:jpg,jpeg,png,gif|max:2048',
        ]);

        $institutionId = $this->resolveInstitutionId($request);
        if (!$institutionId) {
            return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
        }

        $file = $request->file('file');
        $path = $file->store(
            'question_assets/' . $institutionId . '/' . date('Y/m'),
            'public'
        );

        $url = Storage::url($path);
        // Full URL for frontend (if app uses absolute URLs for img src)
        $fullUrl = $request->getSchemeAndHttpHost() . $url;

        return response()->json(['location' => $fullUrl]);
    }
}
