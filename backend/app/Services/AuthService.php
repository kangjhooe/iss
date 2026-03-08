<?php

namespace App\Services;

use App\Models\User;
use App\Notifications\ResetPasswordNotification;
use App\Notifications\VerifyEmailNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthService
{
    /**
     * Register a new institution and admin user.
     */
    public function register(array $data): array
    {
        DB::beginTransaction();

        try {
            $institution = \App\Models\Institution::create([
                'npsn' => $data['npsn'],
                'name' => $data['institution_name'],
                'phone' => $data['phone'],
                'email' => $data['email'],
                'is_active' => true,
            ]);

            // Auto-verify email in development/local environment
            $emailVerifiedAt = $this->shouldSkipEmailVerification() ? now() : null;
            
            $user = User::create([
                'institution_id' => $institution->id,
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'role' => 'institution_admin',
                'email_verified_at' => $emailVerifiedAt,
            ]);

            // Create tokens
            $accessToken = $user->createToken('auth_token')->plainTextToken;
            $refreshToken = $user->createToken('refresh_token', ['refresh'])->plainTextToken;

            // Send email verification only if not in development
            if (!$this->shouldSkipEmailVerification()) {
                $this->sendVerificationEmail($user);
            }

            DB::commit();

            Log::info('New institution registered', [
                'institution_id' => $institution->id,
                'user_id' => $user->id,
            ]);

            return [
                'user' => $user->load(['institution', 'permissions']),
                'institution' => $institution,
                'access_token' => $accessToken,
                'refresh_token' => $refreshToken,
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Login user.
     */
    public function login(string $email, string $password): array
    {
        $user = User::where('email', $email)->first();

        // Check if account is locked
        if ($user && $user->isLocked()) {
            $minutesRemaining = now()->diffInMinutes($user->locked_until, false);
            throw ValidationException::withMessages([
                'email' => ["Akun Anda terkunci. Silakan coba lagi dalam {$minutesRemaining} menit."],
            ]);
        }

        if (!$user || !Hash::check($password, $user->password)) {
            if ($user) {
                $user->incrementFailedLoginAttempts();
            }
            Log::warning('Failed login attempt', ['email' => $email]);
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

        return [
            'user' => $user->load(['institution', 'permissions']),
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
        ];
    }

    /**
     * Send password reset link.
     */
    public function sendPasswordResetLink(string $email): void
    {
        $user = User::where('email', $email)->first();

        if (!$user) {
            // Return silently for security (don't reveal if email exists)
            return;
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
    }

    /**
     * Reset password with token.
     */
    public function resetPassword(string $email, string $token, string $password): void
    {
        // Find password reset record
        $resetRecord = DB::table('password_reset_tokens')
            ->where('email', $email)
            ->first();

        if (!$resetRecord) {
            throw ValidationException::withMessages([
                'email' => ['Token reset password tidak valid atau sudah kedaluwarsa.'],
            ]);
        }

        // Check if token is expired (60 minutes)
        if (now()->diffInMinutes($resetRecord->created_at) > 60) {
            DB::table('password_reset_tokens')->where('email', $email)->delete();
            throw ValidationException::withMessages([
                'token' => ['Token reset password sudah kedaluwarsa.'],
            ]);
        }

        // Verify token
        if (!Hash::check($token, $resetRecord->token)) {
            throw ValidationException::withMessages([
                'token' => ['Token reset password tidak valid.'],
            ]);
        }

        // Update user password
        $user = User::where('email', $email)->first();
        if (!$user) {
            throw ValidationException::withMessages([
                'email' => ['Email tidak terdaftar.'],
            ]);
        }

        $user->update([
            'password' => Hash::make($password),
        ]);

        // Delete reset token
        DB::table('password_reset_tokens')->where('email', $email)->delete();

        // Reset failed login attempts
        $user->resetFailedLoginAttempts();

        Log::info('Password reset successful', ['user_id' => $user->id]);
    }

    /**
     * Verify email.
     */
    public function verifyEmail(string $email, string $token): void
    {
        $user = User::where('email', $email)->first();

        if (!$user) {
            throw ValidationException::withMessages([
                'email' => ['Email tidak terdaftar.'],
            ]);
        }

        if ($user->isEmailVerified()) {
            return; // Already verified
        }

        // Verify token from cache
        $storedToken = cache()->get('email_verification_' . $user->id);

        if (!$storedToken || $storedToken !== $token) {
            throw ValidationException::withMessages([
                'token' => ['Token verifikasi tidak valid atau sudah kedaluwarsa.'],
            ]);
        }

        // Verify email
        $user->update([
            'email_verified_at' => now(),
        ]);

        // Delete verification token
        cache()->forget('email_verification_' . $user->id);

        Log::info('Email verified', ['user_id' => $user->id]);
    }

    /**
     * Resend verification email.
     */
    public function resendVerificationEmail(string $email): void
    {
        $user = User::where('email', $email)->first();

        if (!$user) {
            throw ValidationException::withMessages([
                'email' => ['Email tidak terdaftar.'],
            ]);
        }

        if ($user->isEmailVerified()) {
            return; // Already verified
        }

        $this->sendVerificationEmail($user);
    }

    /**
     * Refresh access token.
     */
    public function refreshToken(string $refreshToken): string
    {
        // Find token
        $token = DB::table('personal_access_tokens')
            ->where('token', hash('sha256', $refreshToken))
            ->where('name', 'refresh_token')
            ->first();

        if (!$token) {
            throw ValidationException::withMessages([
                'refresh_token' => ['Refresh token tidak valid.'],
            ]);
        }

        // Check if token is expired (refresh tokens expire after 30 days)
        $tokenCreatedAt = \Carbon\Carbon::parse($token->created_at);
        if ($tokenCreatedAt->addDays(30)->isPast()) {
            DB::table('personal_access_tokens')->where('id', $token->id)->delete();
            throw ValidationException::withMessages([
                'refresh_token' => ['Refresh token sudah kedaluwarsa. Silakan login ulang.'],
            ]);
        }

        // Get user
        $user = User::find($token->tokenable_id);
        if (!$user) {
            throw ValidationException::withMessages([
                'refresh_token' => ['User tidak ditemukan.'],
            ]);
        }

        // Create new access token
        $accessToken = $user->createToken('auth_token')->plainTextToken;

        Log::info('Token refreshed', ['user_id' => $user->id]);

        return $accessToken;
    }

    /**
     * Send verification email to user.
     */
    protected function sendVerificationEmail(User $user): void
    {
        $verificationToken = Str::random(64);
        $frontendUrl = config('frontend.url');
        $verificationUrl = $frontendUrl . '/verify-email?token=' . $verificationToken . '&email=' . urlencode($user->email);

        // Store verification token
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

    /**
     * Check if email verification should be skipped (development mode).
     */
    protected function shouldSkipEmailVerification(): bool
    {
        return config('app.env') === 'local' 
            || config('app.env') === 'development'
            || env('SKIP_EMAIL_VERIFICATION', false) === true;
    }
}
