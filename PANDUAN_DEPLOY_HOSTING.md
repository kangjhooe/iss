# Panduan Deploy Hosting

Panduan singkat untuk update aplikasi ke shared hosting (FTP / File Manager). Fokus: **build frontend → upload `dist`**, **ganti file backend yang perlu**, **pertahankan data server**, lalu **jalankan migration**.

---

## Ringkasan alur

1. Backup database + folder `storage` di hosting
2. Build frontend di lokal → upload isi folder `dist`
3. Upload folder backend yang diganti (jangan timpa `.env` & `storage`)
4. Jalankan `php artisan migrate`
5. Clear cache Laravel (opsional tapi disarankan)

---

## 1. Persiapan (lokal)

### Backup di hosting (wajib)

Sebelum upload apa pun:

- Export database (phpMyAdmin → Export)
- Download/backup folder `backend/storage` (file upload user: logo, surat, foto, dll.)

### Frontend — build production

```bash
cd frontend
npm install
npm run build
```

Hasil build ada di: `frontend/dist/`

Pastikan `.env.production` sudah benar sebelum build, misalnya:

```env
VITE_API_BASE_URL=https://api.servr.in/api
VITE_APP_NAME=servr.in
```

> `VITE_*` di-bake saat build. Kalau URL API salah, harus build ulang lalu upload ulang `dist`.

---

## 2. Deploy Frontend

Upload **isi** folder `frontend/dist/` ke document root frontend di hosting (bukan folder `dist` itu sendiri, kecuali document root memang menunjuk ke `.../dist`).

Contoh struktur:

```text
public_html/          ← document root frontend
  index.html
  assets/
  ...
```

| Aksi | Keterangan |
|------|------------|
| **Ganti** | Seluruh isi document root frontend dengan isi `dist` terbaru |
| **Tidak perlu** | Source `src/`, `node_modules/`, `package.json` — tidak diupload ke hosting |

Setelah upload, hard-refresh browser (Ctrl+F5) atau clear cache PWA bila perlu.

---

## 3. Deploy Backend — mana diganti, mana dipertahankan

Path contoh: `public_html/.../backend/` atau folder API di subdomain.

### Diganti / di-overwrite (upload dari lokal)

| Folder / file | Keterangan |
|---------------|------------|
| `app/` | Controller, model, service, dll. |
| `bootstrap/` | Bootstrap app (kecuali isi `bootstrap/cache` — lebih aman di-clear di server) |
| `config/` | Konfigurasi Laravel |
| `database/migrations/` | File migrasi baru |
| `database/seeders/` | Jika ada seeder yang dipakai |
| `resources/` | View Blade (laporan/cetak, dll.) |
| `routes/` | Route API |
| `public/` | `index.php`, `.htaccess` (lihat catatan di bawah) |
| `composer.json` | Dependency |
| `composer.lock` | **Wajib ikut** agar versi package sama |
| `artisan` | CLI Laravel |

### Dipertahankan (JANGAN ditimpa)

| Folder / file | Alasan |
|---------------|--------|
| `.env` | Kredensial DB, `APP_KEY`, URL production, CORS, mail |
| `storage/` | File upload user, log, session, cache |
| `storage/app/public/` | Logo, surat PDF, foto, dokumen — **data produksi** |
| `storage/logs/` | Log error server |
| `vendor/` | Jangan hapus sembarangan; update lewat `composer install` (lihat bawah) |

### `public/` — hati-hati

- **Ganti:** `index.php`, `.htaccess`
- **Pertahankan:** symlink/folder `storage` di dalam `public` (hasil `php artisan storage:link`), dan file upload lain jika ada di situ
- Jangan hapus `public/storage` jika sudah ter-link ke `storage/app/public`

### `vendor/`

Pilih salah satu:

**A. Ada SSH / terminal di hosting**

```bash
cd backend
composer install --no-dev --optimize-autoloader
```

**B. Tidak ada Composer di hosting**

Upload folder `vendor/` dari lokal setelah `composer install --no-dev` di lokal (bisa besar; pastikan PHP version mirip).

---

## 4. Setelah upload backend

Jalankan di terminal hosting (SSH / Terminal cPanel), dari folder `backend`:

```bash
# 1. Migration (wajib setelah ada migrasi baru)
php artisan migrate --force

# 2. Pastikan symlink storage ada (sekali saja / jika hilang)
php artisan storage:link

# 3. Clear & rebuild cache
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### Tanpa SSH

1. Upload file migrasi ke `database/migrations/`
2. Jalankan SQL manual di phpMyAdmin sesuai migrasi yang belum jalan  
   (lihat juga `backend/database/migrations/MIGRATIONS_HOSTING.md`)
3. Pastikan permission folder `storage` dan `bootstrap/cache` writable (biasanya `775` atau `755` sesuai hosting)

---

## 5. Checklist cepat

- [ ] Backup DB + `storage`
- [ ] `npm run build` dengan `VITE_API_BASE_URL` benar
- [ ] Upload isi `frontend/dist` ke document root frontend
- [ ] Upload folder backend yang diganti
- [ ] `.env` & `storage/` **tidak** tertimpa
- [ ] `php artisan migrate --force`
- [ ] `php artisan storage:link` (jika perlu)
- [ ] `php artisan optimize:clear` (+ cache production)
- [ ] Test login, upload file, dan 1–2 fitur utama

---

## 6. Troubleshooting singkat

| Gejala | Kemungkinan | Tindakan |
|--------|-------------|----------|
| API error "Unknown column ..." | Migrasi belum jalan | `php artisan migrate --force` |
| Logo/file hilang | `storage` tertimpa / symlink putus | Restore backup `storage`, jalankan `storage:link` |
| Login/CORS gagal | `.env` salah atau tertimpa | Cek `FRONTEND_URL`, `CORS_ALLOWED_ORIGINS`, `SANCTUM_STATEFUL_DOMAINS` |
| Frontend masih versi lama | Cache browser/PWA atau `dist` belum ter-upload | Hard refresh; pastikan `index.html` + `assets/` baru |
| Frontend tidak ke API | Salah URL saat build | Perbaiki `.env.production`, build ulang, upload ulang `dist` |

---

## Referensi terkait

- `SETUP.md` — setup & konfigurasi production
- `RELEASE_CHECKLIST.md` — checklist sebelum rilis
- `backend/database/migrations/MIGRATIONS_HOSTING.md` — error kolom hilang / migrate di hosting
- `backend/.env.production.example` — contoh variabel `.env` production
