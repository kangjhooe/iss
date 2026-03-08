# Checklist Deploy Backend (FE–BE & Purifier)

Pastikan perubahan berikut sudah ter-commit dan ter-deploy ke server.

## 1. Bootstrap & error lama (Application::share)

- **File:** `backend/bootstrap/app.php`
- **Isi:** Macro `Application::share()` untuk kompatibilitas paket lama (mis. l5-swagger).
- **Cek:** Baris 7–17 berisi `Application::macro('share', ...)`.

## 2. Purifier opsional (Class PurifierServiceProvider not found)

- **File:** `backend/composer.json`
- **Isi:** `"dont-discover": ["mews/purifier"]` di `extra.laravel`.
- **Cek:** Laravel tidak lagi memasukkan Purifier ke daftar provider otomatis.

- **File:** `backend/app/Providers/OptionalPurifierServiceProvider.php` (baru)
- **Isi:** Hanya memanggil `register()` ke `Mews\Purifier\PurifierServiceProvider` jika `class_exists(...)`.
- **Cek:** Tidak ada `use` ke class Mews; hanya `class_exists` + `$this->app->register()`.

- **File:** `backend/bootstrap/app.php`
- **Isi:** `withProviders([OptionalPurifierServiceProvider::class, L5Swagger\...])`.
- **Cek:** `OptionalPurifierServiceProvider` ada di urutan pertama.

- **File:** `backend/bootstrap/cache/services.php`
- **Isi:** Entri `Mews\Purifier\PurifierServiceProvider` dihapus dari `providers` dan `eager`.
- **Cek:** Tidak ada string `Mews\\Purifier` di file ini.

## 3. Controller tidak Fatal saat Purifier tidak terpasang

- **File:** `backend/app/Services/HtmlPurifier.php` (baru)
- **Isi:** Helper yang memakai `class_exists(\Mews\Purifier\Facades\Purifier::class)` sebelum memanggil Purifier; fallback `strip_tags()`.
- **Cek:** Tidak ada `use Mews\Purifier`; hanya pemanggilan dengan nama lengkap di dalam `class_exists`.

- **File:** `backend/app/Http/Controllers/API/QuestionBankController.php`
- **Isi:** `use App\Services\HtmlPurifier`, dan `sanitizeQuestionHtml()` memanggil `HtmlPurifier::sanitizeQuestion($html, 'question')`.
- **Cek:** Tidak ada `use Mews\Purifier`; tidak ada `Purifier::clean` langsung.

- **File:** `backend/app/Http/Controllers/API/QuestionStimulusController.php`
- **Isi:** Sama: `use App\Services\HtmlPurifier`, `sanitizeStimulusHtml()` memanggil `HtmlPurifier::sanitizeQuestion()`.
- **Cek:** Tidak ada `use Mews\Purifier`; tidak ada `Purifier::clean` langsung.

## 4. Config (tidak memuat class Mews)

- **File:** `backend/config/purifier.php`
- **Isi:** Hanya array + `storage_path()`, tidak ada reference ke class Mews.
- **Cek:** Aman di-load meskipun paket Purifier tidak terpasang.

## Alur setelah deploy

| Kondisi server                         | Hasil yang diharapkan |
|----------------------------------------|------------------------|
| `composer install` jalan, vendor lengkap | Purifier terdaftar via OptionalPurifierServiceProvider; sanitasi pakai Purifier. |
| Vendor tidak ada / Purifier tidak ada   | App tetap boot; sanitasi pakai fallback `strip_tags()` lewat `HtmlPurifier`. |
| Request ke `https://api.servr.in`       | Tidak lagi error 500 karena `share()` atau `PurifierServiceProvider not found`. |

## Opsional di server setelah deploy

```bash
cd backend
composer install --no-dev
php artisan config:clear
php artisan cache:clear
```

Ini memastikan cache config/cache tidak menyimpan reference lama. `bootstrap/cache/services.php` yang ikut deploy sudah tanpa Mews; jika nanti di server dijalankan `php artisan package:discover`, cache akan di-regenerate dan tetap tidak memasukkan Mews (karena `dont-discover`).
