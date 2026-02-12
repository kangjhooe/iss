# servr

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
- ✅ **Data Siswa** – CRUD, import, dokumen, restore, naik kelas (promote)
- ✅ **Buku Induk** – view & export PDF per siswa
- ✅ **Alumni** – kelulusan, tahun lulus, tracking destinasi (lanjut sekolah/kuliah/kerja)
- ✅ **Mutasi Siswa** – mutasi keluar/masuk, pull dari institusi lain, approval, laporan
- ✅ **Data Pegawai/Guru** – CRUD, import, dokumen, restore, reset password, penugasan institusi
- ✅ **Kelas** – CRUD, pengaturan siswa dalam kelas
- ✅ **Tahun Ajaran & Semester** – manajemen (Super Admin), auto-generate semester
- ✅ **Mata Pelajaran & Jadwal** – mapel, jadwal per kelas/guru/ruang, copy per semester
- ✅ **Jurnal Mengajar** – catatan mengajar, absensi siswa per jam
- ✅ **Absensi Pegawai** – absensi harian guru/staff, bulk input
- ✅ **Absensi QR** – generate & scan QR untuk absensi siswa/pegawai
- ✅ **Kalender Akademik** – event, pengingat, integrasi notifikasi
- ✅ **Buku Nilai & Raport** – nilai per kelas/mapel/semester, export raport
- ✅ **Pelanggaran & Poin** – jenis pelanggaran/prestasi, poin siswa, threshold tindakan, catatan tindakan
- ✅ **Konseling** – sesi konseling, jenis konseling, statistik, export
- ✅ **Ekstrakurikuler** – data ekskul, peserta, export
- ✅ **Sarana & Prasarana (Facility)** – lahan, bangunan, ruang, laporan lab
- ✅ **Inventaris** – kategori, item, transaksi, maintenance, peminjaman, laporan
- ✅ **Persuratan (Correspondence)** – CRUD surat, statistik, disposisi, lampiran, import/export, approve/archive
- ✅ **Arsip Digital** – kategori, upload/download arsip
- ✅ **Buku Tamu** – kunjungan tamu, checkout, export
- ✅ **Pengambilan Ijazah** – tracking pengambilan ijazah alumni
- ✅ **Perpustakaan** – kategori buku, buku, eksemplar, peminjaman, perpanjangan, denda, laporan
- ✅ **Laporan/Statistik** – statistik institusi
- ✅ **Notifikasi** – notifikasi user, mark read
- ✅ **Akses Modul & Permission** – atur akses per user (per modul), tugas tambahan
- ✅ **Audit Log** – log aktivitas, filter, export
- ✅ **Permintaan Perubahan Instansi** – (Super Admin) approve permintaan perubahan data instansi
- ✅ **Dashboard** – Admin institusi, Guru, Super Admin
- ✅ **Multi-tenant** – setiap sekolah terisolasi
- ✅ **Rate limiting** – auth 5 req/menit, protected 60 req/menit
- ✅ **Responsive UI**
- ✅ **PWA** – install ke perangkat, dukungan offline

## Role & Akses

- **Super Admin** – kelola tahun ajaran, instansi, permintaan perubahan instansi
- **Admin / Institution Admin** – akses penuh modul di institusi, kelola akses modul user
- **Teacher / Staff** – akses sesuai permission (modul), dashboard guru

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

Daftar lengkap endpoint per modul (Auth, Institution, Student, Employee, Class, Jadwal, Nilai, Pelanggaran, Konseling, Facility, Inventory, Persuratan, Perpustakaan, dll.) ada di **[API.md](API.md)**.

## Security & Praktik

- Laravel Sanctum, rate limiting (5/60 req menit), validasi input
- Proteksi SQL injection (Eloquent), XSS, CORS, error logging
- API Resources, try-catch, index DB, logging, validasi bahasa Indonesia, transaksi DB

## Troubleshooting

Lihat `PENTING.md` dan `SETUP.md` untuk panduan lengkap.
