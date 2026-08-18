# Portal Siswa (Student Portal)

Dokumentasi fitur dan route untuk pengguna dengan role **siswa** (student).

## Akses

- Login siswa: **NIK** (16 digit) + sandi awal **tanggal lahir `DDMMYYYY`**. Guru/admin tetap login dengan email.
- Saat data siswa disimpan (tambah/edit/import), akun login `role=student` dibuat otomatis jika NIK + tanggal lahir tersedia.
- User dengan **role `student`** setelah login diarahkan ke `/student/dashboard` (atau `/ganti-sandi-wajib` jika masih sandi awal).
- Data siswa diambil dari relasi **`student_profile`** (User ↔ Student via `login_nik` = NIK). Pastikan endpoint **`/me`** memuat `studentProfile` agar NIK, NIS, NISN, kelas, dan `institution_id` tersedia.
- Admin dapat **reset sandi** ke tanggal lahir dari biodata siswa (tombol di Data Siswa).

## Route (Frontend)

| Path | Nama | Keterangan |
|------|------|------------|
| `/student/dashboard` | StudentDashboard | Beranda: sambutan, poin, pelanggaran/prestasi, quick actions, jadwal hari ini, nilai, event mendatang, konseling, ekstrakurikuler |
| `/student/jadwal` | StudentSchedule | Jadwal pelajaran mingguan (matrix hari × jam) |
| `/student/nilai` | StudentGrades | Nilai per semester (dropdown semester), tombol Download Raport CSV |
| `/student/poin` | StudentPoints | Ringkasan poin (total, pelanggaran, prestasi) dan tindakan yang diperlukan |
| `/student/profil` | StudentProfile | Profil + edit langsung field non-kunci + ajukan perubahan data kunci + riwayat permintaan |
| `/student/permintaan-perubahan` | StudentChangeRequests | **Legacy** — redirect ke `/student/profil` (bukan menu terpisah) |
| `/student/pelanggaran-prestasi` | StudentViolations | Daftar pelanggaran dan prestasi |
| `/student/konseling` | StudentCounseling | Daftar konseling |
| `/student/uks` | StudentUks | Ringkasan & riwayat kunjungan UKS sendiri |
| `/student/ekstrakurikuler` | StudentExtracurricular | Daftar ekstrakurikuler yang diikuti |
| `/student/keuangan` | StudentFinance | Tagihan & riwayat pembayaran (read-only; kwitansi PDF) |
| `/student/pkl` | StudentPkl | Penempatan PKL/Prakerin + jurnal kegiatan harian (**hanya SMK/MAK**) |
| `/student/bkk` | StudentBkk | Lowongan BKK terbuka + lamaran mandiri (**hanya SMK/MAK**) |
| `/student/absensi` | StudentAttendance | Riwayat absensi sendiri (filter semester/tanggal) + cetak PDF |
| `/student/ebooks` | StudentEbooks | Perpustakaan digital: katalog ebook institusi + baca PDF |
| `/ujian-ikuti` | ExamTake | Ikuti ujian online (PIN sesi + nomor urut; halaman publik standalone) |
| `/notifications` | Notifications | Notifikasi (akses sama seperti role lain, jika backend mengizinkan) |

Semua route `/student/*` memakai meta **`requiresStudent: true`**; selain role student akan di-redirect ke dashboard sesuai role.  
`/ujian-ikuti` **tidak** memakai `requiresAuth` / `requiresStudent` (masuk lewat PIN sesi + nomor urut kartu peserta).

## Backend (Keamanan)

- Semua endpoint yang dipakai portal siswa **membatasi akses**: siswa hanya boleh mengakses **data milik sendiri** (`student_id` = `user->student_profile->id`).
- **Institution**: Untuk user siswa tanpa `institution_id`, backend memakai **`student_profile->institution_id`** (mis. grades, schedule, violations, counseling, extracurricular, poin, export raport, kalender akademik).

Endpoint yang sudah disesuaikan untuk role student:

- `GET /api/v1/grades/by-student-semester` — hanya nilai sendiri
- `GET /api/v1/grades/export-student-raport` — hanya export raport sendiri
- `GET /api/v1/lesson-schedules/by-class/{classId}` — hanya jadwal kelas sendiri
- `GET /api/v1/violations/by-student/{studentId}` — hanya data sendiri
- `GET /api/v1/achievements/by-student/{studentId}` — hanya data sendiri
- `GET /api/v1/student-points/{studentId}/summary` — hanya poin sendiri
- `GET /api/v1/counseling/by-student/{studentId}` — hanya data sendiri
- `GET /api/v1/uks/visits/my` — ringkasan + riwayat kunjungan UKS sendiri (auth siswa, tanpa permission modul `uks`)
- `GET /api/v1/extracurriculars/by-student/{studentId}` — hanya data sendiri
- `GET /api/v1/academic-calendar/upcoming` — event institusi (pakai `institution_id` dari profil siswa)
- **Absensi (portal siswa, tanpa `module:teaching_journal`)**:
  - `GET /api/v1/student-attendances/my` — riwayat absensi sendiri (`semester_id`, `date_from`, `date_to` opsional)
  - `GET /api/v1/student-attendances/my/export` — cetak PDF riwayat absensi sendiri
  - Status: `hadir`, `alpha`, `izin`, `sakit`, `dinas_luar` — data dari jurnal mengajar guru; siswa **tidak** bisa absen mandiri
- **Perpustakaan digital / ebook (auth, tanpa `module:library`)**:
  - `GET /api/v1/library/ebooks` — katalog ebook institusi (`search`, `category_id`, paginasi)
  - `GET /api/v1/library/ebooks/categories` — daftar kategori
  - `GET /api/v1/library/books/{book}/ebook` — stream PDF (baca di portal); siswa login melihat semua ebook sekolah yang punya file PDF
  - Katalog publik tanpa login: `GET /api/v1/public/library/ebooks?npsn=...` (hanya `is_public_ebook`)
- **Ikuti ujian (publik, throttle 60/menit; tanpa login Sanctum / tanpa `module:online_exam`)**:
  - `POST /api/v1/exam/attempt/enter` — masuk dengan `entry_pin` (6 huruf) + `nomor_urut`, atau `login_token` + `entry_pin`
  - `GET /api/v1/exam/attempt/question` — soal (`login_token`, `index`)
  - `PUT /api/v1/exam/attempt/answer` — simpan jawaban
  - `POST /api/v1/exam/attempt/submit` — kumpulkan; nilai tampil hanya jika sudah di-release
- **Keuangan (portal siswa, tanpa `module:finance`)**:
  - `GET /api/v1/finance/my/summary` — ringkasan tunggakan / dibayar / total
  - `GET /api/v1/finance/my/invoices` — daftar tagihan sendiri (`status`, `include_cancelled`)
  - `GET /api/v1/finance/my/payments` — riwayat pembayaran sendiri
  - `GET /api/v1/finance/my/payments/{payment}/receipt` — kwitansi PDF milik sendiri
- **PKL / jurnal (portal siswa, tanpa `module:pkl`)**:
  - `GET /api/v1/pkl/my/placements` — penempatan PKL sendiri
  - `GET /api/v1/pkl/my/placements/{id}` — detail + monitoring pembimbing (read-only)
  - `GET|POST /api/v1/pkl/my/placements/{id}/journals` — daftar / buat jurnal harian
  - `PUT|DELETE /api/v1/pkl/my/placements/{id}/journals/{journalId}` — ubah / hapus jurnal sendiri
- **BKK (portal siswa/alumni, tanpa `module:bkk`)**:
  - `GET /api/v1/bkk/my/vacancies` — lowongan terbuka
  - `GET /api/v1/bkk/my/applications` — lamaran sendiri
  - `POST /api/v1/bkk/my/applications` — lamar mandiri
- **Profil & permintaan perubahan data** (semua lewat halaman Profil, bukan menu terpisah):
  - `GET /api/v1/student/profile` — profil lengkap sendiri
  - `PUT /api/v1/student/profile` — update langsung field **self-editable** (tanpa approval)
  - `GET /api/v1/student-change-requests/allowed-fields` — `approval_fields` + `self_editable_fields`
  - `GET /api/v1/student-change-requests/pending-count` — jumlah pending milik sendiri
  - `GET/POST /api/v1/student-change-requests`, `GET .../{id}` — siswa hanya akses permintaan sendiri
  - Admin/super_admin approve/tolak lewat `POST .../approve`

### Field profil (siswa)

| Jenis | Cara ubah | Contoh field |
|-------|-----------|--------------|
| Self-editable | `PUT /student/profile` di Profil | alamat, telepon, agama, hobi, tinggi/berat, sekolah sebelumnya, detail orang tua/wali non-identitas, catatan |
| Perlu approval | Ajukan di Profil → `POST /student-change-requests` | nama, NIK, NIS, NISN, gender, tempat/tgl lahir, email, no KK, nama/NIK ayah/ibu/wali |
| Admin-only | Tidak diubah siswa | kelas, status, institusi, tahun ajaran, dll. |

Satu permintaan **pending** per field; nilai baru harus berbeda dari nilai saat ini. Approve `name` / `email` / `nik` dapat menyelaraskan akun login terkait.

## Menu & Navigasi

- **Sidebar**: Dashboard, Jadwal Saya, Absensi Saya, Nilai Saya, **Ikuti Ujian**, Pelanggaran & Prestasi, Konseling, Kunjungan UKS, Ekstrakurikuler, Poin Saya, Tagihan & Pembayaran, PKL / Jurnal, BKK / Lowongan, Perpustakaan Digital, **Profil Saya**.
- Tidak ada menu terpisah “Permintaan Perubahan”; pengajuan & riwayat ada di **Profil Saya**.
- **Bottom nav (mobile)**: Beranda, Jadwal, Absensi, Nilai, Profil.
- **Notifikasi**: Ikon bel notifikasi ditampilkan untuk siswa (jika `student_profile` ada).
- **Dashboard quick actions**: Absensi, Ujian (`/ujian-ikuti`), Ebook, Profil, dll.

## Ringkasan fitur utama

### Absensi Saya
- Hanya **riwayat** (bukan absen mandiri); data dari jurnal mengajar guru.
- Filter semester / rentang tanggal; tombol **Cetak PDF**.

### Perpustakaan Digital
- Cari, filter kategori, baca PDF di aplikasi (atau tab baru).
- Portal login = semua ebook institusi yang punya file; katalog publik NPSN = hanya yang ditandai publik.

### Ikuti Ujian
- Dari sidebar/dashboard → `/ujian-ikuti` (halaman penuh, tanpa chrome portal).
- Alur: pengawas mulai sesi → siswa masuk **PIN 6 huruf** + **nomor urut** kartu peserta → kerjakan soal → submit (timer otomatis).
- PIN berganti berkala; jika gagal, minta kode terbaru ke pengawas. Setelah submit, masuk ulang menampilkan hasil (bukan soal lagi).

### Profil Saya
- Lihat biodata, **Edit Langsung** (field non-kunci), **Ajukan Perubahan Data Kunci**, dan **Riwayat** (pending / approved / rejected).
- Chip pending menandakan ada permintaan menunggu approval.

## Peringatan Khusus

- **Tanpa profil siswa**: Jika user berrole student tetapi **tidak punya `student_profile`** (akun belum dihubungkan dengan data siswa), dashboard menampilkan banner peringatan dan halaman Profil menampilkan pesan agar menghubungi operator sekolah.
- **Migrasi**: Tabel `student_change_requests` dibuat lewat migration `2026_02_05_100000_create_student_change_requests_table`. Jalankan `php artisan migrate` saat MySQL tersedia.
- **Semester (Nilai Saya)**: Untuk user non–super_admin (termasuk siswa), daftar semester dibatasi ke **tahun ajaran aktif institusi** mereka (`institution.active_academic_year_id`).
