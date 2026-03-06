<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\AppBranding;
use App\Helpers\FileUploadRules;
use App\Helpers\FileUploadHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class AppBrandingController extends Controller
{
    /**
     * Get app branding (logo, favicon, hero). Public, no auth.
     * Digunakan di halaman awal, login, register; tidak mengubah logo institusi.
     */
    public function show()
    {
        $branding = AppBranding::first();

        if (!$branding) {
            return response()->json([
                'data' => [
                    'app_logo_url' => null,
                    'favicon_url' => null,
                    'hero_headline' => null,
                    'hero_subheadline' => null,
                    'hero_image_url' => null,
                    'hero_primary_cta_text' => null,
                    'hero_primary_cta_to' => null,
                    'hero_secondary_cta_text' => null,
                    'hero_secondary_cta_to' => null,
                ],
            ]);
        }

        return response()->json([
            'data' => [
                'app_logo_url' => $branding->app_logo_url,
                'favicon_url' => $branding->favicon_url,
                'hero_headline' => $branding->hero_headline,
                'hero_subheadline' => $branding->hero_subheadline,
                'hero_image_url' => $branding->hero_image_url,
                'hero_primary_cta_text' => $branding->hero_primary_cta_text,
                'hero_primary_cta_to' => $branding->hero_primary_cta_to,
                'hero_secondary_cta_text' => $branding->hero_secondary_cta_text,
                'hero_secondary_cta_to' => $branding->hero_secondary_cta_to,
            ],
        ]);
    }

    /**
     * Upload app logo. Hanya super admin.
     */
    public function uploadLogo(Request $request)
    {
        try {
            $request->validate(
                FileUploadRules::appLogo(),
                FileUploadRules::messages(
                    \App\Helpers\FileUploadRules::TYPE_IMAGE_LOGO,
                    \App\Helpers\FileUploadRules::SIZE_SMALL,
                    'logo',
                    false
                )
            );

            $file = $request->file('logo');
            $branding = AppBranding::firstOrCreate([], ['app_logo' => null, 'favicon' => null]);

            if ($branding->app_logo && Storage::disk('public')->exists($branding->app_logo)) {
                Storage::disk('public')->delete($branding->app_logo);
            }

            $fileName = FileUploadHelper::safeStorageName($file, 'app_logo');
            $filePath = $file->storeAs('app_branding', $fileName, 'public');

            $branding->update(['app_logo' => $filePath]);

            Log::info('App logo uploaded');

            return response()->json([
                'message' => 'Logo aplikasi berhasil diunggah',
                'data' => [
                    'app_logo_url' => $branding->fresh()->app_logo_url,
                ],
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return \App\Helpers\ApiResponse::validationFailed($e->errors());
        } catch (\Exception $e) {
            Log::error('App logo upload failed', ['error' => $e->getMessage()]);
            return \App\Helpers\ApiResponse::serverError('Gagal mengunggah logo aplikasi', $e->getMessage());
        }
    }

    /**
     * Upload favicon. Hanya super admin.
     */
    public function uploadFavicon(Request $request)
    {
        try {
            $request->validate(
                FileUploadRules::appFavicon(),
                FileUploadRules::messages(
                    \App\Helpers\FileUploadRules::TYPE_FAVICON,
                    512,
                    'favicon',
                    false
                )
            );

            $file = $request->file('favicon');
            $branding = AppBranding::firstOrCreate([], ['app_logo' => null, 'favicon' => null]);

            if ($branding->favicon && Storage::disk('public')->exists($branding->favicon)) {
                Storage::disk('public')->delete($branding->favicon);
            }

            $fileName = FileUploadHelper::safeStorageName($file, 'favicon');
            $filePath = $file->storeAs('app_branding', $fileName, 'public');

            $branding->update(['favicon' => $filePath]);

            Log::info('Favicon uploaded');

            return response()->json([
                'message' => 'Favicon berhasil diunggah',
                'data' => [
                    'favicon_url' => $branding->fresh()->favicon_url,
                ],
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return \App\Helpers\ApiResponse::validationFailed($e->errors());
        } catch (\Exception $e) {
            Log::error('Favicon upload failed', ['error' => $e->getMessage()]);
            return \App\Helpers\ApiResponse::serverError('Gagal mengunggah favicon', $e->getMessage());
        }
    }

    /**
     * Update teks hero halaman awal. Hanya super admin.
     */
    public function updateHero(Request $request)
    {
        try {
            $validated = $request->validate([
                'hero_headline' => 'nullable|string|max:255',
                'hero_subheadline' => 'nullable|string|max:5000',
                'hero_primary_cta_text' => 'nullable|string|max:100',
                'hero_primary_cta_to' => 'nullable|string|max:255',
                'hero_secondary_cta_text' => 'nullable|string|max:100',
                'hero_secondary_cta_to' => 'nullable|string|max:255',
            ]);

            $branding = AppBranding::firstOrCreate([], [
                'app_logo' => null,
                'favicon' => null,
                'hero_headline' => null,
                'hero_subheadline' => null,
                'hero_image' => null,
                'hero_primary_cta_text' => null,
                'hero_primary_cta_to' => null,
                'hero_secondary_cta_text' => null,
                'hero_secondary_cta_to' => null,
            ]);

            $branding->update($validated);

            return response()->json([
                'message' => 'Hero halaman awal berhasil disimpan',
                'data' => [
                    'hero_headline' => $branding->hero_headline,
                    'hero_subheadline' => $branding->hero_subheadline,
                    'hero_image_url' => $branding->hero_image_url,
                    'hero_primary_cta_text' => $branding->hero_primary_cta_text,
                    'hero_primary_cta_to' => $branding->hero_primary_cta_to,
                    'hero_secondary_cta_text' => $branding->hero_secondary_cta_text,
                    'hero_secondary_cta_to' => $branding->hero_secondary_cta_to,
                ],
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return \App\Helpers\ApiResponse::validationFailed($e->errors());
        } catch (\Exception $e) {
            Log::error('Hero update failed', ['error' => $e->getMessage()]);
            return \App\Helpers\ApiResponse::serverError('Gagal menyimpan hero', $e->getMessage());
        }
    }

    /**
     * Upload gambar hero halaman awal. Hanya super admin.
     */
    public function uploadHeroImage(Request $request)
    {
        try {
            $request->validate(
                FileUploadRules::appHeroImage(),
                FileUploadRules::messages(
                    \App\Helpers\FileUploadRules::TYPE_IMAGE_LOGO,
                    \App\Helpers\FileUploadRules::SIZE_SMALL,
                    'hero_image',
                    false
                )
            );

            $file = $request->file('hero_image');
            $branding = AppBranding::firstOrCreate([], ['app_logo' => null, 'favicon' => null]);

            if ($branding->hero_image && Storage::disk('public')->exists($branding->hero_image)) {
                Storage::disk('public')->delete($branding->hero_image);
            }

            $fileName = FileUploadHelper::safeStorageName($file, 'hero_image');
            $filePath = $file->storeAs('app_branding', $fileName, 'public');

            $branding->update(['hero_image' => $filePath]);

            Log::info('Hero image uploaded');

            return response()->json([
                'message' => 'Gambar hero berhasil diunggah',
                'data' => [
                    'hero_image_url' => $branding->fresh()->hero_image_url,
                ],
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return \App\Helpers\ApiResponse::validationFailed($e->errors());
        } catch (\Exception $e) {
            Log::error('Hero image upload failed', ['error' => $e->getMessage()]);
            return \App\Helpers\ApiResponse::serverError('Gagal mengunggah gambar hero', $e->getMessage());
        }
    }
}
