# Konfigurasi CORS untuk Production

## Masalah
Error: "Tidak ada response dari server" terjadi karena backend Laravel belum mengizinkan request dari `https://app.kangjhooe.com`.

## Solusi

### 1. Update file `.env` di server production (`api.kangjhooe.com`)

Tambahkan atau update baris berikut di file `.env` di backend:

```env
# CORS Configuration
CORS_ALLOWED_ORIGINS=https://app.kangjhooe.com
FRONTEND_URL=https://app.kangjhooe.com

# Sanctum Configuration (jika menggunakan Sanctum)
SANCTUM_STATEFUL_DOMAINS=app.kangjhooe.com,api.kangjhooe.com

# Session Domain (untuk cookie sharing jika diperlukan)
SESSION_DOMAIN=.kangjhooe.com
```

### 2. Clear config cache di Laravel

Setelah update `.env`, jalankan command berikut di server production:

```bash
cd /path/to/backend
php artisan config:clear
php artisan cache:clear
php artisan config:cache
```

### 3. Verifikasi CORS sudah aktif

Cek apakah CORS sudah benar dengan melihat response header. Di browser DevTools → Network → Request login, cek header:

- `Access-Control-Allow-Origin: https://app.kangjhooe.com` ✅
- `Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS` ✅
- `Access-Control-Allow-Headers: Content-Type, Authorization, Accept` ✅
- `Access-Control-Allow-Credentials: true` ✅ (karena `withCredentials: true`)

### 4. Troubleshooting

#### Jika masih error "Tidak ada response dari server":

1. **Cek apakah `api.kangjhooe.com` bisa diakses**
   ```bash
   curl -I https://api.kangjhooe.com/api/v1/login
   ```

2. **Cek log Laravel**
   ```bash
   tail -f storage/logs/laravel.log
   ```

3. **Cek apakah CORS middleware aktif**
   - Pastikan `HandleCors` middleware ada di `bootstrap/app.php`
   - Laravel 11+ sudah include secara default

4. **Test CORS dengan curl**
   ```bash
   curl -X OPTIONS https://api.kangjhooe.com/api/v1/login \
     -H "Origin: https://app.kangjhooe.com" \
     -H "Access-Control-Request-Method: POST" \
     -H "Access-Control-Request-Headers: Content-Type,Authorization" \
     -v
   ```
   
   Harus return header `Access-Control-Allow-Origin: https://app.kangjhooe.com`

### 5. Catatan Penting

- **Jangan set `Access-Control-Allow-Origin: *`** jika menggunakan `withCredentials: true`
- Pastikan URL di `CORS_ALLOWED_ORIGINS` **exact match** (termasuk `https://`)
- Setelah update `.env`, **wajib** clear cache dengan `php artisan config:clear`

## File yang Sudah Diupdate

1. ✅ `backend/config/cors.php` - Sekarang membaca dari env variable
2. ✅ `frontend/src/api/index.js` - Base URL menggunakan env variable
3. ✅ `frontend/.env.production` - Sudah di-set ke `https://api.kangjhooe.com/api`

## Langkah Selanjutnya

1. Update `.env` di production server dengan konfigurasi di atas
2. Clear config cache: `php artisan config:clear && php artisan config:cache`
3. Test login lagi dari `https://app.kangjhooe.com`
