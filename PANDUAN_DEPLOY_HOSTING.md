# Panduan Deploy Hosting

Panduan singkat untuk update aplikasi ke shared hosting (FTP / File Manager).

**Target URL (same-origin):**
- FE: `https://servr.in`
- API: `https://servr.in/api` (Laravel `/api/v1/...`)
- Storage: `https://servr.in/storage/...`

---

## Struktur folder di shared hosting

Document root `servr.in` = **Laravel `public/` + isi build FE** digabung:

```text
~/
  backend/                      ← kode Laravel (di luar document root, ideal)
    app/
    bootstrap/
    config/
    database/
    resources/
    routes/
    storage/                    ← JANGAN ditimpa (data produksi)
    vendor/
    .env                        ← JANGAN ditimpa; edit manual
    artisan
    composer.json
    public/  ─── symlink / sama dengan ───┐
                                          │
  public_html/                  ← document root servr.in
    index.php                   ← Laravel (dari backend/public)
    .htaccess                   ← SPA + /api → Laravel (dari backend/public)
    storage/                    ← symlink ke backend/storage/app/public
    index.html                  ← FE (dari frontend/dist)
    assets/                     ← FE
    favicon.ico, robots.txt, …  ← FE
```

Kalau hosting tidak memungkinkan Laravel di luar `public_html`, alternatif umum:

```text
public_html/
  backend/          ← full Laravel (app, .env, storage, …)
  index.php         ← salinan/isi dari backend/public
  .htaccess
  storage/
  index.html        ← FE
  assets/
```

Pastikan `index.php` masih mengarah ke folder Laravel yang benar (`require __DIR__.'/../backend/vendor/autoload.php'` dst. — sesuaikan path).

### Domain `api.servr.in` (lama)

Setelah cutover, arahkan `api.servr.in` ke document root yang sama, **atau** buat redirect ke `https://servr.in` (lebih bersih).

---

## Ringkasan alur update rutin

1. Backup database + folder `backend/storage` di hosting
2. Update FE: `git pull` lalu salin isi `frontend/dist/` ke document root (**jangan** timpa `index.php` / `.htaccess` / `storage/`)
3. Update backend: `git pull` (atau upload folder yang diganti; jangan timpa `.env` & `storage`)
4. Pastikan `.env` server: `APP_URL=https://servr.in`
5. `php artisan migrate` + clear cache

---

## 1. Persiapan (lokal)

### Backup di hosting (wajib)

- Export database (phpMyAdmin → Export)
- Backup folder `backend/storage` (logo, surat, foto, dll.)

### Frontend — build production (lalu commit `dist`)

Folder `frontend/dist/` **ikut di-repo** agar hosting cukup `git pull`.

```bash
cd frontend
npm install
npm run build
```

Pastikan `frontend/.env.production` sebelum build:

```env
VITE_API_BASE_URL=/api
VITE_APP_URL=https://servr.in
VITE_APP_NAME=servr.in
```

Commit + push `frontend/dist/` (bersama perubahan FE). Salah URL → build ulang, commit ulang `dist`, push.

---

## 2. Deploy Frontend (via git pull)

Di hosting (SSH / Terminal), dari root repo:

```bash
git pull
```

Lalu salin **isi** `frontend/dist/` ke document root (`public_html/`), tanpa menimpa file Laravel:

```bash
# Contoh: sesuaikan path document root Anda
rsync -a --exclude 'index.php' --exclude '.htaccess' --exclude 'storage' \
  frontend/dist/ ~/public_html/
```

Atau manual (File Manager / cp): ganti `index.html`, `assets/`, icon, `robots.txt`, dll. — **jangan** timpa `index.php`, `.htaccess`, `storage/`.

| Aksi | Keterangan |
|------|------------|
| **Ganti** | Isi dari `frontend/dist/` |
| **Jangan timpa** | `index.php`, `.htaccess`, folder `storage/` |
| **Tidak perlu di document root** | `src/`, `node_modules/`, `package.json` |

Hard-refresh (Ctrl+F5) atau clear cache PWA setelah sync.

---

## 3. Deploy Backend — mana diganti, mana dipertahankan

### Diganti / di-overwrite

| Folder / file | Keterangan |
|---------------|------------|
| `app/` | Controller, model, service, dll. |
| `bootstrap/` | Kecuali isi `bootstrap/cache` — clear di server |
| `config/` | Konfigurasi Laravel |
| `database/migrations/` | Migrasi baru |
| `database/seeders/` | Jika dipakai |
| `resources/` | View Blade |
| `routes/` | Route API |
| `public/index.php`, `public/.htaccess` | Upload ke document root |
| `composer.json` + `composer.lock` | Lock wajib ikut |
| `artisan` | CLI Laravel |

### Dipertahankan (JANGAN ditimpa)

| Folder / file | Alasan |
|---------------|--------|
| `.env` | Kredensial; edit manual (lihat bawah) |
| `storage/` | Upload user, log, session |
| `vendor/` | Update lewat Composer / upload terpisah |

### `.env` production (edit di server, jangan overwrite buta)

Samakan dengan `backend/.env.production.example`, minimal:

```env
APP_URL=https://servr.in
FRONTEND_URL=https://servr.in
CORS_ALLOWED_ORIGINS=https://servr.in,https://www.servr.in
SANCTUM_STATEFUL_DOMAINS=servr.in,www.servr.in
SESSION_DOMAIN=.servr.in
COOKIE_DOMAIN=.servr.in
```

### `public/` / document root — hati-hati

- **Ganti:** `index.php`, `.htaccess` (versi SPA + `/api`)
- **Pertahankan:** symlink `storage/`
- Jangan hapus `storage` di document root jika sudah ter-link

### `vendor/`

**A. Ada SSH**

```bash
cd backend
composer install --no-dev --optimize-autoloader
```

**B. Tanpa Composer di hosting** — upload `vendor/` dari lokal setelah `composer install --no-dev` (PHP version mirip).

---

## 4. Setelah upload

Dari folder `backend` (SSH / Terminal cPanel):

```bash
php artisan migrate --force
php artisan storage:link
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### Tanpa SSH

1. Upload migrasi baru
2. SQL manual di phpMyAdmin bila perlu (`backend/database/migrations/MIGRATIONS_HOSTING.md`)
3. Permission `storage` + `bootstrap/cache` writable (`775` / `755`)

---

## 5. Checklist cutover first-time (subdomain → same-origin)

- [ ] Backup DB + `storage`
- [ ] Document root `servr.in` = Laravel public + FE
- [ ] `.htaccess` SPA + `/api` terpasang
- [ ] Symlink `storage` OK
- [ ] `.env`: `APP_URL=https://servr.in` (+ Sanctum/CORS di atas)
- [ ] Build FE dengan `VITE_API_BASE_URL=/api`, upload `dist`
- [ ] `php artisan optimize:clear` (+ cache)
- [ ] Test: login, panggil `/api/v1/...`, buka file `/storage/...`
- [ ] Opsional: redirect `api.servr.in` → `servr.in`

---

## 6. Checklist update rutin

- [ ] Backup DB + `storage`
- [ ] `npm run build` (`VITE_API_BASE_URL=/api`) + commit/push `frontend/dist`
- [ ] Di hosting: `git pull`, salin isi `frontend/dist` (jangan timpa `index.php` / `.htaccess` / `storage/`)
- [ ] Upload/pull folder backend yang diganti
- [ ] `.env` & `storage/` **tidak** tertimpa
- [ ] `php artisan migrate --force`
- [ ] `php artisan optimize:clear` (+ cache production)
- [ ] Test login + 1–2 fitur utama

---

## 7. Troubleshooting singkat

| Gejala | Kemungkinan | Tindakan |
|--------|-------------|----------|
| `/api/...` jadi halaman Vue | `.htaccess` belum versi SPA+API | Upload ulang `.htaccess` dari `backend/public` |
| Logo/file 404 | `APP_URL` masih `api.servr.in` atau symlink putus | Set `APP_URL=https://servr.in`, `storage:link` |
| Login/cookie gagal | Domain cookie / Sanctum | Cek `COOKIE_DOMAIN`, `SANCTUM_STATEFUL_DOMAINS` |
| FE masih hit `api.servr.in` | Build lama | Build ulang dengan `/api`, upload `dist` |
| API error "Unknown column" | Migrasi belum jalan | `php artisan migrate --force` |

---

## Referensi

- `SETUP.md` — setup & konfigurasi
- `RELEASE_CHECKLIST.md` — checklist rilis
- `backend/database/migrations/MIGRATIONS_HOSTING.md`
- `backend/.env.production.example`
