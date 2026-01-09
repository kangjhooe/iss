# Panduan Setup Indonesia Smart School (ISS)

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

### Authentication
- `POST /api/register` - Registrasi sekolah baru
- `POST /api/login` - Login
- `POST /api/logout` - Logout
- `GET /api/me` - Get user yang sedang login

### Institution
- `GET /api/institution` - List semua institusi (admin)
- `GET /api/institution/my` - Get institusi sendiri
- `GET /api/institution/{id}` - Get detail institusi
- `POST /api/institution` - Create institusi (admin)
- `PUT /api/institution/{id}` - Update institusi
- `DELETE /api/institution/{id}` - Delete institusi (admin)

### Student
- `GET /api/student` - List siswa
- `GET /api/student/{id}` - Get detail siswa
- `POST /api/student` - Create siswa
- `PUT /api/student/{id}` - Update siswa
- `DELETE /api/student/{id}` - Delete siswa

### Teacher
- `GET /api/teacher` - List guru
- `GET /api/teacher/{id}` - Get detail guru
- `POST /api/teacher` - Create guru
- `PUT /api/teacher/{id}` - Update guru
- `DELETE /api/teacher/{id}` - Delete guru

## Fitur yang Tersedia

✅ Registrasi sekolah baru
✅ Login/Logout
✅ Manajemen profil instansi
✅ CRUD data siswa
✅ CRUD data guru
✅ Multi-tenant (setiap sekolah data terisolasi)
✅ Filter dan pencarian

## Catatan

- Pastikan backend berjalan sebelum frontend
- Token authentication menggunakan Laravel Sanctum
- Setiap sekolah hanya bisa mengakses data sekolahnya sendiri
- Admin bisa mengakses semua data
