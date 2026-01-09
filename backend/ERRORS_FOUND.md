# Error yang Ditemukan di Backend

## Ringkasan Error

Berikut adalah error-error yang ditemukan di backend berdasarkan log Laravel:

### 1. Database Tidak Ditemukan ⚠️ **KRITIS**
**Error:** `SQLSTATE[HY000] [1049] Unknown database 'iss_db'`

**Penyebab:** Database `iss_db` belum dibuat di MySQL.

**Solusi:**
```sql
CREATE DATABASE iss_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Atau melalui phpMyAdmin:
1. Buka phpMyAdmin (http://localhost/phpmyadmin)
2. Klik "New" untuk membuat database baru
3. Nama database: `iss_db`
4. Collation: `utf8mb4_unicode_ci`
5. Klik "Create"

Setelah database dibuat, jalankan migration:
```bash
cd backend
php artisan migrate
```

---

### 2. Cache Directory Tidak Writable ⚠️
**Error:** `The C:\xampp\htdocs\iss\backend\bootstrap\cache directory must be present and writable`

**Penyebab:** Direktori cache tidak memiliki permission write atau tidak ada.

**Solusi:**
```bash
cd backend
# Pastikan direktori ada dan writable
mkdir -p bootstrap/cache
chmod -R 775 bootstrap/cache
chmod -R 775 storage
```

Untuk Windows (PowerShell sebagai Administrator):
```powershell
# Pastikan direktori ada
New-Item -ItemType Directory -Force -Path "bootstrap\cache"
New-Item -ItemType Directory -Force -Path "storage\framework\cache"
New-Item -ItemType Directory -Force -Path "storage\framework\sessions"
New-Item -ItemType Directory -Force -Path "storage\framework\views"
New-Item -ItemType Directory -Force -Path "storage\logs"

# Set permission (jika diperlukan)
icacls "bootstrap\cache" /grant Users:F /T
icacls "storage" /grant Users:F /T
```

---

### 3. Application Encryption Key Tidak Ada ⚠️
**Error:** `No application encryption key has been specified`

**Penyebab:** File `.env` tidak memiliki `APP_KEY` atau belum di-generate.

**Solusi:**
```bash
cd backend
php artisan key:generate
```

Pastikan file `.env` ada. Jika tidak ada, copy dari `.env.example`:
```bash
cp .env.example .env
php artisan key:generate
```

---

### 4. Cache Path Tidak Valid ⚠️
**Error:** `Please provide a valid cache path`

**Penyebab:** Konfigurasi cache di `.env` atau `config/cache.php` tidak valid.

**Solusi:**
Pastikan di file `.env`:
```env
CACHE_DRIVER=file
SESSION_DRIVER=file
QUEUE_CONNECTION=sync
```

Dan pastikan direktori berikut ada:
- `storage/framework/cache`
- `storage/framework/sessions`
- `storage/framework/views`

---

### 5. Route Conflict (SUDAH DIPERBAIKI) ✅
**Masalah:** Route `/institution-change-requests/pending-count` bisa conflict dengan route resource.

**Solusi:** 
- Route spesifik sudah diletakkan sebelum `apiResource`
- Menambahkan `->except(['update', 'destroy'])` karena controller tidak memiliki method tersebut
- Menambahkan nama route untuk memudahkan debugging

**File yang diperbaiki:** `backend/routes/api.php`

---

## Langkah-Langkah Setup yang Disarankan

Setelah memperbaiki error di atas, jalankan langkah-langkah berikut:

```bash
cd backend

# 1. Copy file .env jika belum ada
cp .env.example .env

# 2. Generate APP_KEY
php artisan key:generate

# 3. Pastikan direktori storage dan cache writable
# (Lihat solusi di atas)

# 4. Pastikan database sudah dibuat
# (Lihat solusi di atas)

# 5. Jalankan migration
php artisan migrate

# 6. Clear cache
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# 7. Optimize (opsional, untuk production)
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

---

## Catatan Penting

1. **Database:** Pastikan MySQL/MariaDB sudah berjalan di XAMPP
2. **Environment:** Pastikan file `.env` sudah dikonfigurasi dengan benar (database name, username, password)
3. **Permissions:** Pastikan direktori `storage` dan `bootstrap/cache` memiliki permission write
4. **PHP Version:** Pastikan menggunakan PHP 8.1 atau lebih tinggi

---

## Testing

Setelah semua error diperbaiki, test API dengan:

```bash
# Test API info
curl http://localhost/iss/backend/public/api

# Test login (jika sudah ada user)
curl -X POST http://localhost/iss/backend/public/api/login \
  -H "Content-Type: application/json" \
  -d '{"email":"test@example.com","password":"password"}'
```

---

## File yang Diperbaiki

- ✅ `backend/routes/api.php` - Route conflict sudah diperbaiki

---

**Terakhir diperbarui:** 2026-01-09
