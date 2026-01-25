# Implementasi Laravel Policy dan Standarisasi File Upload
## Tanggal: 25 Januari 2026

Dokumen ini menjelaskan implementasi Laravel Policy untuk authorization dan standarisasi validasi file upload.

---

## ✅ 1. Laravel Policy untuk Authorization

### 1.1 CorrespondencePolicy
**File:** `backend/app/Policies/CorrespondencePolicy.php`

Policy ini mengatur authorization untuk semua operasi pada model Correspondence.

**Methods yang diimplementasikan:**
- `viewAny()` - Semua user authenticated bisa melihat list
- `view()` - Super admin/admin bisa lihat semua, user biasa hanya institusi sendiri
- `create()` - Semua user authenticated bisa create
- `update()` - Super admin/admin bisa update semua, user biasa hanya institusi sendiri
- `delete()` - Super admin/admin bisa delete semua, user biasa hanya institusi sendiri
- `restore()` - Super admin/admin bisa restore semua, user biasa hanya institusi sendiri
- `forceDelete()` - Hanya super admin
- `approve()` - Hanya admin/super admin, dengan validasi status
- `send()` - Hanya admin/super admin, dengan validasi status
- `archive()` - Super admin/admin bisa archive semua, user biasa hanya institusi sendiri

### 1.2 Registration
**File:** `backend/bootstrap/app.php`

Policy diregister menggunakan `withPolicies()`:
```php
->withPolicies([
    \App\Models\Correspondence::class => \App\Policies\CorrespondencePolicy::class,
])
```

### 1.3 Usage di Controller
**File:** `backend/app/Http/Controllers/API/CorrespondenceController.php`

Semua authorization checks diganti dari manual check menjadi menggunakan Policy:

**Sebelum:**
```php
if (!$request->user()->isAdminOrSuperAdmin() && 
    $correspondence->institution_id !== $request->user()->institution_id) {
    return response()->json(['message' => 'Unauthorized'], 403);
}
```

**Sesudah:**
```php
$this->authorize('view', $correspondence);
```

**Methods yang diupdate:**
- `show()` - menggunakan `authorize('view')`
- `update()` - menggunakan `authorize('update')`
- `destroy()` - menggunakan `authorize('delete')`
- `restore()` - menggunakan `authorize('restore')`
- `approve()` - menggunakan `authorize('approve')`
- `send()` - menggunakan `authorize('send')`
- `archive()` - menggunakan `authorize('archive')`
- `print()` - menggunakan `authorize('view')`

**Keuntungan:**
1. ✅ Code lebih clean dan DRY (Don't Repeat Yourself)
2. ✅ Konsistensi authorization di semua endpoint
3. ✅ Mudah di-maintain dan di-test
4. ✅ Mengurangi risiko lupa menambahkan authorization check
5. ✅ Bisa digunakan di Blade views juga dengan `@can` directive

---

## ✅ 2. Standarisasi Validasi File Upload

### 2.1 FileUploadRules Helper
**File:** `backend/app/Helpers/FileUploadRules.php`

Helper class untuk standarisasi validasi file upload di seluruh aplikasi.

**Constants:**
- **File Types:**
  - `TYPE_DOCUMENT` - PDF, DOC, DOCX
  - `TYPE_IMAGE` - JPG, JPEG, PNG
  - `TYPE_MIXED` - PDF, DOC, DOCX, JPG, JPEG, PNG
  - `TYPE_PDF_ONLY` - PDF only
  - `TYPE_IMAGE_ONLY` - JPG, JPEG, PNG only

- **Size Limits (in KB):**
  - `SIZE_SMALL` - 2048 KB (2MB)
  - `SIZE_MEDIUM` - 5120 KB (5MB)
  - `SIZE_LARGE` - 10240 KB (10MB)

**Methods:**
- `rules()` - Get validation rules untuk single file
- `multipleRules()` - Get validation rules untuk multiple files
- `messages()` - Get custom validation messages
- `correspondenceFile()` - Helper untuk correspondence file (PDF, 5MB)
- `correspondenceAttachments()` - Helper untuk correspondence attachments (Mixed, 10MB, max 10 files)
- `studentDocument()` - Helper untuk student document (Mixed, 2MB)
- `employeeDocument()` - Helper untuk employee document (PDF only, 2MB)
- `inventoryImage()` - Helper untuk inventory image (Image only, 2MB)

### 2.2 Implementasi di Request Classes

#### StoreCorrespondenceRequest & UpdateCorrespondenceRequest
**Sebelum:**
```php
'file' => 'nullable|file|mimes:pdf|max:5120', // Max 5MB
```

**Sesudah:**
```php
use App\Helpers\FileUploadRules;

// Di rules()
$rules = array_merge($rules, FileUploadRules::correspondenceFile(false));

// Di messages()
$messages = array_merge($messages, FileUploadRules::messages(
    FileUploadRules::TYPE_PDF_ONLY,
    FileUploadRules::SIZE_MEDIUM,
    'file',
    false
));
```

#### StoreInventoryRequest & UpdateInventoryRequest
**Sebelum:**
```php
'image' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
```

**Sesudah:**
```php
use App\Helpers\FileUploadRules;

// Di rules()
$rules = array_merge($rules, FileUploadRules::inventoryImage());
```

### 2.3 Implementasi di Controllers

#### AttachmentController
**Sebelum:**
```php
$request->validate([
    'files' => 'required|array|min:1|max:10',
    'files.*' => 'required|file|max:10240|mimes:pdf,doc,docx,jpg,jpeg,png',
], [
    'files.required' => 'Minimal 1 file harus diunggah',
    // ... banyak messages
]);
```

**Sesudah:**
```php
$rules = \App\Helpers\FileUploadRules::correspondenceAttachments();
$messages = \App\Helpers\FileUploadRules::messages(
    \App\Helpers\FileUploadRules::TYPE_MIXED,
    \App\Helpers\FileUploadRules::SIZE_LARGE,
    'files',
    true
);
$request->validate($rules, $messages);
```

#### StudentController
**Sebelum:**
```php
$request->validate([
    'file' => 'required|file|mimes:jpg,jpeg,png,pdf|max:2048',
    // ...
]);
```

**Sesudah:**
```php
$rules = array_merge(
    \App\Helpers\FileUploadRules::studentDocument(),
    ['name' => 'required|string|max:255', 'description' => 'nullable|string']
);
$messages = \App\Helpers\FileUploadRules::messages(
    \App\Helpers\FileUploadRules::TYPE_MIXED,
    \App\Helpers\FileUploadRules::SIZE_SMALL,
    'file',
    false
);
$request->validate($rules, $messages);
```

#### EmployeeController
**Sebelum:**
```php
$request->validate([
    'file' => 'required|file|mimes:pdf|max:2048',
    // ...
]);
```

**Sesudah:**
```php
$rules = array_merge(
    \App\Helpers\FileUploadRules::employeeDocument(),
    ['name' => 'required|string|max:255', 'description' => 'nullable|string']
);
$messages = \App\Helpers\FileUploadRules::messages(
    \App\Helpers\FileUploadRules::TYPE_PDF_ONLY,
    \App\Helpers\FileUploadRules::SIZE_SMALL,
    'file',
    false
);
$request->validate($rules, $messages);
```

**Keuntungan:**
1. ✅ Konsistensi validasi di seluruh aplikasi
2. ✅ Mudah di-maintain - ubah di satu tempat, semua terupdate
3. ✅ Standardized error messages
4. ✅ Type-safe dengan constants
5. ✅ Reusable untuk use case baru

---

## 📋 Standarisasi File Upload per Fitur

| Fitur | File Type | Max Size | Max Files | Field Name |
|-------|-----------|----------|-----------|------------|
| Correspondence File | PDF only | 5MB | 1 | `file` |
| Correspondence Attachments | Mixed (PDF, DOC, DOCX, JPG, JPEG, PNG) | 10MB | 10 | `files` |
| Student Document | Mixed (JPG, JPEG, PNG, PDF) | 2MB | 1 | `file` |
| Employee Document | PDF only | 2MB | 1 | `file` |
| Inventory Image | Image only (JPG, JPEG, PNG) | 2MB | 1 | `image` |

---

## 🔄 Migration Path

### Untuk menambahkan Policy baru:
1. Buat Policy class: `php artisan make:policy ModelNamePolicy --model=ModelName`
2. Implementasikan methods yang diperlukan
3. Register di `bootstrap/app.php` dengan `withPolicies()`
4. Update controller untuk menggunakan `$this->authorize()`

### Untuk menambahkan file upload validation baru:
1. Tambahkan helper method di `FileUploadRules` jika perlu
2. Gunakan helper method di Request class atau Controller
3. Pastikan konsisten dengan standar yang sudah ada

---

## ✅ Testing Checklist

- [x] Policy terdaftar dengan benar
- [x] Authorization checks bekerja di semua endpoint
- [x] File upload validation konsisten
- [x] Error messages user-friendly
- [x] Semua file upload endpoints menggunakan helper
- [ ] Unit test untuk Policy
- [ ] Integration test untuk file upload

---

## 📝 Catatan

1. **Policy Auto-Discovery:** Laravel 11 bisa auto-discover policies jika naming convention sesuai (ModelNamePolicy untuk ModelName), tapi kita register manual untuk lebih explicit.

2. **File Upload Security:** 
   - File name sudah di-sanitize di Service layer (dilakukan sebelumnya)
   - MIME type validation dilakukan di validation layer
   - File size limit di-enforce di validation layer

3. **Extensibility:**
   - Mudah menambahkan Policy untuk model lain
   - Mudah menambahkan file type/size baru di helper
   - Helper bisa di-extend dengan method baru sesuai kebutuhan

---

**Status:** ✅ **COMPLETED** - Policy dan standarisasi file upload sudah diimplementasikan.
