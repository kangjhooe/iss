<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Middleware\AddTokenFromCookie;
use App\Http\Requests\ForgotPasswordRequest;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RefreshTokenRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\ResetPasswordRequest;
use App\Http\Requests\UpdateProfileRequest;
use App\Http\Requests\ChangePasswordRequest;
use App\Http\Resources\UserResource;
use App\Models\Institution;
use App\Models\User;
use App\Services\MonetizationService;
use App\Support\InstitutionContext;
use App\Notifications\ResetPasswordNotification;
use App\Notifications\VerifyEmailNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Buat cookie untuk access token (httpOnly, aman dari XSS).
     */
    private function makeAuthCookie(string $token): \Symfony\Component\HttpFoundation\Cookie
    {
        $minutes = 24 * 60; // 24 jam
        $secure = request()->secure();
        return Cookie::make(
            AddTokenFromCookie::COOKIE_AUTH,
            $token,
            $minutes,
            '/',
            env('COOKIE_DOMAIN'), // null = current host (localhost / api domain)
            $secure,
            true,  // httpOnly
            false,
            'lax'
        );
    }

    private function makeActiveInstitutionCookie(int $institutionId): \Symfony\Component\HttpFoundation\Cookie
    {
        $minutes = 30 * 24 * 60;
        $secure = request()->secure();
        return Cookie::make(
            InstitutionContext::COOKIE_ACTIVE_INSTITUTION,
            (string) $institutionId,
            $minutes,
            '/',
            env('COOKIE_DOMAIN'),
            $secure,
            true,
            false,
            'lax'
        );
    }

    private function clearActiveInstitutionCookie(): \Symfony\Component\HttpFoundation\Cookie
    {
        $secure = request()->secure();
        return Cookie::make(
            InstitutionContext::COOKIE_ACTIVE_INSTITUTION,
            '',
            -1,
            '/',
            env('COOKIE_DOMAIN'),
            $secure,
            true,
            false,
            'lax'
        );
    }

    /**
     * Buat cookie untuk refresh token (httpOnly).
     */
    private function makeRefreshCookie(string $token): \Symfony\Component\HttpFoundation\Cookie
    {
        $minutes = 30 * 24 * 60; // 30 hari
        $secure = request()->secure();
        return Cookie::make(
            AddTokenFromCookie::COOKIE_REFRESH,
            $token,
            $minutes,
            '/',
            env('COOKIE_DOMAIN'), // null = current host (localhost / api domain)
            $secure,
            true,  // httpOnly
            false,
            'lax'
        );
    }

    /**
     * Cookie untuk menghapus auth_token dan refresh_token (expire di masa lalu).
     */
    private function clearAuthCookies(): array
    {
        $secure = request()->secure();
        $domain = env('COOKIE_DOMAIN');
        return [
            Cookie::make(AddTokenFromCookie::COOKIE_AUTH, '', -1, '/', $domain, $secure, true, false, 'lax'),
            Cookie::make(AddTokenFromCookie::COOKIE_REFRESH, '', -1, '/', $domain, $secure, true, false, 'lax'),
            Cookie::make(AddTokenFromCookie::COOKIE_IMPERSONATOR, '', -1, '/', $domain, $secure, true, false, 'lax'),
            Cookie::make(AddTokenFromCookie::COOKIE_IMPERSONATOR_REFRESH, '', -1, '/', $domain, $secure, true, false, 'lax'),
            Cookie::make(InstitutionContext::COOKIE_ACTIVE_INSTITUTION, '', -1, '/', $domain, $secure, true, false, 'lax'),
        ];
    }

    private function isMaintenanceEnabled(): bool
    {
        try {
            if (!\Illuminate\Support\Facades\Schema::hasTable('app_branding')
                || !\Illuminate\Support\Facades\Schema::hasColumn('app_branding', 'maintenance_mode')) {
                return false;
            }
            return (bool) \App\Models\AppBranding::query()->value('maintenance_mode');
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function maintenanceMessage(): string
    {
        try {
            $msg = \App\Models\AppBranding::query()->value('maintenance_message');
            if (is_string($msg) && trim($msg) !== '') {
                return $msg;
            }
        } catch (\Throwable $e) {
            // ignore
        }
        return 'Sistem sedang dalam mode pemeliharaan. Silakan coba lagi nanti.';
    }

    /**
     * Register a new institution and admin user.
     *
     * @OA\Post(
     *     path="/api/v1/register",
     *     summary="Registrasi institusi dan admin",
     *     tags={"Authentication"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"npsn","institution_name","name","email","phone","password","password_confirmation"},
     *             @OA\Property(property="npsn", type="string", example="12345678"),
     *             @OA\Property(property="institution_name", type="string", example="Sekolah Contoh"),
     *             @OA\Property(property="name", type="string", example="Admin Sekolah"),
     *             @OA\Property(property="email", type="string", format="email", example="admin@sekolah.id"),
     *             @OA\Property(property="phone", type="string", example="081234567890"),
     *             @OA\Property(property="password", type="string", format="password", example="Password123!"),
     *             @OA\Property(property="password_confirmation", type="string", format="password", example="Password123!")
     *         )
     *     ),
     *     @OA\Response(response=201, description="Registrasi berhasil",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Registrasi berhasil. Silakan cek email untuk verifikasi."),
     *             @OA\Property(property="user", type="object"),
     *             @OA\Property(property="token", type="string", example="1|..."),
     *             @OA\Property(property="refresh_token", type="string", example="2|...")
     *         )
     *     ),
     *     @OA\Response(response=422, description="Validasi gagal")
     * )
     */
    public function register(RegisterRequest $request)
    {
        try {
            $validated = $request->validated();

            DB::beginTransaction();

            $institution = \App\Models\Institution::create([
                'npsn' => $validated['npsn'],
                'name' => $validated['institution_name'],
                'phone' => $validated['phone'],
                'email' => $validated['email'], // Set email dari registrasi
                'is_active' => true,
            ]);

            // Set email_verified_at agar user bisa login lagi setelah logout (tanpa wajib klik link verifikasi)
            $user = User::create([
                'institution_id' => $institution->id,
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'role' => 'institution_admin',
                'email_verified_at' => now(),
            ]);

            // Create tokens
            $accessToken = $user->createToken('auth_token')->plainTextToken;
            $refreshToken = $user->createToken('refresh_token', ['refresh'])->plainTextToken;

            // Send email verification only if not in development
            if (!$this->shouldSkipEmailVerification()) {
                $verificationToken = Str::random(64);
                $frontendUrl = config('frontend.url');
                $verificationUrl = $frontendUrl . '/verify-email?token=' . $verificationToken . '&email=' . urlencode($user->email);
                
                // Store verification token (you might want to create a separate table for this)
                // For now, we'll use a simple approach with cache or database
                cache()->put('email_verification_' . $user->id, $verificationToken, now()->addHours(24));
                
                try {
                    $user->notify(new VerifyEmailNotification($verificationUrl));
                } catch (\Exception $e) {
                    Log::warning('Failed to send verification email', [
                        'user_id' => $user->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            DB::commit();

            Log::info('New institution registered', [
                'institution_id' => $institution->id,
                'user_id' => $user->id,
            ]);

            $response = response()->json([
                'message' => 'Registrasi berhasil. Silakan cek email untuk verifikasi.',
                'user' => new UserResource($user->load(['institution', 'permissions'])),
                'token' => $accessToken,
                'refresh_token' => $refreshToken,
            ], 201);
            $response->cookie($this->makeAuthCookie($accessToken));
            $response->cookie($this->makeRefreshCookie($refreshToken));
            return $response;
        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            throw $e;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Registration failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'message' => 'Terjadi kesalahan saat registrasi',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Login user.
     *
     * @OA\Post(
     *     path="/api/v1/login",
     *     summary="Login pengguna",
     *     tags={"Authentication"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email","password"},
     *             @OA\Property(property="email", type="string", format="email", example="admin@sekolah.id"),
     *             @OA\Property(property="password", type="string", format="password", example="Password123!")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Login berhasil",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Login berhasil"),
     *             @OA\Property(property="user", type="object"),
     *             @OA\Property(property="token", type="string", example="1|..."),
     *             @OA\Property(property="refresh_token", type="string", example="2|...")
     *         )
     *     ),
     *     @OA\Response(response=422, description="Kredensial salah atau email belum diverifikasi")
     * )
     */
    public function login(LoginRequest $request)
    {
        try {
            $validated = $request->validated();
            $login = (string) $validated['login'];
            $loginField = 'login';

            if (preg_match('/^\d{16}$/', $login)) {
                $user = User::where('login_nik', $login)->where('role', 'student')->first();
            } else {
                $user = User::where('email', $login)->first();
                $loginField = 'email';
            }

            // Check if account is deactivated by admin
            if ($user && $user->is_active === false) {
                throw ValidationException::withMessages([
                    $loginField => ['Akun Anda dinonaktifkan. Silakan hubungi administrator.'],
                ]);
            }

            // Maintenance mode: only super_admin may login
            if ($user && !$user->isSuperAdmin() && $this->isMaintenanceEnabled()) {
                throw ValidationException::withMessages([
                    $loginField => [$this->maintenanceMessage()],
                ]);
            }

            // Check if account is locked
            if ($user && $user->isLocked()) {
                $minutesRemaining = $user->lockedMinutesRemaining();
                throw ValidationException::withMessages([
                    $loginField => ["Akun Anda terkunci. Silakan coba lagi dalam {$minutesRemaining} menit."],
                ]);
            }

            $passwordValid = false;
            try {
                $passwordValid = $user && Hash::check($validated['password'], $user->password);
            } catch (\RuntimeException $e) {
                Log::error('Password hash algorithm error on login', [
                    'login' => preg_match('/^\d{16}$/', $login) ? '[NIK]' : $login,
                    'error' => $e->getMessage(),
                ]);
            }

            if (!$passwordValid) {
                if ($user) {
                    $user->incrementFailedLoginAttempts();
                }
                Log::warning('Failed login attempt', [
                    'login' => preg_match('/^\d{16}$/', $login) ? '[NIK]' : $login,
                ]);
                throw ValidationException::withMessages([
                    $loginField => ['Kredensial yang diberikan salah.'],
                ]);
            }

            // Auto-verify email in development if not verified
            if ($this->shouldSkipEmailVerification() && !$user->isEmailVerified()) {
                $user->update(['email_verified_at' => now()]);
                Log::info('Auto-verified user email in development', ['user_id' => $user->id]);
            }
            
            // Students use NIK login (may have synthetic email) — skip email verification
            $skipEmailVerification = $this->shouldSkipEmailVerification() || $user->isStudent() || $user->isParent();
            if (!$skipEmailVerification && !$user->isEmailVerified()) {
                throw ValidationException::withMessages([
                    $loginField => ['Email Anda belum diverifikasi. Silakan cek email untuk link verifikasi.'],
                ]);
            }

            // Check if user's institution is active (skip for super admin)
            // Students may have institution only via student_profile
            if (!$user->isSuperAdmin()) {
                $institution = $user->institution;
                if (!$institution && $user->isStudent()) {
                    $institution = $user->studentProfile?->institution;
                }
                if ($institution && !$institution->is_active) {
                    throw ValidationException::withMessages([
                        $loginField => ['Akun institusi Anda tidak aktif. Silakan hubungi administrator.'],
                    ]);
                }
            }

            // Reset failed login attempts on successful login
            $user->resetFailedLoginAttempts();

            // Create tokens
            $accessToken = $user->createToken('auth_token')->plainTextToken;
            $refreshToken = $user->createToken('refresh_token', ['refresh'])->plainTextToken;

            Log::info('User logged in', ['user_id' => $user->id]);

            // Load relationships safely
            try {
                $user->load(['institution']);
                // Try to load permissions, but don't fail if table doesn't exist
                try {
                    $user->load(['permissions']);
                } catch (\Exception $e) {
                    Log::warning('Could not load permissions', [
                        'user_id' => $user->id,
                        'error' => $e->getMessage()
                    ]);
                }
            } catch (\Exception $e) {
                Log::error('Error loading user relationships', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage()
                ]);
            }

            if ($user->role === 'teacher' || $user->role === 'staff') {
                try {
                    $user->load(['teacherProfile']);
                } catch (\Exception $e) {
                    // ignore
                }
            }

            if ($user->isStudent()) {
                try {
                    $user->load(['studentProfile.schoolClass', 'studentProfile.institution']);
                } catch (\Exception $e) {
                    // ignore
                }
            }

            InstitutionContext::applyToRequest($request, $user);
            $userPayload = (new UserResource($user))->resolve();
            $userPayload = array_merge($userPayload, $this->institutionContextPayload($user, $request));

            $response = response()->json([
                'message' => 'Login berhasil',
                'user' => $userPayload,
                'token' => $accessToken,
                'refresh_token' => $refreshToken,
                'must_change_password' => (bool) $user->must_change_password,
            ]);
            $response->cookie($this->makeAuthCookie($accessToken));
            $response->cookie($this->makeRefreshCookie($refreshToken));
            if (!empty($userPayload['active_institution_id'])) {
                $response->cookie($this->makeActiveInstitutionCookie((int) $userPayload['active_institution_id']));
            }
            return $response;
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('Login failed', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Terjadi kesalahan saat login',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Logout user.
     */
    public function logout(Request $request)
    {
        try {
            $user = $request->user();
            if ($user) {
                $user->currentAccessToken()->delete();
                Log::info('User logged out', ['user_id' => $user->id]);
            }

            $response = response()->json([
                'message' => 'Logout berhasil',
            ]);
            foreach ($this->clearAuthCookies() as $cookie) {
                $response->cookie($cookie);
            }
            return $response;
        } catch (\Exception $e) {
            Log::error('Logout failed', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Terjadi kesalahan saat logout',
            ], 500);
        }
    }

    /**
     * Get authenticated user.
     */
    public function me(Request $request)
    {
        try {
            $user = $request->user();
            $loads = ['institution', 'permissions'];
            if ($user->role === 'student') {
                $loads[] = 'studentProfile.schoolClass';
                $loads[] = 'studentProfile.institution';
            }
            if ($user->role === 'teacher' || $user->role === 'staff') {
                $loads[] = 'teacherProfile';
            }

            InstitutionContext::applyToRequest($request, $user);

            $userPayload = (new UserResource($user->load($loads)))->resolve();
            $userPayload['impersonation'] = $this->resolveImpersonationMeta($request);
            $userPayload = array_merge($userPayload, $this->institutionContextPayload($user, $request));

            return response()->json([
                'user' => $userPayload,
            ]);
        } catch (\Exception $e) {
            Log::error('Get user failed', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Terjadi kesalahan saat mengambil data user',
            ], 500);
        }
    }

    /**
     * Switch active institution context (induk / non-induk).
     */
    public function switchInstitution(Request $request)
    {
        try {
            $user = $request->user();
            $validated = $request->validate([
                'institution_id' => 'required|integer|exists:institution,id',
            ]);

            $institutionId = (int) $validated['institution_id'];
            if (!InstitutionContext::canAccessInstitution($user, $institutionId)) {
                return response()->json([
                    'message' => 'Anda tidak memiliki akses ke sekolah ini',
                ], 403);
            }

            InstitutionContext::forceActiveInstitution($request, $user, $institutionId);

            $loads = ['institution', 'permissions'];
            if ($user->role === 'teacher' || $user->role === 'staff') {
                $loads[] = 'teacherProfile';
            }
            if ($user->role === 'student') {
                $loads[] = 'studentProfile.schoolClass';
                $loads[] = 'studentProfile.institution';
            }

            $userPayload = (new UserResource($user->load($loads)))->resolve();
            $userPayload['impersonation'] = $this->resolveImpersonationMeta($request);
            $userPayload = array_merge($userPayload, $this->institutionContextPayload($user, $request));

            $response = response()->json([
                'message' => 'Konteks sekolah berhasil diganti',
                'user' => $userPayload,
            ]);
            $response->cookie($this->makeActiveInstitutionCookie($institutionId));

            return $response;
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('Switch institution failed', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Terjadi kesalahan saat mengganti sekolah',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    private function institutionContextPayload(User $user, Request $request): array
    {
        $available = InstitutionContext::availableInstitutions($user);
        $activeId = InstitutionContext::resolveActiveInstitutionId($user, $request);
        $active = $available->firstWhere('id', $activeId);

        $activeAcademicYear = null;
        $activeSemester = null;
        $institution = null;
        if ($activeId) {
            $institution = Institution::query()
                ->with([
                    'activeAcademicYear:id,code,name',
                    'activeSemester:id,name,order,academic_year_id',
                    'subscription.plan',
                    'addonGrants',
                ])
                ->find($activeId);
            if ($institution?->activeAcademicYear) {
                $activeAcademicYear = [
                    'id' => $institution->activeAcademicYear->id,
                    'code' => $institution->activeAcademicYear->code,
                    'name' => $institution->activeAcademicYear->name,
                ];
            }
            if ($institution?->activeSemester) {
                $activeSemester = [
                    'id' => $institution->activeSemester->id,
                    'name' => $institution->activeSemester->name,
                    'order' => $institution->activeSemester->order,
                ];
            }
        }

        $monetizationFeatures = app(MonetizationService::class)->featuresForInstitution($institution);

        return [
            'available_institutions' => $available->values()->all(),
            'active_institution_id' => $activeId,
            'active_affiliation' => InstitutionContext::affiliationFor($user, $activeId),
            'active_institution' => $active ? [
                'id' => $active['id'],
                'name' => $active['name'],
                'npsn' => $active['npsn'] ?? null,
                'is_demo' => (bool) ($active['is_demo'] ?? false),
                'affiliation' => $active['affiliation'],
                'active_academic_year_id' => $activeAcademicYear['id'] ?? null,
                'active_semester_id' => $activeSemester['id'] ?? null,
                'active_academic_year' => $activeAcademicYear,
                'active_semester' => $activeSemester,
                'monetization' => $monetizationFeatures,
            ] : null,
            // Shortcut global: sekolah memakai ini untuk menyembunyikan menu billing/add-on
            'monetization' => $monetizationFeatures,
        ];
    }

    /**
     * Meta impersonation dari cookie impersonator_token (jika valid).
     */
    private function resolveImpersonationMeta(Request $request): array
    {
        $inactive = [
            'active' => false,
            'admin_id' => null,
            'admin_name' => null,
            'admin_email' => null,
        ];

        $token = $request->cookie(AddTokenFromCookie::COOKIE_IMPERSONATOR);
        if (!$token) {
            return $inactive;
        }

        $tokenModel = \Laravel\Sanctum\PersonalAccessToken::findToken($token);
        $admin = $tokenModel?->tokenable;
        if (!$admin instanceof User || !$admin->isSuperAdmin()) {
            return $inactive;
        }

        return [
            'active' => true,
            'admin_id' => $admin->id,
            'admin_name' => $admin->name,
            'admin_email' => $admin->email,
        ];
    }

    /**
     * Update authenticated user profile (name, email).
     */
    public function updateProfile(UpdateProfileRequest $request)
    {
        try {
            $user = $request->user();
            $validated = $request->validated();

            $updateData = [
                'name' => $validated['name'],
                'email' => $validated['email'],
            ];

            // Jika email berubah, reset verifikasi email agar pemilik email baru dapat memverifikasi
            if (strtolower($user->email) !== strtolower($validated['email'])) {
                $updateData['email_verified_at'] = null;
            }

            $user->update($updateData);

            $loads = ['institution', 'permissions'];
            if ($user->role === 'student') {
                $loads[] = 'studentProfile.schoolClass';
                $loads[] = 'studentProfile.institution';
            }
            if ($user->role === 'teacher' || $user->role === 'staff') {
                $loads[] = 'teacherProfile';
            }

            Log::info('User profile updated', ['user_id' => $user->id]);

            return response()->json([
                'message' => 'Profil berhasil diperbarui',
                'user' => new UserResource($user->load($loads)),
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('Update profile failed', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Terjadi kesalahan saat memperbarui profil',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Change authenticated user password.
     */
    public function changePassword(ChangePasswordRequest $request)
    {
        try {
            $user = $request->user();
            $validated = $request->validated();

            if (!Hash::check($validated['current_password'], $user->password)) {
                throw ValidationException::withMessages([
                    'current_password' => ['Sandi saat ini salah.'],
                ]);
            }

            $user->forceFill([
                'password' => $validated['password'],
                'must_change_password' => false,
            ])->save();

            $user->resetFailedLoginAttempts();

            Log::info('User password changed', ['user_id' => $user->id]);

            return response()->json([
                'message' => 'Sandi berhasil diubah. Silakan gunakan sandi baru untuk login berikutnya.',
                'user' => new UserResource($user->fresh(['institution', 'permissions'])),
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('Change password failed', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Terjadi kesalahan saat mengubah sandi',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Send password reset link.
     */
    public function forgotPassword(ForgotPasswordRequest $request)
    {
        try {
            $validated = $request->validated();
            $user = User::where('email', $validated['email'])->first();

            if (!$user) {
                // Return success even if user doesn't exist (security best practice)
                return response()->json([
                    'message' => 'Jika email terdaftar, link reset password telah dikirim.',
                ]);
            }

            // Generate reset token
            $token = Str::random(64);
            
            // Store token in password_reset_tokens table
            DB::table('password_reset_tokens')->updateOrInsert(
                ['email' => $user->email],
                [
                    'token' => Hash::make($token),
                    'created_at' => now(),
                ]
            );

            // Send notification
            try {
                $user->notify(new ResetPasswordNotification($token));
            } catch (\Exception $e) {
                Log::error('Failed to send password reset email', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                ]);
            }

            Log::info('Password reset requested', ['user_id' => $user->id]);

            return response()->json([
                'message' => 'Jika email terdaftar, link reset password telah dikirim.',
            ]);
        } catch (\Exception $e) {
            Log::error('Forgot password failed', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Terjadi kesalahan saat memproses permintaan',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Reset password.
     */
    public function resetPassword(ResetPasswordRequest $request)
    {
        try {
            $validated = $request->validated();

            // Find password reset record
            $resetRecord = DB::table('password_reset_tokens')
                ->where('email', $validated['email'])
                ->first();

            if (!$resetRecord) {
                throw ValidationException::withMessages([
                    'email' => ['Token reset password tidak valid atau sudah kedaluwarsa.'],
                ]);
            }

            // Check if token is expired (60 minutes)
            if (now()->diffInMinutes($resetRecord->created_at) > 60) {
                DB::table('password_reset_tokens')->where('email', $validated['email'])->delete();
                throw ValidationException::withMessages([
                    'token' => ['Token reset password sudah kedaluwarsa.'],
                ]);
            }

            // Verify token
            if (!Hash::check($validated['token'], $resetRecord->token)) {
                throw ValidationException::withMessages([
                    'token' => ['Token reset password tidak valid.'],
                ]);
            }

            // Update user password
            $user = User::where('email', $validated['email'])->first();
            if (!$user) {
                throw ValidationException::withMessages([
                    'email' => ['Email tidak terdaftar.'],
                ]);
            }

            $user->update([
                'password' => Hash::make($validated['password']),
            ]);

            // Delete reset token
            DB::table('password_reset_tokens')->where('email', $validated['email'])->delete();

            // Reset failed login attempts
            $user->resetFailedLoginAttempts();

            Log::info('Password reset successful', ['user_id' => $user->id]);

            return response()->json([
                'message' => 'Password berhasil direset. Silakan login dengan password baru.',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('Reset password failed', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Terjadi kesalahan saat reset password',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Verify email.
     */
    public function verifyEmail(Request $request)
    {
        try {
            $request->validate([
                'token' => 'required|string',
                'email' => 'required|email|exists:user,email',
            ]);

            $user = User::where('email', $request->email)->first();

            if (!$user) {
                return response()->json([
                    'message' => 'Email tidak terdaftar.',
                ], 404);
            }

            if ($user->isEmailVerified()) {
                return response()->json([
                    'message' => 'Email sudah diverifikasi sebelumnya.',
                ]);
            }

            // Verify token from cache
            $storedToken = cache()->get('email_verification_' . $user->id);
            
            if (!$storedToken || $storedToken !== $request->token) {
                return response()->json([
                    'message' => 'Token verifikasi tidak valid atau sudah kedaluwarsa.',
                ], 400);
            }

            // Verify email
            $user->update([
                'email_verified_at' => now(),
            ]);

            // Delete verification token
            cache()->forget('email_verification_' . $user->id);

            Log::info('Email verified', ['user_id' => $user->id]);

            return response()->json([
                'message' => 'Email berhasil diverifikasi.',
            ]);
        } catch (\Exception $e) {
            Log::error('Email verification failed', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Terjadi kesalahan saat verifikasi email',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Resend verification email.
     */
    public function resendVerificationEmail(Request $request)
    {
        try {
            $request->validate([
                'email' => 'required|email|exists:user,email',
            ]);

            $user = User::where('email', $request->email)->first();

            if (!$user) {
                return response()->json([
                    'message' => 'Email tidak terdaftar.',
                ], 404);
            }

            if ($user->isEmailVerified()) {
                return response()->json([
                    'message' => 'Email sudah diverifikasi sebelumnya.',
                ]);
            }

            // Generate new verification token
            $verificationToken = Str::random(64);
            $frontendUrl = config('frontend.url');
            $verificationUrl = $frontendUrl . '/verify-email?token=' . $verificationToken . '&email=' . urlencode($user->email);
            
            cache()->put('email_verification_' . $user->id, $verificationToken, now()->addHours(24));

            try {
                $user->notify(new VerifyEmailNotification($verificationUrl));
            } catch (\Exception $e) {
                Log::warning('Failed to send verification email', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                ]);
            }

            return response()->json([
                'message' => 'Email verifikasi telah dikirim ulang.',
            ]);
        } catch (\Exception $e) {
            Log::error('Resend verification email failed', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Terjadi kesalahan saat mengirim email verifikasi',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Refresh access token.
     */
    public function refreshToken(RefreshTokenRequest $request)
    {
        try {
            // Ambil refresh_token dari body atau dari httpOnly cookie
            $refreshTokenValue = $request->input('refresh_token')
                ?? $request->cookie(AddTokenFromCookie::COOKIE_REFRESH);

            if (empty($refreshTokenValue)) {
                return response()->json([
                    'message' => 'Refresh token tidak ditemukan. Silakan login ulang.',
                ], 401);
            }

            $token = DB::table('personal_access_tokens')
                ->where('token', hash('sha256', $refreshTokenValue))
                ->where('name', 'refresh_token')
                ->first();

            if (!$token) {
                return response()->json([
                    'message' => 'Refresh token tidak valid.',
                ], 401);
            }

            // Check if token is expired (refresh tokens expire after 30 days)
            $tokenCreatedAt = \Carbon\Carbon::parse($token->created_at);
            if ($tokenCreatedAt->addDays(30)->isPast()) {
                DB::table('personal_access_tokens')->where('id', $token->id)->delete();
                return response()->json([
                    'message' => 'Refresh token sudah kedaluwarsa. Silakan login ulang.',
                ], 401);
            }

            // Get user
            $user = User::find($token->tokenable_id);
            if (!$user) {
                return response()->json([
                    'message' => 'User tidak ditemukan.',
                ], 404);
            }

            // Create new access token
            $accessToken = $user->createToken('auth_token')->plainTextToken;

            Log::info('Token refreshed', ['user_id' => $user->id]);

            $response = response()->json([
                'message' => 'Token berhasil di-refresh',
                'token' => $accessToken,
            ]);
            $response->cookie($this->makeAuthCookie($accessToken));
            return $response;
        } catch (\Exception $e) {
            Log::error('Refresh token failed', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Terjadi kesalahan saat refresh token',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Check if email verification should be skipped (development mode).
     */
    private function shouldSkipEmailVerification(): bool
    {
        return config('app.env') === 'local' 
            || config('app.env') === 'development'
            || env('SKIP_EMAIL_VERIFICATION', false) === true;
    }
}
