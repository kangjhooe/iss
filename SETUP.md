# Panduan Setup Indonesia Smart School (ISS)

## Deployment (Production)

- **URL:** https://sicerdik.kangjhooe.com
- **Path server:** `public_html/sicerdik`

Pastikan backend dan frontend dikonfigurasi untuk domain ini (lihat bagian konfigurasi production di bawah).

## Persyaratan

- PHP >= 8.2
- Composer
- Node.js >= 18
- MySQL/PostgreSQL
- XAMPP (sudah terinstall)

## Setup Backend (Laravel 12)

1. **Masuk ke folder backend**
```bash
cd backend
```

2. **Install dependencies**
```bash
composer install
```

3. **Setup environment**
```bash
cp .env.example .env
php artisan key:generate
```

4. **Konfigurasi database di `.env`**
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=iss_db
DB_USERNAME=root
DB_PASSWORD=
```

5. **Jalankan migration**
```bash
php artisan migrate
```

6. **Jalankan server**
```bash
php artisan serve
```

Backend akan berjalan di: http://localhost:8000

## Setup Frontend (Vue 3 + Vite)

1. **Masuk ke folder frontend**
```bash
cd frontend
```

2. **Install dependencies**
```bash
npm install
```

3. **Jalankan development server**
```bash
npm run dev
```

Frontend akan berjalan di: http://localhost:5173

## Konfigurasi Production (sicerdik.kangjhooe.com)

### Backend (Laravel)

Di server, path: `public_html/sicerdik/backend`

1. **Atur `.env` untuk production**
```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://sicerdik.kangjhooe.com

# Sesuaikan dengan kredensial database production
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=...
DB_USERNAME=...
DB_PASSWORD=...
```

2. **Document root untuk Laravel**  
   Arahkan domain/subdomain ke folder `public_html/sicerdik/backend/public` (atau gunakan subdomain/alias terpisah untuk API, misalnya `api.sicerdik.kangjhooe.com` → `backend/public`).

### Frontend (Vue)

Di server, path: `public_html/sicerdik/frontend`

1. **Build production**
```bash
cd frontend
npm run build
```

2. **URL API di production**  
   Set `VITE_API_BASE_URL` sebelum build. Untuk sicerdik.kangjhooe.com, buat/ubah `.env.production`:
```env
VITE_API_BASE_URL=https://sicerdik.kangjhooe.com/api
```
   (Jika API di subdomain terpisah, gunakan URL tersebut, misalnya `https://api.sicerdik.kangjhooe.com/api`.)

3. **Document root untuk frontend**  
   Arahkan `sicerdik.kangjhooe.com` ke folder hasil build, misalnya `public_html/sicerdik/frontend/dist` (atau salin isi `dist` ke `public_html/sicerdik` jika itu document root).

## Struktur Database

### Tabel `institution`
- Menyimpan data sekolah/institusi
- Setiap sekolah memiliki data profil lengkap

### Tabel `user`
- Menyimpan data pengguna sistem
- Setiap user terhubung ke satu institusi
- Role: admin, institution_admin, teacher, student

### Tabel `student`
- Menyimpan data siswa
- Terhubung ke institusi

### Tabel `teacher`
- Menyimpan data guru
- Terhubung ke institusi

## API Endpoints

Semua endpoint memakai prefix **`/api/v1`**. Daftar lengkap ada di **[API.md](API.md)**.

### Contoh (ringkas)
- **Auth:** `POST /api/v1/register`, `POST /api/v1/login`, `POST /api/v1/logout`, `GET /api/v1/me`
- **Institution:** `GET /api/v1/institution/my`, `GET|POST|PUT|DELETE /api/v1/institution`, ...
- **Student:** `GET|POST|PUT|DELETE /api/v1/student`, ...
- **Employee:** `GET|POST|PUT|DELETE /api/v1/employee`, ...

Protected route membutuhkan header: `Authorization: Bearer <token>`.

## Fitur yang Tersedia

✅ Registrasi sekolah baru & Login/Logout  
✅ Manajemen profil instansi  
✅ CRUD data siswa & guru/pegawai  
✅ Kelas, tahun ajaran, semester, jadwal, nilai, raport  
✅ Absensi pegawai & siswa (termasuk **QR absensi**)  
✅ **Kalender akademik** & pengingat  
✅ Persuratan, inventaris, perpustakaan, arsip digital  
✅ **PWA** (install ke perangkat, offline-aware)  
✅ Multi-tenant (setiap sekolah data terisolasi)

## Catatan

- **Production:** Aplikasi live di https://sicerdik.kangjhooe.com (path: `public_html/sicerdik`)
- Pastikan backend berjalan sebelum frontend (development)
- Token authentication menggunakan Laravel Sanctum
- Setiap sekolah hanya bisa mengakses data sekolahnya sendiri
- Admin bisa mengakses semua data
