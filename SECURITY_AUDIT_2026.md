# Analisis Celah Keamanan (Security Audit) - ISS
## Tanggal: 3 Februari 2026

Dokumen ini berisi hasil analisis celah keamanan aplikasi Indonesia Smart School (backend Laravel + frontend Vue.js).

---

## Ringkasan

| Tingkat    | Jumlah | Keterangan |
|-----------|--------|------------|
| Kritis    | 3      | Perbaiki segera |
| Sedang    | 4      | Perbaiki dalam sprint terdekat |
| Rendah    | 3      | Rekomendasi peningkatan |

---

## KRITIS

### 1. IDOR pada Update Institusi (Insecure Direct Object Reference)

**Lokasi:** `backend/app/Http/Controllers/API/InstitutionController.php` — method `update($request, $id)`

**Masalah:**  
Method `update()` **tidak memeriksa** apakah user yang login berhak mengubah institusi dengan `id` tersebut. Hanya method `show()` yang memeriksa: non-admin hanya boleh melihat institusi sendiri. Akibatnya, pengguna dengan role `institution_admin` (atau guru/staff) dari sekolah A bisa memanggil `PUT /api/v1/institution/{id}` dengan `id` sekolah B dan **mengubah data institusi sekolah B** (kecuali nama dan NPSN yang dilindungi khusus).

**Contoh:**  
User sekolah 1 memanggil `PUT /api/v1/institution/2` dengan body berisi alamat, telepon, email, dll. — request akan berhasil.

**Solusi:**  
Tambahkan pengecekan otorisasi di awal `update()` (seperti di `show()`):

```php
// Setelah: $institution = Institution::findOrFail($id);
if (!$request->user()->isAdminOrSuperAdmin() && $request->user()->institution_id != $institution->id) {
    return response()->json(['message' => 'Unauthorized'], 403);
}
```

---

### 2. Penyimpanan Token di Frontend (LocalStorage + “Enkripsi” Lemah)

**Lokasi:** `frontend/src/utils/tokenStorage.js`

**Masalah:**  
- Token akses dan refresh disimpan di **localStorage** sehingga rentan **XSS**: script jahat di halaman yang sama bisa membaca token.  
- “Enkripsi” hanya **base64** (`btoa`/`atob`), bukan enkripsi sungguhan.  
- Kunci `ENCRYPTION_KEY = 'iss_token_key'` **hardcoded**; di production seharusnya tidak ada rahasia di frontend.

**Dampak:**  
Jika ada celah XSS (misalnya di konten yang di-render dari server), token bisa dicuri dan akun diambil alih.

**Solusi (sudah diterapkan):**  
- Backend mengirim token lewat **httpOnly cookie** (auth_token, refresh_token); frontend memakai `withCredentials: true` dan tidak menyimpan token di localStorage.  
- Middleware `AddTokenFromCookie` menyalin token dari cookie ke header Authorization agar Sanctum tetap berfungsi.  
- Login, register, refresh-token meng-set cookie; logout meng-clear cookie.

---

### 3. Upload File Arsip Digital Tanpa Validasi Tipe

**Lokasi:**  
- `backend/app/Http/Requests/StoreDigitalArchiveRequest.php`  
- `backend/app/Http/Requests/UpdateDigitalArchiveRequest.php`  
- Service: `DigitalArchiveService::storeFile()`

**Masalah:**  
- Validasi file hanya: `'file' => 'required|file|max:51200'` (50MB). **Tidak ada aturan `mimes` atau `mimetypes`.**  
- Nama file disimpan aman (sanitasi nama), tetapi **ekstensi diambil dari `getClientOriginalExtension()`** sehingga ekstensi berbahaya (mis. `.php`, `.phtml`, `.exe`) bisa ikut tersimpan.  
- Jika file disimpan di bawah web root dan server dikonfigurasi salah, file bisa dieksekusi (risiko RCE).

**Solusi:**  
- Tambah validasi ketat, mis. `mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png` (sesuai kebutuhan bisnis) dan/atau `mimetypes:application/pdf,...`.  
- Di `DigitalArchiveService::storeFile()`, gunakan **whitelist ekstensi** dan jangan pakai ekstensi asli dari client; contoh: hanya izinkan `['pdf','doc','docx','xls','xlsx','jpg','jpeg','png']` dan normalisasi ke lowercase.

---

## SEDANG

### 4. Endpoint Logo Institusi Tanpa Pengecekan Otorisasi

**Lokasi:** `backend/app/Http/Controllers/API/InstitutionController.php` — method `getLogo($request, $id)`

**Masalah:**  
Siapa pun yang **sudah terautentikasi** bisa meminta logo institusi mana pun dengan `GET /api/v1/institution/{id}/logo`. Tidak ada pengecekan `institution_id` vs user (mis. wali kelas sekolah A bisa mengakses logo sekolah B).

**Dampak:**  
Kebocoran informasi (siapa saja yang login bisa mengakses aset institusi lain). Untuk logo mungkin rendah, tetapi konsisten dengan prinsip least privilege jika dibatasi.

**Solusi:**  
Tambahkan pengecekan yang sama seperti di `show()`: jika user bukan admin/super_admin, pastikan `$request->user()->institution_id == $institution->id`.

---

### 5. Parameter `institution_id` dari Request untuk Admin

**Lokasi:**  
- `DocumentPickupController::getInstitutionId()`  
- `GuestVisitController::getInstitutionId()`  
- `DigitalArchiveController::getInstitutionId()`  
- Dan controller lain yang memakai pola serupa

**Masalah:**  
Untuk user dengan `isAdminOrSuperAdmin()`, `institution_id` diambil dari **request** (`$request->institution_id` atau `$request->get('institution_id')`). Jika tidak ada validasi bahwa admin hanya boleh mengakses institusi yang “dijaga”-nya, token admin yang bocor bisa dipakai untuk mengakses semua institusi.  
(Bagi `institution_admin`, `institution_id` dari user, bukan request — ini sudah benar.)

**Rekomendasi:**  
- Pastikan kebijakan bisnis jelas: apakah role `admin` boleh mengakses semua institusi.  
- Jika ada pembatasan (mis. admin hanya untuk wilayah tertentu), tambah validasi bahwa `institution_id` dari request termasuk dalam daftar yang diizinkan untuk user tersebut.  
- Jangan log atau expose `institution_id` dari request ke pihak yang tidak perlu.

---

### 6. Rate Limiting Hanya pada Grup Auth

**Lokasi:** `backend/routes/api/v1.php`

**Masalah:**  
- Login/register/forgot-password pakai `throttle:5,1` (5 percobaan per menit) — baik.  
- Setelah auth, route dilindungi `throttle:60,1` (60 per menit) secara global. Tidak ada pembedaan untuk endpoint yang lebih sensitif (mis. ubah kata sandi, export data besar, atau API yang mahal).

**Rekomendasi:**  
- Pertahankan throttle ketat untuk login/register/forgot-password.  
- Pertimbangkan throttle lebih ketat untuk endpoint sensitif (reset password, export, import, upload massal) untuk mengurangi abuse dan brute force.

---

### 7. Password Reset Token di URL

**Lokasi:**  
- `backend/app/Notifications/ResetPasswordNotification.php`  
- Frontend (halaman reset password)

**Masalah:**  
Token reset password dikirim di **URL** (`/reset-password?token=...&email=...`). Jika user membagikan link atau referrer dikirim ke pihak ketiga, token bisa bocor.  
Backend sudah membatasi umur token (60 menit) dan menghapus token setelah dipakai — ini bagus.

**Rekomendasi:**  
- Hindari mengirim token di query string jika memungkinkan (mis. gunakan POST body dari halaman yang dibuka tanpa token di URL).  
- Pastikan halaman reset password tidak mengirim referrer ke domain lain (meta tag atau header).  
- Edukasi user untuk tidak membagikan link reset password.

---

## RENDAH

### 8. CORS Mengandalkan Environment

**Lokasi:** `backend/config/cors.php`

**Masalah:**  
Daftar origin diambil dari `CORS_ALLOWED_ORIGINS` dan `FRONTEND_URL`. Jika di production env salah set atau kosong, default fallback ke localhost — bisa membuat frontend production tidak bisa akses API, atau sebaliknya terlalu longgar.

**Rekomendasi:**  
- Di production, **selalu** set `CORS_ALLOWED_ORIGINS` dan `FRONTEND_URL` dengan nilai yang benar.  
- Jangan default ke `*` untuk origin di production.  
- Lakukan pengecekan konfigurasi CORS dalam checklist deploy.

---

### 9. Debug Message di Response

**Lokasi:** Berbagai controller, mis. `AuthController`, `StudentController`, dll.

**Masalah:**  
Banyak response error memakai `config('app.debug') ? $e->getMessage() : null`. Jika suatu saat `APP_DEBUG=true` terpasang di production, stack trace dan pesan error bisa terkirim ke client dan membocorkan informasi internal.

**Rekomendasi:**  
- Pastikan `APP_DEBUG=false` di production.  
- Tambah pengecekan di pipeline/deploy.  
- Pertimbangkan logging error ke server tanpa mengembalikan detail ke client.

---

### 10. Model User – Kolom `role` dan `failed_login_attempts` di Fillable

**Lokasi:** `backend/app/Models/User.php`

**Masalah:**  
`role` dan `failed_login_attempts`, `locked_until` ada di `$fillable`. Jika ada endpoint yang salah memakai `User::create()` atau `$user->update($request->all())` dengan input user, bisa terjadi **escalation of privilege** atau manipulasi lock.

**Status:**  
Dari penelusuran, **tidak ada** endpoint yang mengupdate `User` secara mass assignment dari input (register hanya set role tetap; PermissionController hanya mengubah permission, bukan role). Jadi risiko saat ini rendah, tetapi rapuh terhadap perubahan kode di masa depan.

**Rekomendasi:**  
- Hindari mass assignment untuk kolom sensitif: pindahkan `role`, `failed_login_attempts`, `locked_until` ke `$guarded` atau jangan masukkan ke `$fillable`, dan set nilai tersebut hanya lewat kode yang terkontrol (service/auth).  
- Selalu gunakan Form Request / validated array untuk update user, jangan `$request->all()`.

---

## Yang Sudah Baik

- **Autentikasi:** Login dengan throttle, lock akun setelah gagal berkali-kali, email verification (dengan skip di development).  
- **Otorisasi:** Sebagian besar resource memeriksa `institution_id` dan role (admin/super_admin vs institution); module permission dipakai untuk akses fitur.  
- **Password:** Di-hash dengan Laravel (bcrypt); tidak ada log yang mencatat password.  
- **Reset password:** Token di-hash, kadaluarsa 60 menit, dihapus setelah dipakai.  
- **Raw query:** Penggunaan `DB::raw()`/`selectRaw()` hanya untuk ekspresi statis (agregasi, grup), tidak ada concatenation dengan input user — tidak terlihat risiko SQL injection.  
- **File upload umum:** Ada helper `FileUploadRules` dan `FileUploadHelper` (safe name, path aman); batas ukuran dan MIME dipakai di banyak endpoint. Yang perlu diperketat: arsip digital (lihat poin 3).  
- **CORS:** Dikonfigurasi dengan daftar origin, credentials support; tidak membiarkan origin `*` sembarangan.  
- **Frontend:** Tidak ditemukan `v-html` dengan data tidak terpercaya; penggunaan `innerHTML` di BukuTamu dipakai untuk escape (textContent → innerHTML), bukan injeksi.

---

## Prioritas Perbaikan

1. **Segera:** Perbaiki IDOR update institusi (poin 1) dan validasi tipe/ekstensi file arsip digital (poin 3).  
2. **Singkat:** Perkuat penyimpanan token / migrasi ke httpOnly cookie (poin 2), batasi akses get logo (poin 4).  
3. **Berikutnya:** Validasi `institution_id` untuk admin (poin 5), rate limit endpoint sensitif (poin 6), dan rekomendasi token reset password (poin 7).  
4. **Rutin:** CORS dan APP_DEBUG (poin 8–9), serta fillable/guarded User (poin 10).

---

*Dokumen ini hasil analisis statis kode. Pentest dan uji penetrasi disarankan untuk validasi lebih lanjut.*
