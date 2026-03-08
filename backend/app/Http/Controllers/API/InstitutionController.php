<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreInstitutionRequest;
use App\Http\Requests\UpdateInstitutionRequest;
use App\Http\Resources\InstitutionResource;
use App\Models\Institution;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class InstitutionController extends Controller
{
    /**
     * Display a listing of institutions (admin only).
     */
    public function index(Request $request)
    {
        try {
            $query = Institution::query();

            // Only admin or super admin can see all institutions
            $user = $request->user();
            if (!$user->isAdmin() && !$user->isSuperAdmin()) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('name', 'like', '%' . $search . '%')
                      ->orWhere('npsn', 'like', '%' . $search . '%');
                });
            }

            if ($request->has('level')) {
                $query->where('level', $request->level);
            }

            if ($request->has('type')) {
                $query->where('type', $request->type);
            }

            if ($request->has('is_active')) {
                $query->where('is_active', filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN));
            }

            $perPage = min($request->get('per_page', 15), 100); // Max 100 per page
            
            // Eager load relationships if requested
            $with = [];
            if ($request->has('with')) {
                $with = explode(',', $request->get('with'));
                $allowedRelations = ['users', 'students', 'teachers', 'changeRequests'];
                $with = array_intersect($with, $allowedRelations);
            }
            
            // Select all columns needed for list + detail so data filled by admin sekolah tampil di super admin
            $institutions = $query
                ->when(!empty($with), function ($q) use ($with) {
                    return $q->with($with);
                })
                ->orderBy('created_at', 'desc')
                ->paginate($perPage);

            return InstitutionResource::collection($institutions);
        } catch (\Exception $e) {
            Log::error('Failed to list institutions', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Terjadi kesalahan saat mengambil data institusi',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Store a newly created institution.
     */
    public function store(StoreInstitutionRequest $request)
    {
        try {
            $institution = Institution::create($request->validated());

            Log::info('Institution created', [
                'institution_id' => $institution->id,
                'user_id' => $request->user()->id,
            ]);

            return response()->json([
                'message' => 'Institusi berhasil dibuat',
                'data' => new InstitutionResource($institution),
            ], 201);
        } catch (\Exception $e) {
            Log::error('Failed to create institution', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Terjadi kesalahan saat membuat institusi',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Display the specified institution.
     */
    public function show(Request $request, $id)
    {
        try {
            // Eager load relationships if requested
            $with = ['users', 'students', 'teachers'];
            if ($request->has('with')) {
                $requestedWith = explode(',', $request->get('with'));
                $allowedRelations = ['users', 'students', 'teachers', 'changeRequests'];
                $with = array_intersect($requestedWith, $allowedRelations);
                if (empty($with)) {
                    $with = ['users', 'students', 'teachers'];
                }
            }
            
            $institution = Institution::with($with)->findOrFail($id);

            // Jika bukan admin/super admin, hanya bisa melihat institusi sendiri
            if (!$request->user()->isAdminOrSuperAdmin() && $request->user()->institution_id != $institution->id) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            return new InstitutionResource($institution);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Institusi tidak ditemukan',
            ], 404);
        } catch (\Exception $e) {
            Log::error('Failed to get institution', [
                'institution_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Terjadi kesalahan saat mengambil data institusi',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Update the specified institution.
     */
    public function update(UpdateInstitutionRequest $request, $id)
    {
        try {
            $institution = Institution::findOrFail($id);
            $user = $request->user();

            if (!$user) {
                return response()->json([
                    'message' => 'Unauthorized',
                ], 401);
            }

            // Hanya admin/super admin atau user dari institusi yang sama yang boleh mengubah
            if (!$user->isAdminOrSuperAdmin() && (int) $user->institution_id !== (int) $institution->id) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            $validated = $request->validated();

            // Check if user is trying to change name or npsn without super admin permission
            $isSuperAdmin = method_exists($user, 'isSuperAdmin') ? $user->isSuperAdmin() : false;
            
            if (!$isSuperAdmin) {
                if (isset($validated['name']) && $validated['name'] !== $institution->name) {
                    return response()->json([
                        'message' => 'Perubahan nama sekolah memerlukan persetujuan super admin. Silakan gunakan fitur request perubahan.',
                    ], 422);
                }
                
                if (isset($validated['npsn']) && $validated['npsn'] !== $institution->npsn) {
                    return response()->json([
                        'message' => 'Perubahan NPSN memerlukan persetujuan super admin. Silakan gunakan fitur request perubahan.',
                    ], 422);
                }
            }
            
            // Remove name, npsn, and is_active from update if user is not super admin
            // Hanya super admin yang boleh mengubah status aktif/nonaktif (ban instansi)
            if (!$isSuperAdmin) {
                unset($validated['name']);
                unset($validated['npsn']);
                unset($validated['is_active']);
            }

            $institution->update($validated);

            Log::info('Institution updated', [
                'institution_id' => $institution->id,
                'user_id' => $user->id,
            ]);

            return response()->json([
                'message' => 'Institusi berhasil diperbarui',
                'data' => new InstitutionResource($institution),
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Institusi tidak ditemukan',
            ], 404);
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('Failed to update institution', [
                'institution_id' => $id,
                'error' => $e->getMessage(),
            ]);

            $message = self::userFriendlyUpdateError($e, false);
            $payload = ['message' => $message];
            if (config('app.debug') && $e->getMessage()) {
                $payload['error'] = $e->getMessage();
            }
            return response()->json($payload, 500);
        }
    }

    /**
     * Pesan error yang ramah untuk user (profil/update), termasuk tip untuk hosting.
     */
    private static function userFriendlyUpdateError(\Throwable $e, bool $isUpload): string
    {
        if (config('app.debug') && $e->getMessage()) {
            return 'Terjadi kesalahan: ' . $e->getMessage();
        }
        $msg = $e->getMessage();
        $isStorage = (
            stripos($msg, 'Permission denied') !== false
            || stripos($msg, 'failed to open stream') !== false
            || stripos($msg, 'No such file or directory') !== false
            || stripos($msg, 'Unable to write') !== false
            || stripos($msg, 'Directory') !== false && stripos($msg, 'exist') !== false
        );
        if ($isUpload && $isStorage) {
            return 'Gagal menyimpan file. Pastikan di server: (1) Jalankan php artisan storage:link, (2) Folder storage/app/public dapat ditulis (permission).';
        }
        if ($isUpload) {
            return 'Gagal mengunggah file. Pastikan ukuran file sesuai (maks. 5MB) dan format JPG/PNG/GIF. Di hosting, cek juga batas upload PHP (upload_max_filesize, post_max_size minimal 6MB).';
        }
        return 'Gagal memperbarui profil. Jika di hosting, periksa: (1) Jalankan php artisan storage:link, (2) Izin tulis pada folder storage, (3) Batas upload PHP minimal 6MB. Silakan coba lagi.';
    }

    /**
     * Remove the specified institution.
     */
    public function destroy(Request $request, $id)
    {
        try {
            $institution = Institution::findOrFail($id);

            // Hanya super admin yang boleh menghapus instansi (untuk penindakan pelanggaran)
            if (!$request->user()->isSuperAdmin()) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            $institution->delete();

            Log::info('Institution deleted', [
                'institution_id' => $id,
                'user_id' => $request->user()->id,
            ]);

            return response()->json([
                'message' => 'Institusi berhasil dihapus',
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Institusi tidak ditemukan',
            ], 404);
        } catch (\Exception $e) {
            Log::error('Failed to delete institution', [
                'institution_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Terjadi kesalahan saat menghapus institusi',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Get current user's institution.
     */
    public function myInstitution(Request $request)
    {
        try {
            $user = $request->user();
            $institution = $user->institution;

            // Jika relasi null tapi user punya institution_id, coba load langsung (mis. relasi belum diload)
            if (!$institution && $user->institution_id) {
                $institution = Institution::find($user->institution_id);
            }

            // Fallback: admin sekolah yang mendaftar pertama kali — saat registrasi institution.email = user.email.
            // Jika institution_id di user kosong (bug/data lama), cari instansi berdasarkan email lalu perbaiki user.
            if (!$institution && $user->role === 'institution_admin' && $user->email) {
                $institution = Institution::where('email', $user->email)->first();
                if ($institution) {
                    $user->institution_id = $institution->id;
                    $user->save();
                }
            }

            if (!$institution) {
                $message = 'Institusi tidak ditemukan.';
                if ($user->role === 'institution_admin' && !$user->institution_id) {
                    $message = 'Akun admin sekolah belum terhubung ke instansi. Silakan hubungi administrator.';
                }
                return response()->json(['message' => $message], 404);
            }

            // Load active academic year and semester
            $institution->load(['activeAcademicYear', 'activeSemester']);

            return response()->json([
                'data' => new InstitutionResource($institution),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to get my institution', [
                'user_id' => $request->user()->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Terjadi kesalahan saat mengambil data institusi',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Update active academic year and semester for institution.
     */
    public function updateActiveAcademicYear(Request $request, $id)
    {
        try {
            $institution = Institution::findOrFail($id);
            $user = $request->user();

            if (!$user) {
                return response()->json([
                    'message' => 'Unauthorized',
                ], 401);
            }

            // Only institution admin/super admin can update their own institution
            if (!$user->isAdminOrSuperAdmin() && $user->institution_id != $institution->id) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            $request->validate([
                'active_academic_year_id' => 'required|exists:academic_years,id',
                'semester_name' => 'required|in:Ganjil,Genap',
            ]);

            $academicYearId = $request->active_academic_year_id;
            $semesterName = $request->semester_name;

            // Find or create semester
            $semester = \App\Models\Semester::where('academic_year_id', $academicYearId)
                ->where('name', $semesterName)
                ->first();

            if (!$semester) {
                // Auto-create semester if doesn't exist
                $academicYear = \App\Models\AcademicYear::findOrFail($academicYearId);
                
                // Calculate semester dates
                $startDate = \Carbon\Carbon::parse($academicYear->start_date);
                $endDate = \Carbon\Carbon::parse($academicYear->end_date);
                $totalDays = $startDate->diffInDays($endDate);
                $midDate = $startDate->copy()->addDays(floor($totalDays / 2));
                
                // Determine dates based on semester name
                if ($semesterName === 'Ganjil') {
                    $semesterStartDate = $startDate;
                    $semesterEndDate = $midDate;
                    $order = 1;
                } else {
                    $semesterStartDate = $midDate->copy()->addDay();
                    $semesterEndDate = $endDate;
                    $order = 2;
                }

                $semester = \App\Models\Semester::create([
                    'academic_year_id' => $academicYearId,
                    'name' => $semesterName,
                    'order' => $order,
                    'start_date' => $semesterStartDate->format('Y-m-d'),
                    'end_date' => $semesterEndDate->format('Y-m-d'),
                    'status' => 'Draft',
                    'description' => 'Semester ' . $semesterName . ' - Auto-generated'
                ]);
            }

            $institution->update([
                'active_academic_year_id' => $academicYearId,
                'active_semester_id' => $semester->id,
            ]);

            $institution->load(['activeAcademicYear', 'activeSemester']);

            Log::info('Institution active academic year updated', [
                'institution_id' => $institution->id,
                'user_id' => $user->id,
                'academic_year_id' => $institution->active_academic_year_id,
                'semester_id' => $institution->active_semester_id,
            ]);

            return response()->json([
                'message' => 'Tahun ajaran dan semester aktif berhasil diperbarui',
                'data' => new InstitutionResource($institution),
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Institusi tidak ditemukan',
            ], 404);
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('Failed to update active academic year', [
                'institution_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Terjadi kesalahan saat memperbarui tahun ajaran aktif',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Upload logo for institution.
     */
    public function uploadLogo(Request $request, $id)
    {
        try {
            $institution = Institution::findOrFail($id);
            $user = $request->user();

            if (!$user) {
                return \App\Helpers\ApiResponse::unauthorized();
            }

            // Only institution admin/super admin can upload logo
            if (!$user->isAdminOrSuperAdmin() && $user->institution_id != $institution->id) {
                return \App\Helpers\ApiResponse::forbidden();
            }

            $request->validate(
                \App\Helpers\FileUploadRules::institutionLogo(),
                \App\Helpers\FileUploadRules::messages(
                    \App\Helpers\FileUploadRules::TYPE_IMAGE_LOGO,
                    \App\Helpers\FileUploadRules::SIZE_SMALL,
                    'logo',
                    false
                )
            );

            $file = $request->file('logo');

            // Delete old logo if exists
            if ($institution->logo && Storage::disk('public')->exists($institution->logo)) {
                Storage::disk('public')->delete($institution->logo);
            }

            // Sanitasi nama file (mencegah path traversal)
            $fileName = \App\Helpers\FileUploadHelper::safeStorageName($file, 'institution_' . $institution->id);
            $filePath = $file->storeAs('institution_logos', $fileName, 'public');

            $institution->update(['logo' => $filePath]);

            Log::info('Institution logo uploaded', [
                'institution_id' => $institution->id,
                'user_id' => $user->id,
                'logo_path' => $filePath,
            ]);

            return response()->json([
                'message' => 'Logo berhasil diupload',
                'data' => new InstitutionResource($institution),
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return \App\Helpers\ApiResponse::notFound('Institusi tidak ditemukan');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return \App\Helpers\ApiResponse::validationFailed($e->errors());
        } catch (\Exception $e) {
            Log::error('Failed to upload logo', [
                'institution_id' => $id,
                'error' => $e->getMessage(),
            ]);
            $message = self::userFriendlyUpdateError($e, true);
            return response()->json([
                'message' => $message,
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Upload cover/hero image for institution (halaman publik sekolah).
     */
    public function uploadCoverImage(Request $request, $id)
    {
        try {
            $institution = Institution::findOrFail($id);
            $user = $request->user();

            if (!$user) {
                return \App\Helpers\ApiResponse::unauthorized();
            }

            if (!$user->isAdminOrSuperAdmin() && $user->institution_id != $institution->id) {
                return \App\Helpers\ApiResponse::forbidden();
            }

            $request->validate(
                \App\Helpers\FileUploadRules::institutionCoverImage(),
                \App\Helpers\FileUploadRules::messages(
                    \App\Helpers\FileUploadRules::TYPE_IMAGE_LOGO,
                    \App\Helpers\FileUploadRules::SIZE_MEDIUM,
                    'cover_image',
                    false
                )
            );

            $file = $request->file('cover_image');

            if ($institution->cover_image && Storage::disk('public')->exists($institution->cover_image)) {
                Storage::disk('public')->delete($institution->cover_image);
            }

            $fileName = \App\Helpers\FileUploadHelper::safeStorageName($file, 'institution_cover_' . $institution->id);
            $filePath = $file->storeAs('institution_covers', $fileName, 'public');

            $institution->update(['cover_image' => $filePath]);

            Log::info('Institution cover image uploaded', [
                'institution_id' => $institution->id,
                'user_id' => $user->id,
                'cover_path' => $filePath,
            ]);

            return response()->json([
                'message' => 'Gambar cover berhasil diupload',
                'data' => new InstitutionResource($institution),
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return \App\Helpers\ApiResponse::notFound('Institusi tidak ditemukan');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return \App\Helpers\ApiResponse::validationFailed($e->errors());
        } catch (\Exception $e) {
            Log::error('Failed to upload cover image', [
                'institution_id' => $id,
                'error' => $e->getMessage(),
            ]);
            $message = self::userFriendlyUpdateError($e, true);
            return response()->json([
                'message' => $message,
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Get logo for institution.
     */
    public function getLogo(Request $request, $id)
    {
        try {
            $institution = Institution::findOrFail($id);

            // Hanya admin/super admin atau user dari institusi yang sama yang boleh mengunduh logo
            $user = $request->user();
            if (!$user) {
                return response()->json(['message' => 'Unauthorized'], 401);
            }
            if (!$user->isAdminOrSuperAdmin() && (int) $user->institution_id !== (int) $institution->id) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            if (!$institution->logo) {
                return response()->json([
                    'message' => 'Logo tidak ditemukan',
                ], 404);
            }

            if (!Storage::disk('public')->exists($institution->logo)) {
                return response()->json([
                    'message' => 'File logo tidak ditemukan',
                ], 404);
            }

            return Storage::disk('public')->response($institution->logo);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Institusi tidak ditemukan',
            ], 404);
        } catch (\Exception $e) {
            Log::error('Failed to get logo', [
                'institution_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Terjadi kesalahan saat mengambil logo',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }
}
