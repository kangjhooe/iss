# Panduan Deploy Hosting

**URL (same-origin):**
- FE: `https://servr.in`
- API: `https://servr.in/api`
- Storage: `https://servr.in/storage/...`

---

## Struktur repo = struktur hosting

Clone/pull repo ke `/public_html/servr.in/`:

```text
/public_html/servr.in/          ← root git
  public/                       ← document root domain servr.in
    index.php                   ← Laravel (path ke ../backend)
    .htaccess                   ← /api → Laravel, sisanya SPA
    index.html, assets/, …      ← FE (hasil build)
    storage/                    ← symlink (dibuat di hosting)
  backend/                      ← Laravel (.env, app, storage, vendor, …)
  frontend/                     ← source FE (build di lokal)
```

Di cPanel: document root `servr.in` → `/public_html/servr.in/public`

---

## Update rutin (setelah struktur sudah benar)

### Lokal
```bash
cd frontend
npm run build          # build + sync ke /public
git add public
git commit -m "Update FE production build"
git push
```

### Hosting
```bash
cd /path/ke/servr.in   # root repo
git pull
cd backend
php artisan migrate --force   # jika ada migrasi
php artisan optimize:clear
```

Tidak perlu salin manual `dist` lagi — isi FE sudah di folder `public/`.

---

## Setup pertama kali

1. Backup DB + `backend/storage`
2. Pastikan document root domain = folder `public/`
3. `git pull`
4. Edit `backend/.env` (jangan timpa buta):
```env
APP_URL=https://servr.in
FRONTEND_URL=https://servr.in
CORS_ALLOWED_ORIGINS=https://servr.in,https://www.servr.in
SANCTUM_STATEFUL_DOMAINS=servr.in,www.servr.in
SESSION_DOMAIN=.servr.in
COOKIE_DOMAIN=.servr.in
```
5. Symlink storage (dari folder `public/`):
```bash
ln -sfn ../backend/storage/app/public storage
```
   atau di File Manager buat symlink `public/storage` → `../backend/storage/app/public`
6. Dari `backend/`:
```bash
php artisan optimize:clear
php artisan config:cache
```
7. Tes: `https://servr.in`, `https://servr.in/api/v1`, login, file `/storage/...`
8. Opsional: arahkan `api.servr.in` ke `public/` yang sama, atau redirect ke `servr.in`

---

## Checklist

- [ ] Document root = `.../servr.in/public`
- [ ] `public/index.php` + `.htaccess` ada
- [ ] `public/storage` symlink OK
- [ ] `.env`: `APP_URL=https://servr.in`
- [ ] `git pull` setelah push FE/backend
- [ ] Test login + API + storage

---

## Troubleshooting

| Gejala | Tindakan |
|--------|----------|
| `/api` jadi halaman Vue | `.htaccess` belum benar / cache |
| 500 di API | Cek path di `public/index.php` → `../backend/...` |
| Logo 404 | Symlink `storage` + `APP_URL` |
| FE lama | Belum `npm run build` + push `public/` |

Lihat juga `backend/.env.production.example`.
