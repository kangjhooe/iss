# Perbaikan Authentication - Indonesia Smart School

Dokumen ini menjelaskan perbaikan yang telah dilakukan pada sistem authentication (Perbaikan 1.1 - 1.5).

## ✅ Perbaikan yang Telah Diimplementasikan

### 1.1 Login Request Class ✅

**Status:** Selesai

Validasi login telah dipindahkan dari controller ke Form Request class untuk konsistensi dan maintainability.

**File yang dibuat:**
- `backend/app/Http/Requests/LoginRequest.php`

**File yang diupdate:**
- `backend/app/Http/Controllers/API/AuthController.php` - Method `login()` sekarang menggunakan `LoginRequest`

**Keuntungan:**
- Validasi terpusat dan reusable
- Konsisten dengan `RegisterRequest`
- Lebih mudah untuk testing

---

### 1.2 Password Reset/Forgot Password ✅

**Status:** Selesai

Fitur password reset telah diimplementasikan dengan lengkap.

**File yang dibuat:**
- `backend/app/Http/Requests/ForgotPasswordRequest.php`
- `backend/app/Http/Requests/ResetPasswordRequest.php`
- `backend/app/Notifications/ResetPasswordNotification.php`
- `backend/database/migrations/2026_01_09_120001_create_password_reset_tokens_table.php`

**File yang diupdate:**
- `backend/app/Http/Controllers/API/AuthController.php` - Menambahkan methods:
  - `forgotPassword()` - Request password reset
  - `resetPassword()` - Reset password dengan token

**Routes yang ditambahkan:**
- `POST /api/forgot-password` - Request password reset link
- `POST /api/reset-password` - Reset password dengan token

**Cara menggunakan:**

1. **Request Password Reset:**
```json
POST /api/forgot-password
{
  "email": "user@example.com"
}
```

2. **Reset Password:**
```json
POST /api/reset-password
{
  "token": "reset_token_from_email",
  "email": "user@example.com",
  "password": "NewPassword123!",
  "password_confirmation": "NewPassword123!"
}
```

**Fitur:**
- Token expires dalam 60 menit
- Token di-hash sebelum disimpan
- Email notification dengan link reset
- Password strength validation (min 8 karakter, mixed case, numbers, symbols)

---

### 1.3 Account Lockout setelah Failed Login Attempts ✅

**Status:** Selesai

Sistem account lockout telah diimplementasikan untuk mencegah brute force attacks.

**File yang dibuat:**
- `backend/database/migrations/2026_01_09_120000_add_account_lockout_fields_to_user_table.php`

**File yang diupdate:**
- `backend/app/Models/User.php` - Menambahkan methods:
  - `isLocked()` - Check if account is locked
  - `incrementFailedLoginAttempts()` - Increment failed attempts and lock if threshold reached
  - `resetFailedLoginAttempts()` - Reset failed attempts on successful login
- `backend/app/Http/Controllers/API/AuthController.php` - Method `login()` sekarang check account lockout

**Kolom database yang ditambahkan:**
- `failed_login_attempts` (integer, default: 0)
- `locked_until` (timestamp, nullable)

**Fitur:**
- Account terkunci setelah 5 failed login attempts
- Lock duration: 30 menit
- Failed attempts di-reset setelah login berhasil
- Error message menunjukkan waktu tersisa sebelum unlock

**Keamanan:**
- Mencegah brute force attacks
- Rate limiting masih aktif sebagai layer pertama
- Account lockout sebagai layer kedua

---

### 1.4 Email Verification ✅

**Status:** Selesai

Email verification telah diimplementasikan untuk memastikan email user valid.

**File yang dibuat:**
- `backend/app/Notifications/VerifyEmailNotification.php`

**File yang diupdate:**
- `backend/app/Http/Controllers/API/AuthController.php` - Menambahkan methods:
  - `verifyEmail()` - Verify email dengan token
  - `resendVerificationEmail()` - Resend verification email
  - `register()` - Mengirim email verification setelah registrasi
  - `login()` - Check email verification sebelum login

**Routes yang ditambahkan:**
- `POST /api/verify-email` - Verify email dengan token
- `POST /api/resend-verification` - Resend verification email

**Cara menggunakan:**

1. **Setelah Registrasi:**
   - User akan menerima email dengan link verifikasi
   - Link akan mengarah ke frontend dengan token dan email

2. **Verify Email:**
```json
POST /api/verify-email
{
  "token": "verification_token_from_email",
  "email": "user@example.com"
}
```

3. **Resend Verification Email:**
```json
POST /api/resend-verification
{
  "email": "user@example.com"
}
```

**Fitur:**
- Token expires dalam 24 jam
- Email verification required untuk login
- User dapat request resend verification email
- Verification token disimpan di cache

**Catatan:**
- User yang sudah terdaftar sebelum implementasi ini perlu di-verify manual atau melalui resend verification

---

### 1.5 Token Refresh Mechanism ✅

**Status:** Selesai

Token refresh mechanism telah diimplementasikan untuk meningkatkan security dan user experience.

**File yang dibuat:**
- `backend/app/Http/Requests/RefreshTokenRequest.php`

**File yang diupdate:**
- `backend/app/Http/Controllers/API/AuthController.php` - Menambahkan methods:
  - `refreshToken()` - Refresh access token dengan refresh token
  - `login()` - Mengembalikan access token dan refresh token
  - `register()` - Mengembalikan access token dan refresh token

**Routes yang ditambahkan:**
- `POST /api/refresh-token` - Refresh access token

**Cara menggunakan:**

1. **Login/Register Response:**
```json
{
  "message": "Login berhasil",
  "user": {...},
  "token": "access_token_here",
  "refresh_token": "refresh_token_here"
}
```

2. **Refresh Token:**
```json
POST /api/refresh-token
{
  "refresh_token": "refresh_token_from_login"
}
```

**Response:**
```json
{
  "message": "Token berhasil di-refresh",
  "token": "new_access_token"
}
```

**Fitur:**
- Access token untuk API requests (short-lived, bisa di-set expiry di Sanctum config)
- Refresh token untuk mendapatkan access token baru (expires dalam 30 hari)
- Refresh token memiliki scope 'refresh'
- Access token baru di-generate tanpa perlu login ulang

**Keamanan:**
- Refresh token berbeda dari access token
- Refresh token memiliki scope khusus
- Token di-hash sebelum disimpan di database

---

## 📋 Migration yang Perlu Dijalankan

Jalankan migrations berikut untuk menerapkan perubahan database:

```bash
cd backend
php artisan migrate
```

**Migrations yang akan dijalankan:**
1. `2026_01_09_120000_add_account_lockout_fields_to_user_table.php`
2. `2026_01_09_120001_create_password_reset_tokens_table.php`

---

## ⚙️ Konfigurasi yang Diperlukan

### 1. Environment Variables

Tambahkan di `backend/.env`:

```env
# Frontend URL untuk email links
FRONTEND_URL=http://localhost:5173

# Mail Configuration (untuk email verification dan password reset)
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=your_username
MAIL_PASSWORD=your_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@iss.id
MAIL_FROM_NAME="${APP_NAME}"
```

### 2. Update config/app.php (opsional)

Jika belum ada, tambahkan:

```php
'frontend_url' => env('FRONTEND_URL', 'http://localhost:5173'),
```

Atau gunakan langsung `config('app.frontend_url')` jika sudah ada.

---

## 🔄 Perubahan Response Format

### Login/Register Response (Baru)

```json
{
  "message": "Login berhasil",
  "user": {
    "id": 1,
    "name": "User Name",
    "email": "user@example.com",
    "role": "institution_admin",
    "institution": {...}
  },
  "token": "access_token_here",
  "refresh_token": "refresh_token_here"
}
```

### Error Response untuk Account Locked

```json
{
  "message": "The given data was invalid.",
  "errors": {
    "email": [
      "Akun Anda terkunci. Silakan coba lagi dalam 25 menit."
    ]
  }
}
```

### Error Response untuk Email Not Verified

```json
{
  "message": "The given data was invalid.",
  "errors": {
    "email": [
      "Email Anda belum diverifikasi. Silakan cek email untuk link verifikasi."
    ]
  }
}
```

---

## 🧪 Testing Checklist

Setelah implementasi, pastikan untuk test:

- [ ] Login dengan LoginRequest (validasi bekerja)
- [ ] Login dengan account locked (error message muncul)
- [ ] Login dengan email belum verified (error message muncul)
- [ ] Failed login attempts increment (5 attempts = locked)
- [ ] Successful login reset failed attempts
- [ ] Password reset request (email terkirim)
- [ ] Password reset dengan token valid
- [ ] Password reset dengan token expired (error)
- [ ] Email verification setelah registrasi
- [ ] Email verification dengan token valid
- [ ] Resend verification email
- [ ] Token refresh dengan refresh token valid
- [ ] Token refresh dengan refresh token expired (error)

---

## 📝 Catatan Penting

1. **Email Configuration:** Pastikan mail configuration sudah benar di `.env` untuk email verification dan password reset bekerja.

2. **Frontend URL:** Pastikan `FRONTEND_URL` di `.env` sesuai dengan URL frontend Anda.

3. **Existing Users:** User yang sudah terdaftar sebelum implementasi ini:
   - Email belum verified (tidak bisa login sampai verified)
   - Bisa request resend verification email
   - Atau admin bisa verify manual di database

4. **Token Storage:** 
   - Access token: Simpan di localStorage untuk API requests
   - Refresh token: Simpan di localStorage atau httpOnly cookie (lebih secure)

5. **Account Lockout:** 
   - Lock duration: 30 menit
   - Threshold: 5 failed attempts
   - Bisa disesuaikan di `User::incrementFailedLoginAttempts()`

---

## 🔐 Security Improvements

1. ✅ Account lockout mencegah brute force
2. ✅ Email verification memastikan email valid
3. ✅ Password reset dengan token yang expire
4. ✅ Token refresh mengurangi exposure access token
5. ✅ Token di-hash sebelum disimpan
6. ✅ Rate limiting masih aktif sebagai layer pertama

---

**Terakhir diupdate:** 9 Januari 2026
