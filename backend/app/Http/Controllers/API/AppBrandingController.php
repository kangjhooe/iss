<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\AppBranding;
use App\Helpers\FileUploadRules;
use App\Helpers\FileUploadHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class AppBrandingController extends Controller
{
    /**
     * Get app branding (logo, favicon, hero). Public, no auth.
     * Digunakan di halaman awal, login, register; tidak mengubah logo institusi.
     */
    public function show()
    {
        if (!Schema::hasTable('app_branding')) {
            return response()->json([
                'data' => $this->defaultBrandingData(),
            ]);
        }

        $branding = AppBranding::first();

        if (!$branding) {
            return response()->json([
                'data' => $this->defaultBrandingData(),
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
                'maintenance_mode' => (bool) ($branding->maintenance_mode ?? false),
                'maintenance_message' => $branding->maintenance_message,
            ],
        ]);
    }

    /**
     * Update maintenance mode. Hanya super admin.
     */
    public function updateMaintenance(Request $request)
    {
        try {
            if (!$request->user()?->isSuperAdmin()) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            $validated = $request->validate([
                'maintenance_mode' => 'required|boolean',
                'maintenance_message' => 'nullable|string|max:1000',
            ]);

            $branding = AppBranding::firstOrCreate([], [
                'app_logo' => null,
                'favicon' => null,
                'maintenance_mode' => false,
            ]);

            $old = [
                'maintenance_mode' => (bool) $branding->maintenance_mode,
                'maintenance_message' => $branding->maintenance_message,
            ];

            $branding->update([
                'maintenance_mode' => $validated['maintenance_mode'],
                'maintenance_message' => $validated['maintenance_message'] ?? $branding->maintenance_message,
            ]);

            \App\Models\AuditLog::logManual(
                $request,
                'maintenance.updated',
                AppBranding::class,
                $branding->id,
                $old,
                [
                    'maintenance_mode' => (bool) $branding->maintenance_mode,
                    'maintenance_message' => $branding->maintenance_message,
                ]
            );

            Log::info('Maintenance mode updated', [
                'maintenance_mode' => $branding->maintenance_mode,
                'user_id' => $request->user()->id,
            ]);

            return response()->json([
                'message' => $branding->maintenance_mode
                    ? 'Mode pemeliharaan diaktifkan'
                    : 'Mode pemeliharaan dinonaktifkan',
                'data' => [
                    'maintenance_mode' => (bool) $branding->maintenance_mode,
                    'maintenance_message' => $branding->maintenance_message,
                ],
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('Maintenance update failed', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Gagal memperbarui mode pemeliharaan',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
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

    private function defaultBrandingData(): array
    {
        return [
            'app_logo_url' => null,
            'favicon_url' => null,
            'hero_headline' => null,
            'hero_subheadline' => null,
            'hero_image_url' => null,
            'hero_primary_cta_text' => null,
            'hero_primary_cta_to' => null,
            'hero_secondary_cta_text' => null,
            'hero_secondary_cta_to' => null,
            'maintenance_mode' => false,
            'maintenance_message' => null,
        ];
    }
}
