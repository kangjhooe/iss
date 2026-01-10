# Swagger/OpenAPI Documentation Setup

## Masalah
Command `php artisan l5-swagger:generate` tidak terdaftar di Laravel 12.

## Solusi yang Sudah Diterapkan

### 1. Service Provider Registration
Service provider sudah ditambahkan di `bootstrap/app.php`:
```php
->withProviders([
    \L5Swagger\L5SwaggerServiceProvider::class,
])
```

### 2. Custom Command
Custom command sudah dibuat di `routes/console.php`:
```bash
php artisan swagger:generate
```

### 3. Auto-Generate Configuration
Di `config/l5-swagger.php`, `generate_always` sudah diset ke `true`:
```php
'generate_always' => env('L5_SWAGGER_GENERATE_ALWAYS', true),
```

Ini berarti dokumentasi akan otomatis di-generate setiap kali Swagger UI diakses.

## Cara Menggunakan

### Opsi 1: Akses Swagger UI (Auto-Generate)
1. Pastikan server Laravel berjalan:
   ```bash
   php artisan serve
   ```

2. Akses Swagger UI di browser:
   ```
   http://localhost:8000/api/documentation
   ```

   Dokumentasi akan otomatis di-generate saat pertama kali diakses.

### Opsi 2: Generate Manual dengan Custom Command
```bash
php artisan swagger:generate
```

### Opsi 3: Generate via Route (jika command masih tidak bekerja)
Akses route berikut untuk trigger generation:
```
http://localhost:8000/api/documentation
```

## Menambahkan Annotations

Tambahkan Swagger annotations di controller Anda. Contoh:

```php
/**
 * @OA\Get(
 *     path="/api/v1/student",
 *     summary="Get list of students",
 *     tags={"Student"},
 *     security={{"sanctum": {}}},
 *     @OA\Parameter(
 *         name="search",
 *         in="query",
 *         description="Search term",
 *         required=false,
 *         @OA\Schema(type="string")
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Successful operation",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Student"))
 *         )
 *     )
 * )
 */
public function index(Request $request)
{
    // ...
}
```

## Troubleshooting

### Jika Swagger UI tidak muncul:
1. Clear cache:
   ```bash
   php artisan config:clear
   php artisan cache:clear
   ```

2. Pastikan file `storage/api-docs/api-docs.json` ada dan bisa diakses

3. Cek log untuk error:
   ```bash
   tail -f storage/logs/laravel.log
   ```

### Jika annotations tidak muncul:
1. Pastikan annotations ada di file yang di-scan (default: `app/` directory)
2. Cek format annotations sesuai OpenAPI 3.0
3. Pastikan `@OA\Info()` annotation ada di salah satu controller

## Referensi
- [L5-Swagger Documentation](https://github.com/DarkaOnLine/L5-Swagger)
- [OpenAPI Specification](https://swagger.io/specification/)
