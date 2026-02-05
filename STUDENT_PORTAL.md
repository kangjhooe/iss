# Portal Siswa (Student Portal)

Dokumentasi fitur dan route untuk pengguna dengan role **siswa** (student).

## Akses

- User dengan **role `student`** setelah login diarahkan ke `/student/dashboard`.
- Data siswa diambil dari relasi **`student_profile`** (User ↔ Student via email). Pastikan endpoint **`/me`** memuat `studentProfile` agar NIS, NISN, kelas, dan `institution_id` tersedia.

## Route (Frontend)

| Path | Nama | Keterangan |
|------|------|------------|
| `/student/dashboard` | StudentDashboard | Beranda: sambutan, poin, pelanggaran/prestasi, quick actions, jadwal hari ini, nilai, event mendatang, konseling, ekstrakurikuler |
| `/student/jadwal` | StudentSchedule | Jadwal pelajaran mingguan (matrix hari × jam) |
| `/student/nilai` | StudentGrades | Nilai per semester (dropdown semester), tombol Download Raport CSV |
| `/student/poin` | StudentPoints | Ringkasan poin (total, pelanggaran, prestasi) dan tindakan yang diperlukan |
| `/student/profil` | StudentProfile | Profil read-only (nama, email, NIS, NISN, kelas, status) |
| `/student/permintaan-perubahan` | StudentChangeRequests | Ajukan perubahan data diri (field yang diizinkan), lihat riwayat permintaan |
| `/student/pelanggaran-prestasi` | StudentViolations | Daftar pelanggaran dan prestasi |
| `/student/konseling` | StudentCounseling | Daftar konseling |
| `/student/ekstrakurikuler` | StudentExtracurricular | Daftar ekstrakurikuler yang diikuti |
| `/notifications` | Notifications | Notifikasi (akses sama seperti role lain, jika backend mengizinkan) |

Semua route di atas memakai meta **`requiresStudent: true`**; selain role student akan di-redirect ke dashboard sesuai role.

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
- `GET /api/v1/extracurriculars/by-student/{studentId}` — hanya data sendiri
- `GET /api/v1/academic-calendar/upcoming` — event institusi (pakai `institution_id` dari profil siswa)
- **Permintaan perubahan data**: `GET/POST /api/v1/student-change-requests`, `GET allowed-fields`, `GET pending-count`; siswa hanya akses permintaan sendiri; admin/super_admin approve/tolak lewat `POST .../approve`.

## Menu & Navigasi

- **Sidebar**: Dashboard, Jadwal Saya, Nilai Saya, Pelanggaran & Prestasi, Konseling, Ekstrakurikuler, Poin Saya, Permintaan Perubahan, Profil Saya.
- **Bottom nav (mobile)**: Beranda, Jadwal, Nilai, Poin.
- **Notifikasi**: Ikon bel notifikasi ditampilkan untuk siswa (jika `student_profile` ada).

## Peringatan Khusus

- **Tanpa profil siswa**: Jika user berrole student tetapi **tidak punya `student_profile`** (akun belum dihubungkan dengan data siswa), dashboard menampilkan banner peringatan dan halaman Profil menampilkan pesan agar menghubungi operator sekolah.
- **Migrasi**: Tabel `student_change_requests` dibuat lewat migration `2026_02_05_100000_create_student_change_requests_table`. Jalankan `php artisan migrate` saat MySQL tersedia.
- **Semester (Nilai Saya)**: Untuk user non–super_admin (termasuk siswa), daftar semester dibatasi ke **tahun ajaran aktif institusi** mereka (`institution.active_academic_year_id`).
