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
     * Get app branding (logo & favicon URLs). Public, no auth.
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
                ],
            ]);
        }

        return response()->json([
            'data' => [
                'app_logo_url' => $branding->app_logo_url,
                'favicon_url' => $branding->favicon_url,
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
}
