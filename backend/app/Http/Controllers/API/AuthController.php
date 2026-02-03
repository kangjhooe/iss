<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Middleware\AddTokenFromCookie;
use App\Http\Requests\ForgotPasswordRequest;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RefreshTokenRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\ResetPasswordRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
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
        ];
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

            // Auto-verify email in development/local environment
            $emailVerifiedAt = $this->shouldSkipEmailVerification() ? now() : null;
            
            $user = User::create([
                'institution_id' => $institution->id,
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'role' => 'institution_admin',
                'email_verified_at' => $emailVerifiedAt,
            ]);

            // Create tokens
            $accessToken = $user->createToken('auth_token')->plainTextToken;
            $refreshToken = $user->createToken('refresh_token', ['refresh'])->plainTextToken;

            // Send email verification only if not in development
            if (!$this->shouldSkipEmailVerification()) {
                $verificationToken = Str::random(64);
                $frontendUrl = env('FRONTEND_URL', 'http://localhost:5173');
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

            $user = User::where('email', $validated['email'])->first();

            // Check if account is locked
            if ($user && $user->isLocked()) {
                $minutesRemaining = now()->diffInMinutes($user->locked_until, false);
                throw ValidationException::withMessages([
                    'email' => ["Akun Anda terkunci. Silakan coba lagi dalam {$minutesRemaining} menit."],
                ]);
            }

            if (!$user || !Hash::check($validated['password'], $user->password)) {
                if ($user) {
                    $user->incrementFailedLoginAttempts();
                }
                Log::warning('Failed login attempt', ['email' => $validated['email']]);
                throw ValidationException::withMessages([
                    'email' => ['Kredensial yang diberikan salah.'],
                ]);
            }

            // Auto-verify email in development if not verified
            if ($this->shouldSkipEmailVerification() && !$user->isEmailVerified()) {
                $user->update(['email_verified_at' => now()]);
                Log::info('Auto-verified user email in development', ['user_id' => $user->id]);
            }
            
            // Check if email is verified (skip check in development)
            if (!$this->shouldSkipEmailVerification() && !$user->isEmailVerified()) {
                throw ValidationException::withMessages([
                    'email' => ['Email Anda belum diverifikasi. Silakan cek email untuk link verifikasi.'],
                ]);
            }

            // Check if user's institution is active (skip for super admin)
            if (!$user->isSuperAdmin() && $user->institution && !$user->institution->is_active) {
                throw ValidationException::withMessages([
                    'email' => ['Akun institusi Anda tidak aktif. Silakan hubungi administrator.'],
                ]);
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

            $response = response()->json([
                'message' => 'Login berhasil',
                'user' => new UserResource($user),
                'token' => $accessToken,
                'refresh_token' => $refreshToken,
            ]);
            $response->cookie($this->makeAuthCookie($accessToken));
            $response->cookie($this->makeRefreshCookie($refreshToken));
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
            return response()->json([
                'user' => new UserResource($request->user()->load(['institution', 'permissions'])),
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
            $frontendUrl = env('FRONTEND_URL', 'http://localhost:5173');
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
