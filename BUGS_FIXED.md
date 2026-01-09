# Bugs Fixed dan Perbaikan Relasi - Indonesia Smart School

## Tanggal: 10 Januari 2026

Dokumen ini berisi daftar bug yang ditemukan dan diperbaiki, serta verifikasi relasi database.

---

## 1. Bugs yang Diperbaiki ✅

### 1.1 InstitutionChangeRequest Model - Missing $table Property
**File:** `backend/app/Models/InstitutionChangeRequest.php`

**Masalah:**
- Model tidak memiliki property `$table` yang didefinisikan
- Laravel akan menggunakan konvensi plural (`institution_change_requests`) secara default, tetapi lebih baik didefinisikan eksplisit

**Perbaikan:**
```php
protected $table = 'institution_change_requests';
```

**Status:** ✅ Fixed

---

### 1.2 FacilityController - Validasi institution_id untuk Super Admin
**File:** `backend/app/Http/Controllers/API/FacilityController.php`

**Masalah:**
- Method `createBuilding()` dan `createRoom()` tidak memvalidasi bahwa super admin harus menyertakan `institution_id`
- Inkonsisten dengan `createLand()` yang sudah memvalidasi

**Perbaikan:**
- Menambahkan validasi untuk super admin di `createBuilding()` dan `createRoom()`
- Super admin sekarang harus menyertakan `institution_id` saat membuat building atau room
- Menambahkan error handling yang konsisten

**Status:** ✅ Fixed

---

### 1.3 User Model - Helper Method untuk Admin Check
**File:** `backend/app/Models/User.php`

**Masalah:**
- Tidak ada method untuk mengecek apakah user adalah admin atau super admin sekaligus
- Beberapa controller perlu mengecek kedua kondisi

**Perbaikan:**
- Menambahkan method `isAdminOrSuperAdmin()` untuk kemudahan penggunaan

**Status:** ✅ Fixed

---

## 2. Verifikasi Relasi Database ✅

### 2.1 Institution Model
**Relasi yang Terverifikasi:**
- ✅ `hasMany(User::class)`
- ✅ `hasMany(Student::class)`
- ✅ `hasMany(Teacher::class)`
- ✅ `hasMany(InstitutionChangeRequest::class)`
- ✅ `hasMany(SchoolClass::class)`
- ✅ `hasMany(Land::class)`
- ✅ `hasMany(Building::class)`
- ✅ `hasMany(Room::class)`
- ✅ `belongsTo(AcademicYear::class, 'active_academic_year_id')`
- ✅ `belongsTo(Semester::class, 'active_semester_id')`

### 2.2 User Model
**Relasi yang Terverifikasi:**
- ✅ `belongsTo(Institution::class)`
- ✅ `hasMany(InstitutionChangeRequest::class, 'requested_by')`
- ✅ `hasMany(InstitutionChangeRequest::class, 'approved_by')`
- ✅ `hasOne(Student::class, 'email', 'email')`
- ✅ `hasOne(Teacher::class, 'email', 'email')`

### 2.3 Student Model
**Relasi yang Terverifikasi:**
- ✅ `belongsTo(Institution::class)`
- ✅ `belongsTo(SchoolClass::class, 'class_id')`
- ✅ `belongsTo(AcademicYear::class, 'academic_year_id')`
- ✅ `belongsTo(User::class, 'email', 'email')`
- ✅ `hasMany(ClassStudentHistory::class)`

### 2.4 Teacher Model
**Relasi yang Terverifikasi:**
- ✅ `belongsTo(Institution::class)`
- ✅ `belongsTo(User::class, 'email', 'email')`
- ✅ `hasMany(SchoolClass::class)`

### 2.5 SchoolClass Model
**Relasi yang Terverifikasi:**
- ✅ `belongsTo(Institution::class)`
- ✅ `belongsTo(Room::class)`
- ✅ `belongsTo(Teacher::class)`
- ✅ `belongsTo(AcademicYear::class, 'academic_year_id')`
- ✅ `hasMany(Student::class, 'class_id')`
- ✅ `hasMany(ClassStudentHistory::class)`

### 2.6 AcademicYear Model
**Relasi yang Terverifikasi:**
- ✅ `hasMany(Semester::class)`
- ✅ `hasMany(SchoolClass::class, 'academic_year_id')`
- ✅ `hasMany(Student::class, 'academic_year_id')`
- ✅ `hasMany(ClassStudentHistory::class, 'academic_year_id')`

### 2.7 Semester Model
**Relasi yang Terverifikasi:**
- ✅ `belongsTo(AcademicYear::class)`

### 2.8 ClassStudentHistory Model
**Relasi yang Terverifikasi:**
- ✅ `belongsTo(Student::class)`
- ✅ `belongsTo(SchoolClass::class, 'class_id')`
- ✅ `belongsTo(AcademicYear::class, 'academic_year_id')`

### 2.9 InstitutionChangeRequest Model
**Relasi yang Terverifikasi:**
- ✅ `belongsTo(Institution::class)`
- ✅ `belongsTo(User::class, 'requested_by')`
- ✅ `belongsTo(User::class, 'approved_by')`

### 2.10 Land Model
**Relasi yang Terverifikasi:**
- ✅ `belongsTo(Institution::class)`
- ✅ `hasMany(Building::class)`

### 2.11 Building Model
**Relasi yang Terverifikasi:**
- ✅ `belongsTo(Institution::class)`
- ✅ `belongsTo(Land::class)`
- ✅ `hasMany(Room::class)`

### 2.12 Room Model
**Relasi yang Terverifikasi:**
- ✅ `belongsTo(Institution::class)`
- ✅ `belongsTo(Building::class)`
- ✅ `hasMany(SchoolClass::class)`

---

## 3. Verifikasi Foreign Key Constraints ✅

### 3.1 User Table
- ✅ `institution_id` → `onDelete('set null')` - Cocok untuk super_admin

### 3.2 Student Table
- ✅ `institution_id` → `onDelete('cascade')`
- ✅ `class_id` → `onDelete('set null')`
- ✅ `academic_year_id` → `onDelete('set null')`

### 3.3 Teacher Table
- ✅ `institution_id` → `onDelete('cascade')`

### 3.4 SchoolClass Table
- ✅ `institution_id` → `onDelete('cascade')`
- ✅ `room_id` → `onDelete('set null')`
- ✅ `teacher_id` → `onDelete('set null')`
- ✅ `academic_year_id` → `onDelete('set null')`

### 3.5 ClassStudentHistory Table
- ✅ `student_id` → `onDelete('cascade')`
- ✅ `class_id` → `onDelete('cascade')`
- ✅ `academic_year_id` → `onDelete('set null')`

### 3.6 InstitutionChangeRequest Table
- ✅ `institution_id` → `onDelete('cascade')`
- ✅ `requested_by` → `onDelete('cascade')`
- ✅ `approved_by` → `onDelete('set null')`

### 3.7 Semester Table
- ✅ `academic_year_id` → `onDelete('cascade')`

### 3.8 Land Table
- ✅ `institution_id` → `onDelete('cascade')`

### 3.9 Building Table
- ✅ `institution_id` → `onDelete('cascade')`
- ✅ `land_id` → `onDelete('set null')`

### 3.10 Room Table
- ✅ `institution_id` → `onDelete('cascade')`
- ✅ `building_id` → `onDelete('set null')`

### 3.11 Institution Table
- ✅ `active_academic_year_id` → `onDelete('set null')`
- ✅ `active_semester_id` → `onDelete('set null')`

---

## 4. Catatan Penting

### 4.1 Konsistensi Authorization Checks
Beberapa controller menggunakan `isAdmin()` yang hanya mengecek role 'admin', bukan 'super_admin'. Untuk akses yang memerlukan admin atau super admin, disarankan menggunakan:
- `$user->isAdmin() || $user->isSuperAdmin()`
- Atau method baru `$user->isAdminOrSuperAdmin()`

### 4.2 Relasi Email-based
Relasi antara User, Student, dan Teacher menggunakan email sebagai foreign key (bukan ID). Ini memungkinkan:
- User dapat memiliki profil Student atau Teacher jika email cocok
- Student/Teacher dapat mengakses akun User terkait

### 4.3 Academic Year ID vs String
Beberapa tabel memiliki kedua field:
- `academic_year` (string) - untuk kompatibilitas dengan data lama
- `academic_year_id` (foreign key) - untuk relasi yang lebih efisien

Relasi Eloquent menggunakan `academic_year_id` untuk performa yang lebih baik.

---

## 5. Rekomendasi untuk Masa Depan

1. **Standardisasi Authorization Checks:**
   - Pertimbangkan untuk membuat middleware atau trait untuk authorization checks yang konsisten
   - Gunakan `isAdminOrSuperAdmin()` di semua controller yang memerlukan akses admin

2. **Migration untuk Cleanup:**
   - Pertimbangkan untuk menghapus field `academic_year` (string) setelah semua data menggunakan `academic_year_id`
   - Buat migration untuk memastikan data konsisten

3. **Testing:**
   - Tambahkan unit tests untuk semua relasi
   - Tambahkan integration tests untuk authorization checks

---

**Status Keseluruhan:** ✅ Semua bug kritis telah diperbaiki, semua relasi telah diverifikasi
