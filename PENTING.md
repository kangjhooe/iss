# ⚠️ PENTING - Sebelum Menjalankan Aplikasi

## Catatan tentang Folder Migrations

Laravel secara default mencari folder `database/migrations` (dengan huruf "s"). 

**Jika folder Anda bernama `migration` (tanpa "s"), Anda perlu:**

1. **Rename folder** dari `database/migration` menjadi `database/migrations`, ATAU

2. **Update konfigurasi** di `config/database.php` untuk mengubah path migrations

Untuk saat ini, pastikan folder migrations menggunakan nama standar Laravel: `database/migrations`

## Setup Database

1. Buat database baru di MySQL:
```sql
CREATE DATABASE iss_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

2. Update file `.env` dengan kredensial database Anda

3. Jalankan migrations:
```bash
php artisan migrate
```

## Urutan Migration

Pastikan migrations dijalankan dalam urutan:
1. `create_institution_table` (000000)
2. `create_user_table` (000001) 
3. `create_student_table` (000002)
4. `create_teacher_table` (000003)

## Testing API

Setelah backend berjalan, test endpoint:
- `GET http://localhost:8000` - Should return API info
- `POST http://localhost:8000/api/register` - Register new school
- `POST http://localhost:8000/api/login` - Login

## Troubleshooting

### Error: "Class 'Migration' not found"
- Pastikan sudah run `composer install`
- Pastikan folder migrations ada dan berisi file migration

### Error: "Table 'institution' doesn't exist"
- Pastikan migrations sudah dijalankan
- Cek urutan migration sudah benar

### CORS Error di Frontend
- Pastikan `config/cors.php` sudah dikonfigurasi dengan benar
- Pastikan `SANCTUM_STATEFUL_DOMAINS` di `.env` sudah benar
