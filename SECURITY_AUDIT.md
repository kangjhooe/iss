# Laporan Bug dan Celah Keamanan

Dokumen ini merangkum temuan audit keamanan dan bug pada codebase ISS (backend Laravel + frontend Vue).

---

## 1. Celah keamanan yang diperbaiki

### 1.1 IDOR pada endpoint logo institusi (FIXED)

**Lokasi:** `backend/app/Http/Controllers/API/InstitutionController.php` → `getLogo()`

**Masalah:** Endpoint `GET /api/v1/institution/{id}/logo` tidak memeriksa otorisasi. Pengguna yang sudah login dengan akses modul `institution` (termasuk guru/staff institusi A) bisa mengakses logo institusi lain dengan mengganti `{id}` (contoh: `/institution/2/logo`).

**Dampak:** Insecure Direct Object Reference (IDOR) — informasi logo institusi lain bisa diambil tanpa hak.

**Perbaikan:** Ditambah pengecekan: hanya admin/super admin atau user yang `institution_id`-nya sama dengan institusi yang diminta yang boleh mengunduh logo. Response 401/403 untuk akses tidak sah.

---

## 2. Rekomendasi keamanan (belum diubah)

### 2.1 Informasi sensitif saat `APP_DEBUG=true`

**Lokasi:** Banyak controller (mis. `AuthController`, `StudentController`, `InstitutionController`, dll.)

**Masalah:** Di banyak `catch` exception, response JSON menyertakan `'error' => config('app.debug') ? $e->getMessage() : null`. Jika `APP_DEBUG=true` di production, stack trace dan detail error bisa bocor ke client.

**Rekomendasi:**
- Pastikan `APP_DEBUG=false` di production.
- Pertimbangkan tidak pernah mengembalikan `$e->getMessage()` ke client; cukup log di server dan kirim pesan umum.

---

### 2.2 Enumerasi email via verifikasi / resend verifikasi

**Lokasi:** `AuthController::verifyEmail()`, `AuthController::resendVerificationEmail()`

**Masalah:**
- `verifyEmail`: validasi `exists:user,email` — response bisa membocorkan apakah email terdaftar.
- `resendVerificationEmail`: response berbeda untuk "email sudah diverifikasi" vs "email tidak terdaftar", sehingga email terdaftar bisa terdeteksi.

**Rekomendasi:** Gunakan pesan respons yang sama untuk semua kasus (mis. "Jika email terdaftar dan belum diverifikasi, link telah dikirim."). Tetap rate limit (sudah ada throttle:5,1).

---

### 2.3 Rate limit endpoint ujian (exam attempt) publik

**Lokasi:** `routes/api/v1.php` — prefix `exam/attempt` dengan `throttle:60,1`

**Status:** Sudah ada rate limit 60 request/menit per IP. Token ujian `login_token` dihasilkan dengan `Str::random(32)` (cukup untuk mencegah brute force).

**Rekomendasi:** Pertimbangkan throttle lebih ketat per `login_token` (mis. batasi percobaan salah token per IP) jika ada risiko brute force token.

---

### 2.4 Pembuatan institusi (Store institution)

**Lokasi:** `StoreInstitutionRequest::authorize()` → `$this->user()?->isAdmin() ?? false`

**Status:** Hanya role `admin` (bukan `institution_admin`) yang boleh membuat institusi via API. Di codebase, registrasi publik membuat `institution_admin`. Jadi pembuatan institusi lewat API hanya untuk role `admin` jika ada.

**Rekomendasi:** Pastikan role `admin` vs `institution_admin` konsisten dengan kebijakan bisnis; jika hanya super_admin yang boleh membuat institusi, ubah authorize menjadi `isSuperAdmin()`.

---

## 3. Bug / perilaku yang perlu diperhatikan

### 3.1 Validasi jawaban ujian: option ID vs soal

**Lokasi:** `SaveExamAnswerRequest` + `ExamAttemptController::saveAnswer()`

**Perilaku:** Request memvalidasi `question_option_id` dan `selected_option_ids.*` dengan `exists:question_options,id`. Controller memastikan `question_bank_id` ada di `question_order` peserta, tetapi tidak memvalidasi bahwa `question_option_id` / `selected_option_ids` memang milik soal tersebut.

**Dampak:** Di `ExamService::computeAutoScoreForParticipant()` skor PG/PG kompleks hanya diberi jika opsi benar **dan** opsi tersebut milik soal yang sama (`$q->options()->where(...)`). Jadi tidak ada pemberian skor salah. Hanya integritas data: jawaban bisa menyimpan option_id dari soal lain (tidak disarankan).

**Rekomendasi:** Validasi tambahan: pastikan option_id milik question_bank_id yang dikirim (query ke `question_options` where `question_bank_id` = …). Ini memperketat integritas data.

---

### 3.2 Path traversal export correspondence

**Lokasi:** `CorrespondenceExportController::downloadExcel()`, `downloadPdf()`

**Status:** Sudah ada `resolveSafeExportPath()` yang menolak `..`, backslash, dan path di luar `exports/`. Aman terhadap path traversal.

---

### 3.3 Raw SQL / DB::raw

**Status:** Penggunaan `selectRaw`, `DB::raw`, dll. yang ditemukan hanya untuk agregasi (count, sum, MONTH, dll.) dengan literal/kolom, bukan input user. Tidak ada indikasi SQL injection.

---

## 4. Ringkasan

| Kategori              | Jumlah | Tindakan                    |
|-----------------------|--------|-----------------------------|
| IDOR / auth bypass    | 1      | Sudah diperbaiki (getLogo)  |
| Info disclosure       | 2      | Rekomendasi (debug, enum)   |
| Rate limit / token    | 0      | Sudah memadai               |
| Otorisasi bisnis      | 1      | Rekomendasi (store institution) |
| Integritas data       | 1      | Opsional (validasi option-soal) |

---

*Dibuat dari hasil audit statis pada codebase. Disarankan untuk pengetesan penetrasi dan review konfigurasi production (APP_DEBUG, CORS, HTTPS, cookie domain) secara terpisah.*
