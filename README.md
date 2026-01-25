# Indonesia Smart School (ISS)

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
│       ├── api.php               # API entry (mount versi, contoh: /api/v1)
│       └── api/v1.php            # API Routes v1
└── frontend/         # Vue 3 + Vite
    ├── src/
    │   ├── api/                  # API Client
    │   ├── components/           # Vue Components
    │   ├── stores/               # Pinia Stores
    │   ├── views/                # Vue Views
    │   └── router/               # Vue Router
    └── vite.config.js
```

## Fitur Core

- ✅ Manajemen Profil Instansi/Sekolah
- ✅ Manajemen Data Siswa
- ✅ Manajemen Data Pegawai/Guru
- ✅ Manajemen Kelas
- ✅ Manajemen Tahun Ajaran & Semester
- ✅ Sarana & Prasarana (Facility)
- ✅ Persuratan (Correspondence): statistik, disposisi, lampiran, import/export
- ✅ Inventaris (Inventory): kategori, item, transaksi, maintenance, peminjaman, report
- ✅ Multi-tenant System (setiap sekolah terisolasi)
- ✅ Authentication & Authorization
- ✅ API Rate Limiting (auth 5 req/menit, protected 60 req/menit)
- ✅ Error Handling & Logging
- ✅ Responsive UI

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

# (Opsional) Buat symlink storage untuk download file (export/lampiran/logo)
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

- Backend API (base): http://localhost:8000/api/v1
- Frontend: http://localhost:5173

## API Documentation

Semua endpoint menggunakan versi: **`/api/v1`**.  
Protected routes membutuhkan header: **`Authorization: Bearer <token>`**.

### Authentication

- `POST /api/v1/register` - Registrasi institusi baru (Rate limit: 5 requests/minute)
- `POST /api/v1/login` - Login user (Rate limit: 5 requests/minute)
- `POST /api/v1/forgot-password` - Minta reset password (Rate limit: 5 requests/minute)
- `POST /api/v1/reset-password` - Reset password (Rate limit: 5 requests/minute)
- `POST /api/v1/verify-email` - Verifikasi email (Rate limit: 5 requests/minute)
- `POST /api/v1/resend-verification` - Kirim ulang verifikasi email (Rate limit: 5 requests/minute)
- `POST /api/v1/refresh-token` - Refresh access token (Rate limit: 5 requests/minute)
- `POST /api/v1/logout` - Logout user (Protected)
- `GET /api/v1/me` - Get current user (Protected)

### Institution

- `GET /api/v1/institution` - List semua institusi (Admin only, Protected)
- `GET /api/v1/institution/my` - Get institusi sendiri (Protected)
- `GET /api/v1/institution/{id}` - Get detail institusi (Protected)
- `POST /api/v1/institution` - Create institusi (Admin only, Protected)
- `PUT /api/v1/institution/{id}` - Update institusi (Protected)
- `DELETE /api/v1/institution/{id}` - Delete institusi (Admin only, Protected)
- `PUT /api/v1/institution/{id}/active-academic-year` - Set tahun ajaran aktif (Protected)
- `POST /api/v1/institution/{id}/logo` - Upload logo (Protected)
- `GET /api/v1/institution/{id}/logo` - Get logo (Protected)

### Student

- `GET /api/v1/student` - List siswa (Protected, Rate limit: 60 requests/minute)
- `GET /api/v1/student/{id}` - Get detail siswa (Protected)
- `POST /api/v1/student` - Create siswa (Protected)
- `PUT /api/v1/student/{id}` - Update siswa (Protected)
- `DELETE /api/v1/student/{id}` - Delete siswa (Protected)
- `POST /api/v1/student/import` - Import siswa (Protected)
- `POST /api/v1/student/{id}/documents` - Upload dokumen siswa (Protected)
- `DELETE /api/v1/student/{id}/documents/{documentId}` - Hapus dokumen siswa (Protected)
- `GET /api/v1/student/{id}/documents/{documentId}/download` - Download dokumen siswa (Protected)

### Employee (Pegawai/Guru)

- `GET /api/v1/employee` - List pegawai (Protected, Rate limit: 60 requests/minute)
- `GET /api/v1/employee/{id}` - Get detail pegawai (Protected)
- `POST /api/v1/employee` - Create pegawai (Protected)
- `PUT /api/v1/employee/{id}` - Update pegawai (Protected)
- `DELETE /api/v1/employee/{id}` - Delete pegawai (Protected)
- `POST /api/v1/employee/import` - Import pegawai (Protected)
- `POST /api/v1/employee/{id}/documents` - Upload dokumen pegawai (Protected)
- `DELETE /api/v1/employee/{id}/documents/{documentId}` - Hapus dokumen pegawai (Protected)
- `GET /api/v1/employee/{id}/documents/{documentId}/download` - Download dokumen pegawai (Protected)

### Class

- `GET /api/v1/class` - List kelas (Protected)
- `GET /api/v1/class/{id}` - Detail kelas (Protected)
- `POST /api/v1/class` - Buat kelas (Protected)
- `PUT /api/v1/class/{id}` - Update kelas (Protected)
- `DELETE /api/v1/class/{id}` - Hapus kelas (Protected)
- `GET /api/v1/class/{id}/available-students` - List siswa yang bisa ditambahkan (Protected)
- `GET /api/v1/class/{id}/students` - List siswa dalam kelas (Protected)
- `POST /api/v1/class/{id}/students` - Tambah siswa ke kelas (Protected)
- `DELETE /api/v1/class/{id}/students/{studentId}` - Remove siswa dari kelas (Protected)

### Academic Year & Semester

- `GET /api/v1/academic-years` - List tahun ajaran (Protected)
- `GET /api/v1/academic-years/{id}` - Detail tahun ajaran (Protected)
- `GET /api/v1/semesters` - List semester (Protected)
- `GET /api/v1/semesters/active` - Semester aktif (Protected)
- `GET /api/v1/semesters/academic-year/{academicYearId}` - Semester per tahun ajaran (Protected)

### Facility (Sarana & Prasarana)

- `GET|POST|PUT|DELETE /api/v1/facility/lands` - CRUD lahan (Protected)
- `GET|POST|PUT|DELETE /api/v1/facility/buildings` - CRUD bangunan (Protected)
- `GET|POST|PUT|DELETE /api/v1/facility/rooms` - CRUD ruang (Protected)

### Inventory (Inventaris)

- `GET|POST|PUT|DELETE /api/v1/inventory/categories` - CRUD kategori inventaris (Protected)
- `GET|POST|PUT|DELETE /api/v1/inventory/items` - CRUD item inventaris (Protected)
- `GET|POST /api/v1/inventory/transactions` - List & buat transaksi (Protected)
- `GET /api/v1/inventory/transactions/{transaction}` - Detail transaksi (Protected)
- `GET|POST /api/v1/inventory/maintenances` - List & buat maintenance (Protected)
- `GET|PUT /api/v1/inventory/maintenances/{maintenance}` - Detail & update maintenance (Protected)
- `GET|POST /api/v1/inventory/loans` - List & buat peminjaman (Protected)
- `GET /api/v1/inventory/loans/{loan}` - Detail peminjaman (Protected)
- `POST /api/v1/inventory/loans/{loan}/return` - Pengembalian (Protected)
- `GET /api/v1/inventory/reports/*` - Statistik & laporan inventaris (Protected)

### Correspondence (Persuratan)

- `GET|POST|PUT|DELETE /api/v1/correspondence` - CRUD surat (Protected)
- `GET /api/v1/correspondence/statistics` - Statistik persuratan (Protected)
- `GET /api/v1/correspondence/export/excel|pdf` - Export (Protected)
- `GET /api/v1/correspondence/export/download/{filePath}` - Download export Excel (Protected)
- `GET /api/v1/correspondence/export/pdf/{filePath}` - Download export PDF (Protected)
- `POST /api/v1/correspondence/import` - Import persuratan (Protected)
- `GET /api/v1/correspondence/import/template` - Download template import (Protected)
- `GET|POST|PUT|DELETE /api/v1/correspondence/{correspondenceId}/attachments` - Lampiran (Protected)
- `GET|POST|PUT|DELETE /api/v1/correspondence/{correspondenceId}/dispositions` - Disposisi (Protected)

### Report

- `GET /api/v1/report/institution/{institutionId?}` - Statistik laporan (Protected)

## Security Features

- ✅ Laravel Sanctum Authentication
- ✅ Rate Limiting (5 req/min untuk auth, 60 req/min untuk API)
- ✅ Input Validation
- ✅ SQL Injection Protection (Eloquent ORM)
- ✅ XSS Protection
- ✅ CORS Configuration
- ✅ Error Logging

## Best Practices

- ✅ API Resources untuk response yang konsisten
- ✅ Proper Error Handling dengan try-catch
- ✅ Database Indexes untuk performa
- ✅ Logging untuk debugging
- ✅ Validation messages dalam Bahasa Indonesia
- ✅ Transaction untuk operasi database yang kompleks

## Troubleshooting

Lihat file `PENTING.md` dan `SETUP.md` untuk panduan lengkap troubleshooting.
