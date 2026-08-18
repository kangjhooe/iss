# servr.in

**One Platform for Smarter Education**

Aplikasi manajemen sekolah terintegrasi untuk seluruh sekolah di Indonesia, dari berbagai jenjang dan jenis sekolah.

## Tech Stack

- **Backend**: Laravel 12
- **Frontend**: Vue 3 + Vite
- **Database**: MySQL/PostgreSQL
- **Authentication**: Laravel Sanctum
- **State Management**: Pinia
- **Routing**: Vue Router

## Struktur Proyek

```
iss/
├── backend/          # Laravel API
│   ├── app/
│   │   ├── Http/
│   │   │   ├── Controllers/API/  # API Controllers
│   │   │   ├── Resources/        # API Resources
│   │   │   └── Middleware/       # Custom Middleware
│   │   └── Models/               # Eloquent Models
│   ├── database/migrations/      # Database Migrations
│   └── routes/
│       ├── api.php               # API entry (prefix v1 → /api/v1)
│       └── api/v1.php            # API Routes v1
└── frontend/         # Vue 3 + Vite
    ├── src/
    │   ├── api/                  # API Client (per modul)
    │   ├── components/           # Vue Components
    │   ├── composables/          # Composables (useToast, useConfirmDelete, dll.)
    │   ├── stores/               # Pinia Stores
    │   ├── utils/                # Utilities (validation, tokenStorage, institution)
    │   ├── views/                # Vue Views (halaman)
    │   └── router/               # Vue Router
    └── vite.config.js
```

## Fitur Core

- ✅ **Profil Instansi/Sekolah** – CRUD, logo, tahun ajaran aktif
- ✅ **Validasi NPSN** – NPSN dicek ke data referensi Kemendikbud saat registrasi sekolah, tambah/ubah institusi, dan PPDB (sekolah asal); mencegah NPSN palsu
- ✅ **Data Siswa** – CRUD, import, dokumen, restore, naik kelas (promote)
- ✅ **Buku Induk** – view & export PDF per siswa
- ✅ **Alumni** – kelulusan, tahun lulus, tracking destinasi (lanjut sekolah/kuliah/kerja)
- ✅ **Mutasi Siswa** – mutasi keluar/masuk, pull dari institusi lain, approval, laporan
- ✅ **Data Pegawai/Guru** – CRUD, import, dokumen, restore, reset password, penugasan institusi, cetak PDF
- ✅ **Kepegawaian lanjutan** – cuti (ajukan/approve), register SK, jabatan struktural, riwayat karier; portal **Cuti Saya** untuk guru/staff
- ✅ **Mutasi Guru** – mutasi keluar/masuk antar institusi (push/pull by NIK), approval, batalkan, laporan/export
- ✅ **Kelas** – CRUD, pengaturan siswa dalam kelas, clone ke tahun ajaran
- ✅ **Tahun Ajaran & Semester** – manajemen (Super Admin), auto-generate semester
- ✅ **Mata Pelajaran & Jadwal** – mapel, jadwal per kelas/guru/ruang, copy per semester, **multi-template jadwal** (mis. 8 JP / 9 JP) + assign ke kelas
- ✅ **Dashboard Wali Kelas** – hub terpadu: roster, absensi 7/30 hari, monitoring nilai/KKM, skor BK, usulan pelanggaran/prestasi/mutasi, catatan, jadwal, profil siswa 360°, export
- ✅ **Jurnal Mengajar** – catatan mengajar, absensi siswa per jam
- ✅ **Absensi Pegawai** – absensi harian guru/staff, bulk input
- ✅ **Absensi QR** – generate & scan QR untuk absensi siswa/pegawai
- ✅ **Guru Piket** – jadwal piket, log harian, insiden → usulan pelanggaran, laporan mingguan
- ✅ **Apresiasi & Poin Guru** – prestasi, pelanggaran, poin, leaderboard, reward
- ✅ **Kalender Akademik** – event, pengingat, integrasi notifikasi
- ✅ **Buku Nilai & Raport** – nilai per kelas/mapel/semester, **KKM**, **bobot penilaian**, remidi/pengayaan, export raport
- ✅ **Ujian Online** (Beta) – 4 menu: Daftar Ujian, Bank Soal, Peserta Ujian, Kontrol Ujian (monitoring live, koreksi, rilis nilai, export laporan)
- ✅ **Keuangan** – jenis biaya, generate tagihan SPP/non-rutin, pembayaran, tunggakan, laporan, portal siswa tagihan
- ✅ **PKL / Prakerin** (SMK/MAK) – mitra DU/DI, periode, penempatan (bulk/export), monitoring pembimbing, nilai; **jurnal harian di portal siswa**
- ✅ **BKK / Bursa Kerja** (SMK/MAK) – lowongan & lamaran (staff + lamaran mandiri siswa/alumni)
- ✅ **UKS** – kunjungan, jenis kunjungan, stok obat, laporan; ringkasan di portal siswa
- ✅ **PPDB** – periode, jalur, pendaftar publik, verifikasi, hasil seleksi, pembayaran, export CSV/XLSX, notifikasi email calon, konversi ke siswa
- ✅ **Pelanggaran & Poin** – jenis pelanggaran/prestasi, poin siswa, threshold tindakan, catatan tindakan, laporan BK
- ✅ **Konseling** – sesi konseling, jenis konseling, statistik, export
- ✅ **Ekstrakurikuler** – data ekskul, peserta, nilai kegiatan, export; flag **Pramuka**
- ✅ **Portal orang tua** (MVP) – lihat jadwal/nilai/absensi/pelanggaran/pengumuman anak (`/parent/dashboard`)
- ✅ **Berita & galeri publik** – CMS admin + tampil di halaman publik sekolah
- ✅ **Sarana & Prasarana (Facility)** – lahan, bangunan, ruang, lab, **booking lab**
- ✅ **Inventaris** – kategori, item, transaksi, maintenance, peminjaman, laporan
- ✅ **Persuratan (Correspondence)** – editor surat dari template, kop, TTD, disposisi, lampiran, import/export, approve/archive
- ✅ **Arsip Digital** – kategori, upload/download arsip
- ✅ **Buku Tamu** – kunjungan tamu, checkout, export (juga form publik)
- ✅ **Pengambilan Ijazah** – tracking pengambilan ijazah alumni
- ✅ **Perpustakaan** – kategori, buku, eksemplar, peminjaman, denda, laporan; **ebook PDF** (katalog internal + publik by NPSN)
- ✅ **Permintaan ubah data** – siswa/guru ajukan perubahan profil, admin setujui
- ✅ **Feedback ticket** – admin sekolah lapor bug/request fitur ke Super Admin (API)
- ✅ **Laporan/Statistik** – statistik institusi
- ✅ **Notifikasi** – notifikasi user, mark read
- ✅ **Akses Modul & Permission** – atur akses per user (per modul), tugas tambahan
- ✅ **Audit Log** – log aktivitas, filter, export
- ✅ **Super Admin** – institusi, tahun ajaran, permintaan perubahan, onboarding admin, adopsi, broadcast, catatan rilis, laporan agregat, impersonate
- ✅ **Monetisasi** (dark launch) – paket/add-on/grant per institusi di `/super-admin/monetisasi`; default tersembunyi sampai SA menampilkan ke sekolah; ringkasan sekolah di `/billing`
- ✅ **Dashboard** – Admin institusi, Guru, Super Admin, Wali Kelas
- ✅ **Multi-tenant** – setiap sekolah terisolasi
- ✅ **Rate limiting** – auth 5 req/menit, protected 60 req/menit
- ✅ **Responsive UI**
- ✅ **PWA** – install ke perangkat, dukungan offline

## Kompatibilitas Smartphone & Tablet

Aplikasi didesain responsif untuk **smartphone** dan **tablet**:

- **Viewport & meta**: `width=device-width`, `viewport-fit=cover`, dan meta Apple mobile web app di `index.html`.
- **Breakpoint**: Tablet 769px–1024px; mobile ≤768px (sidebar drawer + bottom nav); smartphone kecil ≤480px.
- **Touch**: Tombol/aksi utama minimal 44×44px dan `touch-action: manipulation`.
- **Safe area**: Padding memakai `env(safe-area-inset-*)` untuk layar notch/home indicator.
- **Form**: Input `font-size: 16px` di mobile untuk mencegah zoom otomatis di iOS.
- **Tabel**: Scroll horizontal di wrapper; beberapa halaman pakai kartu di mobile.
- **Modal**: Lebar 95% di mobile, max-height 90vh.

Halaman **Ikuti Ujian** (ExamTake) dan **Ujian Online** (ExamList, SessionDetail) telah disesuaikan untuk ponsel/tablet.

## Role & Akses

- **Super Admin** – kelola tahun ajaran, instansi, onboarding admin, adopsi, broadcast, rilis, feedback ticket, laporan agregat
- **Admin / Institution Admin** – akses penuh modul di institusi, kelola akses modul user, kirim feedback
- **Teacher / Staff** – akses sesuai permission (modul), dashboard guru; wali kelas mendapat hub terpadu otomatis
- **Student** – portal terbatas (ebook, ekskul, nilai sendiri, ajukan ubah data)

## Setup

### Prerequisites

- PHP >= 8.2
- Composer
- Node.js >= 18
- MySQL/PostgreSQL
- XAMPP (optional)

### Backend (Laravel)

```bash
cd backend
composer install
cp .env.example .env
php artisan key:generate

# Konfigurasi database di .env
# DB_CONNECTION=mysql
# DB_HOST=127.0.0.1
# DB_PORT=3306
# DB_DATABASE=iss_db
# DB_USERNAME=root
# DB_PASSWORD=

# Buat database
mysql -u root -e "CREATE DATABASE iss_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# Jalankan migrations
php artisan migrate

# (Opsional) Symlink storage untuk download file (export/lampiran/logo)
php artisan storage:link

# (Opsional) Seeder tertentu jika dibutuhkan
# php artisan db:seed --class=InventoryCategorySeeder

# Sekolah demo publik (SMA 1 Demo Servrin) — lihat LOGIN_DATA.md
# php artisan demo:reset
# Jadwal reset harian 03:00 WIB via `php artisan schedule:run`

# Jalankan server
php artisan serve
```

### Frontend (Vue)

```bash
cd frontend
npm install
npm run dev
```

## Development

- **Backend API**: http://localhost:8000/api/v1
- **Frontend**: http://localhost:5173

Info endpoint: `GET http://localhost:8000/api/v1` (daftar ringkas public & protected).

## API Documentation

- Prefix: **`/api/v1`**
- Protected: header **`Authorization: Bearer <token>`**
- Rate limit: auth 5 req/menit, protected 60 req/menit

Daftar lengkap endpoint per modul (Auth, Institution, Student, Employee, Mutasi Guru, Wali Kelas, Jadwal & Template, Nilai/KKM/Remidi, Ebook, Feedback, Keuangan, PKL/BKK, PPDB, Ujian Online, dll.) ada di **[API.md](API.md)**. Bantuan UI singkat per modul ada di **HelpSidebar** di aplikasi.

Petunjuk penggunaan di aplikasi ada di panel **HelpSidebar** (tombol Bantuan di kanan).

## Security & Praktik

- Laravel Sanctum, rate limiting (5/60 req menit), validasi input
- Proteksi SQL injection (Eloquent), XSS, CORS, error logging
- API Resources, try-catch, index DB, logging, validasi bahasa Indonesia, transaksi DB

## Troubleshooting

Lihat `PENTING.md` dan `SETUP.md` untuk panduan lengkap.
