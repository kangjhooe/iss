<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Register a new institution and admin user.
     */
    public function register(Request $request)
    {
        try {
            $validated = $request->validate([
                'npsn' => 'required|string|size:8|regex:/^[0-9]{8}$/|unique:institution,npsn',
                'institution_name' => 'required|string|max:255',
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:user,email',
                'phone' => 'required|string|max:20',
                'password' => 'required|string|min:8|confirmed',
            ], [
                'npsn.required' => 'NPSN wajib diisi',
                'npsn.size' => 'NPSN harus terdiri dari 8 digit',
                'npsn.regex' => 'NPSN harus berupa angka 8 digit',
                'npsn.unique' => 'NPSN sudah terdaftar',
                'institution_name.required' => 'Nama institusi wajib diisi',
                'name.required' => 'Nama lengkap wajib diisi',
                'email.required' => 'Email wajib diisi',
                'email.email' => 'Format email tidak valid',
                'email.unique' => 'Email sudah terdaftar',
                'phone.required' => 'Nomor telepon wajib diisi',
                'password.required' => 'Password wajib diisi',
                'password.min' => 'Password minimal 8 karakter',
                'password.confirmed' => 'Konfirmasi password tidak cocok',
            ]);

            DB::beginTransaction();

            $institution = \App\Models\Institution::create([
                'npsn' => $validated['npsn'],
                'name' => $validated['institution_name'],
                'phone' => $validated['phone'],
                'is_active' => true,
            ]);

            $user = User::create([
                'institution_id' => $institution->id,
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'role' => 'institution_admin',
            ]);

            $token = $user->createToken('auth_token')->plainTextToken;

            DB::commit();

            Log::info('New institution registered', [
                'institution_id' => $institution->id,
                'user_id' => $user->id,
            ]);

            return response()->json([
                'message' => 'Registrasi berhasil',
                'user' => new UserResource($user->load('institution')),
                'token' => $token,
            ], 201);
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
     */
    public function login(Request $request)
    {
        try {
            $validated = $request->validate([
                'email' => 'required|email',
                'password' => 'required',
            ], [
                'email.required' => 'Email wajib diisi',
                'email.email' => 'Format email tidak valid',
                'password.required' => 'Password wajib diisi',
            ]);

            $user = User::where('email', $validated['email'])->first();

            if (!$user || !Hash::check($validated['password'], $user->password)) {
                Log::warning('Failed login attempt', ['email' => $validated['email']]);
                throw ValidationException::withMessages([
                    'email' => ['Kredensial yang diberikan salah.'],
                ]);
            }

            // Check if user's institution is active
            if ($user->institution && !$user->institution->is_active) {
                throw ValidationException::withMessages([
                    'email' => ['Akun institusi Anda tidak aktif. Silakan hubungi administrator.'],
                ]);
            }

            $token = $user->createToken('auth_token')->plainTextToken;

            Log::info('User logged in', ['user_id' => $user->id]);

            return response()->json([
                'message' => 'Login berhasil',
                'user' => new UserResource($user->load('institution')),
                'token' => $token,
            ]);
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
            $user->currentAccessToken()->delete();

            Log::info('User logged out', ['user_id' => $user->id]);

            return response()->json([
                'message' => 'Logout berhasil',
            ]);
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
                'user' => new UserResource($request->user()->load('institution')),
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
}
