# Audit Issues - Indonesia Smart School (ISS)
## Tanggal: 25 Januari 2026

Dokumen ini berisi hasil audit menyeluruh untuk mencari bug, kesalahan logika, kesalahan relasi, dan celah keamanan.

---

## 🔴 KRITIS - Masalah yang Harus Segera Diperbaiki

### 1. Duplikasi Migrasi Enum (BUG)
**Lokasi:**
- `backend/database/migrations/2026_01_25_022117_add_deleted_to_correspondence_histories_action_enum.php`
- `backend/database/migrations/2026_01_25_025413_add_disposition_deleted_to_correspondence_histories_action_enum.php`

**Masalah:**
Kedua file migrasi melakukan hal yang sama - menambahkan `'deleted'` dan `'disposition_deleted'` ke enum `action` pada tabel `correspondence_histories`. File kedua sepertinya hanya menambahkan `'disposition_deleted'`, tapi sebenarnya sudah ada di file pertama.

**Dampak:**
- Jika kedua migrasi dijalankan, akan terjadi error karena enum sudah berisi nilai tersebut
- Migrasi kedua tidak diperlukan dan harus dihapus

**Solusi:**
Hapus salah satu file migrasi (disarankan hapus file yang lebih baru: `2026_01_25_025413_...`), atau gabungkan jika memang ada perbedaan.

---

### 2. Kesalahan Relasi Database (RELASI)
**Lokasi:** 
- `backend/app/Models/SchoolClass.php` (line 78)
- Log error menunjukkan query menggunakan `school_class_id` yang tidak ada

**Masalah:**
Dari log error:
```
SQLSTATE[42S22]: Column not found: 1054 Unknown column 'student.school_class_id' in 'where clause'
```

Model `Student` menggunakan `class_id` sebagai foreign key (benar), tapi ada query yang mencoba menggunakan `school_class_id`.

**Dampak:**
- Error saat menghitung jumlah siswa per kelas
- Relasi tidak berfungsi dengan benar

**Solusi:**
Periksa semua query yang menggunakan `school_class_id` dan ganti dengan `class_id`. Pastikan semua relasi menggunakan nama kolom yang benar.

**File yang Perlu Diperiksa:**
- `backend/app/Repositories/StudentRepository.php`
- `backend/app/Services/StudentService.php`
- Semua controller yang berhubungan dengan Student dan SchoolClass

---

## ⚠️ PENTING - Masalah yang Perlu Diperbaiki

### 3. Validasi File Upload - Inkonsistensi (KEAMANAN)
**Lokasi:**
- `backend/app/Http/Controllers/API/AttachmentController.php` (line 60)
- `backend/app/Http/Controllers/API/StudentController.php` (line 388)
- `backend/app/Http/Controllers/API/EmployeeController.php` (line 486)

**Masalah:**
1. **AttachmentController**: Menerima multiple file types (pdf,doc,docx,jpg,jpeg,png) dengan max 10MB
2. **StudentController**: Hanya menerima (jpg,jpeg,png,pdf) dengan max 2MB
3. **EmployeeController**: Hanya menerima PDF dengan max 2MB

**Dampak:**
- Inkonsistensi validasi bisa membingungkan user
- Potensi security issue jika validasi tidak konsisten

**Rekomendasi:**
Standarisasi validasi file upload:
- Tentukan file types yang diizinkan per fitur
- Tentukan ukuran maksimal yang konsisten
- Pastikan validasi MIME type dilakukan di backend (jangan hanya mengandalkan extension)

---

### 4. Authorization Check - Potensi Missing Check (KEAMANAN)
**Lokasi:**
- `backend/app/Http/Controllers/API/CorrespondenceController.php`

**Masalah:**
Beberapa method melakukan authorization check manual dengan pattern yang sama:
```php
if (!$request->user()->isAdminOrSuperAdmin() && 
    $correspondence->institution_id !== $request->user()->institution_id) {
    return response()->json(['message' => 'Unauthorized'], 403);
}
```

**Dampak:**
- Risiko lupa menambahkan check di method baru
- Code duplication

**Rekomendasi:**
1. Buat Policy class untuk Correspondence
2. Gunakan `authorize()` method di controller
3. Atau buat middleware khusus untuk institution access

**Contoh:**
```php
// Di CorrespondenceController
public function show(Request $request, $id)
{
    $correspondence = $this->service->find($id);
    $this->authorize('view', $correspondence);
    // ...
}
```

---

### 5. File Upload - Potensi Path Traversal (KEAMANAN)
**Lokasi:**
- `backend/app/Services/CorrespondenceService.php` (line 82-89)
- `backend/app/Http/Controllers/API/StudentController.php` (line 394-395)

**Masalah:**
File name menggunakan `getClientOriginalName()` yang bisa mengandung karakter berbahaya:
```php
$fileName = time() . '_' . $file->getClientOriginalName();
```

**Dampak:**
- Potensi path traversal jika file name mengandung `../`
- Potensi overwrite file jika file name sama

**Solusi:**
Sanitasi file name:
```php
$fileName = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $file->getClientOriginalName());
// atau lebih aman:
$fileName = time() . '_' . Str::random(10) . '.' . $file->getClientOriginalExtension();
```

---

### 6. SQL Injection - Raw SQL Statement (KEAMANAN)
**Lokasi:**
- `backend/database/migrations/2026_01_25_022117_add_deleted_to_correspondence_histories_action_enum.php` (line 17)
- `backend/database/migrations/2026_01_25_025413_add_disposition_deleted_to_correspondence_histories_action_enum.php` (line 17)

**Masalah:**
Menggunakan `DB::statement()` dengan raw SQL string. Meskipun dalam konteks migrasi ini relatif aman, tetap perlu diperhatikan.

**Dampak:**
- Jika ada input user yang masuk ke raw SQL, bisa terjadi SQL injection
- Dalam kasus ini aman karena hardcoded, tapi pattern ini berbahaya jika digunakan di tempat lain

**Rekomendasi:**
Untuk migrasi enum, ini adalah cara yang benar karena MySQL tidak mendukung modifikasi enum langsung. Tapi pastikan:
1. Tidak ada input user yang masuk ke raw SQL
2. Gunakan parameter binding jika ada variabel

---

## 📋 Saran Perbaikan

### 7. Error Handling - Inconsistent Error Messages
**Lokasi:** Semua controller

**Masalah:**
Beberapa method mengembalikan error message yang berbeda untuk kasus yang sama:
- Ada yang menggunakan `'Unauthorized'`
- Ada yang menggunakan `'Forbidden'`
- Ada yang menggunakan `'Tidak memiliki akses'`

**Rekomendasi:**
Standarisasi error messages:
- 401: `'Unauthorized'` - untuk authentication
- 403: `'Forbidden'` atau `'Tidak memiliki akses'` - untuk authorization
- 404: `'Tidak ditemukan'` - untuk resource not found
- 422: `'Validasi gagal'` - untuk validation error

---

### 8. Logging - Sensitive Data Exposure
**Lokasi:**
- `backend/app/Services/CorrespondenceService.php` (line 101-104)

**Masalah:**
Logging data lengkap bisa mengandung informasi sensitif:
```php
Log::error('Failed to create correspondence', [
    'error' => $e->getMessage(),
    'data' => $data, // Bisa mengandung informasi sensitif
]);
```

**Rekomendasi:**
Jangan log data lengkap, hanya log field yang diperlukan:
```php
Log::error('Failed to create correspondence', [
    'error' => $e->getMessage(),
    'correspondence_type' => $data['type'] ?? null,
    'institution_id' => $data['institution_id'] ?? null,
    // Jangan log subject, description, dll yang bisa sensitif
]);
```

---

### 9. Frontend - Missing Input Sanitization
**Lokasi:**
- `frontend/src/views/Correspondence.vue`

**Masalah:**
Vue secara default melakukan escaping untuk mencegah XSS, tapi perlu memastikan:
- Tidak ada penggunaan `v-html` tanpa sanitization
- Input dari user selalu di-escape

**Status:** ✅ **AMAN** - Tidak ditemukan penggunaan `v-html` atau `innerHTML` yang tidak aman.

---

### 10. CORS Configuration
**Lokasi:**
- `backend/config/cors.php` (jika ada)

**Rekomendasi:**
Pastikan CORS dikonfigurasi dengan benar:
- Jangan gunakan `'allowed_origins' => ['*']` di production
- Gunakan whitelist domain yang diizinkan
- Pastikan credentials tidak di-expose ke domain yang tidak terpercaya

---

## ✅ Yang Sudah Baik

1. **XSS Protection**: Vue.js secara default melakukan escaping, dan tidak ditemukan penggunaan `v-html`
2. **CSRF Protection**: Sudah di-disable untuk API routes (benar untuk API dengan Bearer token)
3. **Security Headers**: Sudah ada middleware `SecurityHeaders` yang menambahkan security headers
4. **Input Validation**: Sudah menggunakan Form Requests untuk validasi
5. **Authorization Checks**: Sudah ada di semua controller (meskipun bisa lebih baik dengan Policy)
6. **SQL Injection Protection**: Menggunakan Eloquent ORM yang aman
7. **File Upload Validation**: Sudah ada validasi MIME type dan size limit

---

## 📝 Checklist Perbaikan

- [x] Hapus duplikasi migrasi enum (file 025413 tidak ada / sudah dihapus)
- [x] Perbaiki relasi `school_class_id` → `class_id` (SchoolClass->students() explicit `class_id`, `id`)
- [x] Standarisasi validasi file upload (FileUploadRules + institutionLogo; Attachment/Student/Employee sudah pakai)
- [x] Implementasi Policy untuk authorization (CorrespondencePolicy dipakai di CorrespondenceController & AttachmentController)
- [x] Sanitasi file name saat upload (FileUploadHelper::safeStorageName, safeImportFileName; Institution, Import, AttachmentService, dll)
- [x] Standarisasi error messages (ApiResponse helper: unauthorized, forbidden, notFound, validationFailed, serverError)
- [x] Perbaiki logging untuk menghindari sensitive data (CorrespondenceService: log type & institution_id saja)
- [ ] Review CORS configuration (sudah whitelist env, bukan * di production)
- [ ] Test semua endpoint untuk memastikan authorization bekerja
- [ ] Review semua raw SQL queries (jika ada)

---

## 🔍 Testing yang Disarankan

1. **Security Testing:**
   - Test file upload dengan malicious file names
   - Test authorization dengan user yang tidak memiliki akses
   - Test SQL injection dengan input yang tidak valid
   - Test XSS dengan input yang mengandung script tags

2. **Functional Testing:**
   - Test relasi Student ↔ SchoolClass
   - Test migrasi enum (pastikan tidak error)
   - Test file upload dengan berbagai format dan ukuran

3. **Integration Testing:**
   - Test flow lengkap dari create sampai delete correspondence
   - Test authorization di semua endpoint

---

## 📌 Catatan

- Audit ini dilakukan berdasarkan code review, bukan penetration testing
- Disarankan untuk melakukan security audit yang lebih mendalam sebelum production
- Pertimbangkan untuk menggunakan tools seperti:
  - Laravel Debugbar untuk development
  - Laravel Telescope untuk monitoring
  - OWASP ZAP untuk security scanning

---

**Status:** ⚠️ **PERLU PERBAIKAN** - Ada beberapa masalah kritis dan penting yang perlu diperbaiki sebelum production.
