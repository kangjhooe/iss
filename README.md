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
│   └── routes/api.php            # API Routes
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
- ✅ Manajemen Data Guru
- ✅ Multi-tenant System (setiap sekolah terisolasi)
- ✅ Authentication & Authorization
- ✅ API Rate Limiting
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

- Backend API: http://localhost:8000
- Frontend: http://localhost:5173

## API Documentation

### Authentication

- `POST /api/register` - Registrasi institusi baru (Rate limit: 5 requests/minute)
- `POST /api/login` - Login user (Rate limit: 5 requests/minute)
- `POST /api/logout` - Logout user (Protected)
- `GET /api/me` - Get current user (Protected)

### Institution

- `GET /api/institution` - List semua institusi (Admin only, Protected)
- `GET /api/institution/my` - Get institusi sendiri (Protected)
- `GET /api/institution/{id}` - Get detail institusi (Protected)
- `POST /api/institution` - Create institusi (Admin only, Protected)
- `PUT /api/institution/{id}` - Update institusi (Protected)
- `DELETE /api/institution/{id}` - Delete institusi (Admin only, Protected)

### Student

- `GET /api/student` - List siswa (Protected, Rate limit: 60 requests/minute)
- `GET /api/student/{id}` - Get detail siswa (Protected)
- `POST /api/student` - Create siswa (Protected)
- `PUT /api/student/{id}` - Update siswa (Protected)
- `DELETE /api/student/{id}` - Delete siswa (Protected)

### Teacher

- `GET /api/teacher` - List guru (Protected, Rate limit: 60 requests/minute)
- `GET /api/teacher/{id}` - Get detail guru (Protected)
- `POST /api/teacher` - Create guru (Protected)
- `PUT /api/teacher/{id}` - Update guru (Protected)
- `DELETE /api/teacher/{id}` - Delete guru (Protected)

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
