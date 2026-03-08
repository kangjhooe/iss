# Migrasi & Hosting — Kolom Hilang ("Unknown column")

Jika di hosting muncul error **"Unknown column 'xxx' in 'SET'"** atau **"Column not found"**, biasanya database di server belum menjalankan migrasi terbaru (schema belum sama dengan kode).

## Error: "Method Illuminate\\Foundation\\Application::share does not exist"

Jika saat menjalankan `php artisan migrate` muncul error:

```text
In Macroable.php line 115: Method Illuminate\Foundation\Application::share does not exist.
```

**Penyebab**: Cache bootstrap lama atau versi package (misalnya L5-Swagger) di server tidak kompatibel dengan Laravel yang dipakai.

**Langkah perbaikan di hosting (jalankan berurutan):**

1. **Bersihkan cache Laravel**
   ```bash
   php artisan config:clear
   php artisan cache:clear
   php artisan optimize:clear
   ```

2. **Hapus file cache bootstrap** (jika masih error):
   - Hapus atau kosongkan isi folder `backend/bootstrap/cache/` **kecuali** file `.gitignore`.
   - Atau hapus hanya: `config.php`, `services.php`, `packages.php` jika ada.
   - Setelah itu jalankan lagi: `php artisan migrate`.

3. **Pastikan dependency sama dengan development**
   - Di server wajib pakai `composer install` (bukan `composer update`).
   - Pastikan file `composer.lock` ikut di-upload / di-deploy agar versi package sama.

4. **Opsi sementara: nonaktifkan L5-Swagger**
   - Jika migrate harus jalan dulu dan error belum hilang, di `backend/bootstrap/app.php` hapus atau komentar baris `\L5Swagger\L5SwaggerServiceProvider::class` dari array `withProviders`.
   - Setelah migrate berhasil, kembalikan lagi dan jalankan `composer install` + `php artisan optimize:clear`.

---

## Solusi umum (Unknown column)

Jalankan di server hosting:

```bash
php artisan migrate
```

Jika tidak bisa (shared hosting tanpa SSH), gunakan file SQL manual di bawah.

---

## Kasus yang sudah terdokumentasi

### 1. Institution — update profil & upload cover gagal

- **Gejala**: "Terjadi kesalahan saat memperbarui institusi", upload gambar cover/hero gagal.
- **Penyebab**: Tabel `institution` belum punya kolom `vision`, `mission`, `cover_image`, `province_code`, `district_code`.
- **Migrasi**: `add_vision_mission_cover_to_institution_table`, `add_exam_participant_number_fields`.
- **Tempat di kode**: `InstitutionController::update()`, `uploadCoverImage()`, `uploadLogo()`; `UpdateInstitutionRequest`; model `Institution` (fillable); `InstitutionResource`.

### 2. Exam participants — nomor urut / nomor peserta

- **Gejala**: Error saat generate nomor urut peserta ujian atau update `participant_order`.
- **Penyebab**: Tabel `exam_participants` belum punya kolom `participant_order`.
- **Migrasi**: `add_exam_participant_number_fields` (sama dengan institution province_code/district_code).
- **Tempat di kode**: `ExamParticipantController` (generate order, reorder, update); model `ExamParticipant` (fillable); `ExamParticipantResource`; `Institution::buildNomorPeserta()` pakai `province_code`/`district_code`.

---

## SQL manual (jika tidak bisa jalankan `php artisan migrate`)

File: **`database/migrations/sql_add_missing_institution_columns.sql`**

Berisi `ALTER TABLE` untuk:

- `institution`: `vision`, `mission`, `cover_image`, `province_code`, `district_code`
- `exam_participants`: `participant_order`

Jalankan per statement di phpMyAdmin / MySQL client. Jika muncul "Duplicate column name", kolom itu sudah ada — lewati.

---

## Migrasi lain yang menambah kolom (risiko serupa)

Setiap migrasi yang memakai `Schema::table('nama_tabel', ...)` **menambah** kolom ke tabel yang sudah ada. Jika migrasi itu belum dijalankan di hosting, fitur yang menulis/update kolom tersebut bisa error "Unknown column".

Contoh migrasi yang mengubah tabel yang sudah ada (bukan hanya buat tabel baru):

- `add_vision_mission_cover_to_institution_table` → institution
- `add_exam_participant_number_fields` → institution, exam_participants
- `add_latitude_longitude_to_institution_table` → institution
- `add_logo_to_institution_table` → institution
- `add_active_academic_year_and_semester_to_institution_table` → institution
- `add_previous_school_fields` → ppdb_applicants, student
- `add_ppdb_phase2_fields` → ppdb_periods, ppdb_applicants
- `add_hero_fields_to_app_branding_table` → app_branding
- `add_keterangan_to_bank_soal_table` → bank_soal
- `add_created_by_user_id_to_bank_soal_table` → bank_soal
- `add_entry_pin_to_exam_sessions` → exam_sessions
- … dan lain-lain (lihat daftar file di `database/migrations/` yang isinya `Schema::table`).

**Rekomendasi**: Selalu jalankan `php artisan migrate` setelah deploy ke hosting, atau sesuaikan schema database secara manual mengikuti isi migrasi yang belum jalan.
