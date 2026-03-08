# Migrasi & Hosting — Kolom Hilang ("Unknown column")

Jika di hosting muncul error **"Unknown column 'xxx' in 'SET'"** atau **"Column not found"**, biasanya database di server belum menjalankan migrasi terbaru (schema belum sama dengan kode).

## Solusi umum

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
