# Laporan Audit Keamanan — servr

**Tanggal:** 5 Februari 2026  
**Lingkup:** Backend (Laravel), Frontend (Vue), API, autentikasi, autorisasi, upload/download file.

---

## Ringkasan Eksekutif

Audit menemukan **1 celah kritis** (path traversal) yang telah diperbaiki, serta sejumlah rekomendasi untuk menguatkan keamanan. Autentikasi dan CORS secara umum sudah dikonfigurasi dengan baik (httpOnly cookie, rate limit, lock login).

---

## 1. Celah yang Diperbaiki

### 1.1 Path Traversal pada Download Export (Kritis) — **DIPERBAIKI**

- **Lokasi:** `CorrespondenceExportController::downloadExcel()`, `downloadPdf()`
- **Masalah:** Parameter `filePath` dari URL (`/export/download/{filePath}` dan `/export/pdf/{filePath}`) tidak divalidasi. Pemakai jahat bisa meminta path seperti `../../.env` atau `../storage/...` dan mengakses file di luar folder export.
- **Perbaikan:** Ditambah helper `resolveSafeExportPath()` yang:
  - Menolak path yang mengandung `..`
  - Hanya mengizinkan path yang diawali `exports/`
  - Menormalkan pemisah path
- **File:** `backend/app/Http/Controllers/API/CorrespondenceExportController.php`

### 1.2 Validasi Parameter Tanggal (Rendah) — **DIPERBAIKI**

- **Lokasi:** `AcademicCalendarController::calendar()`
- **Masalah:** `start_date` dan `end_date` dari request dipakai tanpa validasi format, berisiko error atau perilaku tak terduga.
- **Perbaikan:** Validasi request: `start_date` dan `end_date` nullable, format `Y-m-d`, dan `end_date` after_or_equal `start_date`.
- **File:** `backend/app/Http/Controllers/API/AcademicCalendarController.php`

### 1.3 Kebocoran Informasi di Log Frontend (Sedang) — **DIPERBAIKI**

- **Lokasi:** `frontend/src/api/index.js`, `frontend/src/stores/auth.js`
- **Masalah:** `console.log`/`console.error` menampilkan response login, struktur data, dan error detail yang bisa membantu penyerang dan membocorkan struktur API.
- **Perbaikan:** Log debug yang berisi response/error detail di interceptor API dan auth store dihapus.
- **File:** `frontend/src/api/index.js`, `frontend/src/stores/auth.js`

### 1.4 Log Debug di View (Sedang) — **DIPERBAIKI**

- **Lokasi:** `frontend/src/views/Report.vue`, `frontend/src/views/Correspondence.vue`
- **Masalah:** `console.log`/`console.error` di catch dan saat load data menampilkan response/error detail ke console.
- **Perbaikan:** Semua log yang memuat response/error detail dibungkus dengan `if (import.meta.env.DEV)` sehingga tidak keluar di production.
- **File:** `frontend/src/views/Report.vue`, `frontend/src/views/Correspondence.vue`

---

## 2. Praktik Baik yang Sudah Diterapkan

- **Autentikasi:** Login pakai httpOnly cookie untuk token (mengurangi risiko XSS mencuri token), rate limit (5/1 menit untuk login/register), lock akun setelah gagal login berulang, password di-hash (bcrypt).
- **CORS:** `allowed_origins` dari env (`CORS_ALLOWED_ORIGINS`, `FRONTEND_URL`), default development localhost; `supports_credentials: true` konsisten dengan cookie.
- **Autorisasi:** Route dilindungi `auth:sanctum`; akses per modul dengan middleware `module:*`; Report `institutionId` dibatasi (hanya super admin/admin yang bisa akses institusi lain); controller (mis. QR attendance, academic calendar) memeriksa `institution_id` user vs resource.
- **Validasi input:** Form Request dipakai untuk create/update; `ScanQrAttendanceRequest` memvalidasi `qr_data`, `attendance_type`, `teaching_journal_id`, `date`, koordinat.
- **Upload file:** Helper `FileUploadRules` (mime, ukuran, tipe); nama file disanitasi (path traversal); batas 20 dokumen per siswa; akses dokumen siswa/pegawai dicek institusi.
- **Respons error:** Detail exception hanya dikembalikan jika `config('app.debug')` true, mengurangi kebocoran informasi di production.

---

## 3. Rekomendasi Tambahan

### 3.1 Backend

1. **Environment production**
   - Pastikan `APP_DEBUG=false` dan `APP_ENV=production`.
   - Set `COOKIE_DOMAIN` dan `FRONTEND_URL` sesuai domain production.
   - Jangan set `SKIP_EMAIL_VERIFICATION=true` di production.

2. **Rate limiting**
   - Route publik: throttle 5/1 menit sudah baik.
   - Pertimbangkan throttle per-user yang lebih ketat untuk endpoint berat (export, import, laporan).

3. **Token & session**
   - Pastikan cookie `auth_token` dan `refresh_token` di-set dengan `Secure` di production (HTTPS).
   - Sudah benar: `$secure = request()->secure()` di `AuthController`.

4. **Database**
   - Pastikan migration `cache` dan `permissions` / `user_permissions` sudah dijalankan di environment yang dipakai (log sempat mencatat tabel tidak ditemukan).

5. **Tabel user**
   - Model `User` memakai tabel `user`; validasi `exists:user,email` di AuthController sudah konsisten.

### 3.2 Frontend

1. **Token storage**
   - `tokenStorage.js` masih berisi `setToken`/`getToken` (localStorage). Karena auth sudah pakai httpOnly cookie, pastikan tidak ada kode yang lagi menyimpan token ke localStorage (auth store sudah tidak memanggil setToken — baik).
   - Untuk production, pertimbangkan menghapus atau menonaktifkan fallback penyimpanan token di localStorage agar tidak ada sisa token di client.

2. **XSS**
   - Tetap hindari `v-html` untuk konten user; pakai binding biasa. Sanitasi jika terpaksa menampilkan HTML dari backend.

3. **Content Security Policy (CSP)**
   - Backend sudah memakai `SecurityHeaders` middleware; pastikan header CSP (jika ada) tidak memblok fitur legit (mis. inline script Vite di dev).

### 3.3 Operasional

1. **Dependency**
   - Jalankan `composer audit` (backend) dan `npm audit` (frontend) secara berkala; perbaiki vulnerability yang disarankan.

2. **Backup & recovery**
   - Backup database dan file (storage) secara terjadwal; uji restore.

3. **Log & monitoring**
   - Jangan log password atau token. Log akses sensitif (login, reset password, export data) sudah ada; pertimbangkan alert untuk pola mencurigakan (banyak 401/403, banyak export).

---

## 4. Checklist Pasca-Audit

- [x] Path traversal download export diperbaiki
- [x] Validasi `start_date`/`end_date` kalender akademik
- [x] Penghapusan log debug sensitif di API interceptor dan auth store
- [x] Log debug di Report.vue dan Correspondence.vue dibungkus `import.meta.env.DEV`
- [ ] **Sebelum go-live:** Set `APP_DEBUG=false`, `APP_ENV=production`, `COOKIE_DOMAIN`, `FRONTEND_URL`
- [ ] **Sebelum go-live:** Pastikan migration cache & permissions terjalankan di environment target
- [ ] **Berkala:** Jalankan `composer audit` (backend) dan `npm audit` (frontend)
- [ ] (Opsional) Tinjau ulang penggunaan localStorage di `tokenStorage.js` untuk konsistensi dengan auth cookie-only

---

## 5. Referensi Singkat

- OWASP Top 10: https://owasp.org/www-project-top-ten/
- Laravel Security: https://laravel.com/docs/security
- CORS: `backend/config/cors.php`
- Auth & cookie: `backend/app/Http/Controllers/API/AuthController.php`
- File upload rules: `backend/app/Helpers/FileUploadRules.php`
