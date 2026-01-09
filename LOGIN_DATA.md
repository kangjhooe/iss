# Data Login - Indonesia Smart School (ISS)

## Login

**Endpoint:** `POST /api/login`

**Request:**
```json
{
  "email": "admin@sekolah.com",
  "password": "password123"
}
```

**Response:** Mengembalikan `user`, `token`, dan `institution` (jika ada).

## Data Login Super Admin

**Email:** `superadmin@iss.id`  
**Password:** `admin123`  
**Role:** `super_admin`

**Cara membuat:** Jalankan seeder:
```bash
php artisan db:seed --class=SuperAdminSeeder
```

## Registrasi

**Endpoint:** `POST /api/register`

**Request:**
```json
{
  "institution_name": "Nama Sekolah",
  "institution_type": "sd|smp|sma|smk|ma",
  "institution_address": "Alamat Sekolah",
  "institution_phone": "081234567890",
  "institution_email": "sekolah@email.com",
  "name": "Nama Admin",
  "email": "admin@sekolah.com",
  "password": "password123",
  "password_confirmation": "password123"
}
```

## Role

| Role | Deskripsi |
|------|-----------|
| `super_admin` | Akses penuh ke semua institusi |
| `institution_admin` | Akses penuh ke data institusi sendiri |
| `teacher` | Akses terbatas ke data kelas/mata pelajaran |
| `student` | Akses terbatas ke data pribadi |

## Membuat Akun Manual (Database)

Password harus di-hash dengan bcrypt. Generate hash:
```bash
php artisan tinker
>>> Hash::make('password123')
```

Contoh SQL:
```sql
INSERT INTO `user` (`institution_id`, `name`, `email`, `password`, `role`, `created_at`, `updated_at`)
VALUES (NULL, 'Super Admin', 'superadmin@iss.id', '$2y$12$...', 'super_admin', NOW(), NOW());
```

## Validasi & Keamanan

- Email: wajib, format valid, terdaftar di database
- Password: wajib, di-hash dengan bcrypt
- Institusi: harus aktif (`is_active = true`)
- Rate limiting: 5 requests/minute per IP
- Token: Laravel Sanctum, simpan untuk request selanjutnya
- Logout: `POST /api/logout` dengan header `Authorization: Bearer {token}`
