# Bugs Fixed dan Perbaikan Relasi - Indonesia Smart School (ISS)

## Tanggal: 19 Januari 2026

Dokumen ini berisi daftar bug yang ditemukan dan diperbaiki dalam pemeriksaan keseluruhan sistem.

---

## 1. Bugs yang Diperbaiki ✅

### 1.1 Teacher Model - Tabel Tidak Sesuai
**File:** `backend/app/Models/Teacher.php`

**Masalah:**
- Model `Teacher` masih mengarah ke tabel `teacher` yang sudah di-rename menjadi `employee`
- Ini akan menyebabkan error saat query karena tabel `teacher` sudah tidak ada

**Perbaikan:**
- Mengubah `protected $table = 'teacher'` menjadi `protected $table = 'employee'`
- Menambahkan field `type` ke `$fillable`
- Menambahkan global scope untuk selalu filter `type = 'Guru'`
- Memperbaiki relasi `classes()` untuk menggunakan foreign key yang benar

**Status:** ✅ Fixed

---

### 1.2 StoreTeacherRequest - Validasi Tabel Salah
**File:** `backend/app/Http/Requests/StoreTeacherRequest.php`

**Masalah:**
- Validasi `unique:teacher,nuptk` masih menggunakan tabel `teacher` yang sudah tidak ada

**Perbaikan:**
- Mengubah menjadi `unique:employee,nuptk`

**Status:** ✅ Fixed

---

### 1.3 UpdateTeacherRequest - Validasi Tabel Salah
**File:** `backend/app/Http/Requests/UpdateTeacherRequest.php`

**Masalah:**
- Validasi `Rule::unique('teacher', 'nuptk')` masih menggunakan tabel `teacher`

**Perbaikan:**
- Mengubah menjadi `Rule::unique('employee', 'nuptk')`

**Status:** ✅ Fixed

---

### 1.4 TeacherController - Type Tidak Diset
**File:** `backend/app/Http/Controllers/API/TeacherController.php`

**Masalah:**
- Saat membuat teacher baru, field `type` tidak diset ke 'Guru'
- Ini bisa menyebabkan data tidak konsisten

**Perbaikan:**
- Menambahkan `$validated['type'] = 'Guru'` sebelum create

**Status:** ✅ Fixed

---

## 2. Verifikasi Relasi Database ✅

### 2.1 Institution Model
**Relasi yang Terverifikasi:**
- ✅ `hasMany(User::class)`
- ✅ `hasMany(Student::class)`
- ✅ `hasMany(Employee::class)->where('type', 'Guru')` (teachers)
- ✅ `hasMany(Employee::class)` (employees)
- ✅ `hasMany(InstitutionChangeRequest::class)`
- ✅ `hasMany(SchoolClass::class)`
- ✅ `hasMany(Land::class)`
- ✅ `hasMany(Building::class)`
- ✅ `hasMany(Room::class)`
- ✅ `belongsTo(AcademicYear::class, 'active_academic_year_id')`
- ✅ `belongsTo(Semester::class, 'active_semester_id')`
- ✅ `hasMany(Correspondence::class)`
- ✅ `hasMany(CorrespondenceCategory::class)`

### 2.2 User Model
**Relasi yang Terverifikasi:**
- ✅ `belongsTo(Institution::class)`
- ✅ `hasMany(InstitutionChangeRequest::class, 'requested_by')`
- ✅ `hasMany(InstitutionChangeRequest::class, 'approved_by')`
- ✅ `hasOne(Student::class, 'email', 'email')`
- ✅ `hasOne(Employee::class, 'email', 'email')->where('type', 'Guru')` (teacherProfile)
- ✅ `hasOne(Employee::class, 'email', 'email')` (employeeProfile)

### 2.3 Student Model
**Relasi yang Terverifikasi:**
- ✅ `belongsTo(Institution::class)`
- ✅ `belongsTo(SchoolClass::class, 'class_id')`
- ✅ `belongsTo(AcademicYear::class, 'academic_year_id')`
- ✅ `belongsTo(User::class, 'email', 'email')`
- ✅ `hasMany(ClassStudentHistory::class)`
- ✅ `hasMany(StudentDocument::class)`

### 2.4 Teacher Model
**Relasi yang Terverifikasi:**
- ✅ `belongsTo(Institution::class)`
- ✅ `belongsTo(User::class, 'email', 'email')`
- ✅ `hasMany(SchoolClass::class, 'teacher_id')`
- ✅ Global scope untuk filter `type = 'Guru'`

### 2.5 Employee Model
**Relasi yang Terverifikasi:**
- ✅ `belongsTo(Institution::class)`
- ✅ `belongsTo(User::class, 'email', 'email')`
- ✅ `hasMany(SchoolClass::class, 'teacher_id')`
- ✅ `hasMany(EmployeeEducation::class)`
- ✅ `hasMany(EmployeeDocument::class)`

### 2.6 SchoolClass Model
**Relasi yang Terverifikasi:**
- ✅ `belongsTo(Institution::class)`
- ✅ `belongsTo(Room::class)`
- ✅ `belongsTo(Employee::class, 'teacher_id')`
- ✅ `belongsTo(AcademicYear::class, 'academic_year_id')`
- ✅ `hasMany(Student::class, 'class_id')`
- ✅ `hasMany(ClassStudentHistory::class)`

### 2.7 AcademicYear Model
**Relasi yang Terverifikasi:**
- ✅ `hasMany(Semester::class)`
- ✅ `hasMany(SchoolClass::class, 'academic_year_id')`
- ✅ `hasMany(Student::class, 'academic_year_id')`
- ✅ `hasMany(ClassStudentHistory::class, 'academic_year_id')`

### 2.8 Semester Model
**Relasi yang Terverifikasi:**
- ✅ `belongsTo(AcademicYear::class)`

### 2.9 ClassStudentHistory Model
**Relasi yang Terverifikasi:**
- ✅ `belongsTo(Student::class)`
- ✅ `belongsTo(SchoolClass::class, 'class_id')`
- ✅ `belongsTo(AcademicYear::class, 'academic_year_id')`

### 2.10 InstitutionChangeRequest Model
**Relasi yang Terverifikasi:**
- ✅ `belongsTo(Institution::class)`
- ✅ `belongsTo(User::class, 'requested_by')`
- ✅ `belongsTo(User::class, 'approved_by')`

### 2.11 Land Model
**Relasi yang Terverifikasi:**
- ✅ `belongsTo(Institution::class)`
- ✅ `hasMany(Building::class)`

### 2.12 Building Model
**Relasi yang Terverifikasi:**
- ✅ `belongsTo(Institution::class)`
- ✅ `belongsTo(Land::class)`
- ✅ `hasMany(Room::class)`

### 2.13 Room Model
**Relasi yang Terverifikasi:**
- ✅ `belongsTo(Institution::class)`
- ✅ `belongsTo(Building::class)`
- ✅ `hasMany(SchoolClass::class)`

### 2.14 Correspondence Model
**Relasi yang Terverifikasi:**
- ✅ `belongsTo(Institution::class)`
- ✅ `belongsTo(CorrespondenceCategory::class, 'category_id')`
- ✅ `belongsTo(User::class, 'created_by')`
- ✅ `belongsTo(User::class, 'approved_by')`
- ✅ `hasMany(CorrespondenceDisposition::class)`
- ✅ `hasMany(CorrespondenceAttachment::class)`
- ✅ `hasMany(CorrespondenceHistory::class)`

### 2.15 CorrespondenceCategory Model
**Relasi yang Terverifikasi:**
- ✅ `belongsTo(Institution::class)`
- ✅ `hasMany(Correspondence::class, 'category_id')`

### 2.16 CorrespondenceDisposition Model
**Relasi yang Terverifikasi:**
- ✅ `belongsTo(Correspondence::class)`
- ✅ `belongsTo(User::class, 'from_user_id')`
- ✅ `belongsTo(User::class, 'to_user_id')`

### 2.17 CorrespondenceAttachment Model
**Relasi yang Terverifikasi:**
- ✅ `belongsTo(Correspondence::class)`

### 2.18 CorrespondenceHistory Model
**Relasi yang Terverifikasi:**
- ✅ `belongsTo(Correspondence::class)`
- ✅ `belongsTo(User::class)`

### 2.19 StudentDocument Model
**Relasi yang Terverifikasi:**
- ✅ `belongsTo(Student::class)`

### 2.20 EmployeeEducation Model
**Relasi yang Terverifikasi:**
- ✅ `belongsTo(Employee::class)`

### 2.21 EmployeeDocument Model
**Relasi yang Terverifikasi:**
- ✅ `belongsTo(Employee::class)`

---

## 3. Catatan Penting

### 3.1 Teacher vs Employee
- Tabel `teacher` sudah di-rename menjadi `employee` di migration
- Model `Teacher` sekarang menggunakan tabel `employee` dengan global scope `type = 'Guru'`
- Model `Employee` digunakan untuk semua jenis pegawai (Guru, Staff, dll)
- Relasi `SchoolClass::teacher()` menggunakan `Employee` model

### 3.2 Relasi Email-based
- Relasi antara User, Student, dan Employee menggunakan email sebagai foreign key
- Email harus unik dan cocok untuk relasi ini bekerja
- Jika email tidak cocok, relasi akan mengembalikan null

### 3.3 Academic Year ID vs String
- Beberapa tabel memiliki kedua field:
  - `academic_year` (string) - untuk kompatibilitas dengan data lama
  - `academic_year_id` (foreign key) - untuk relasi yang lebih efisien
- Relasi Eloquent menggunakan `academic_year_id` untuk performa yang lebih baik

---

## 4. File yang Dimodifikasi

### Models
- ✅ `backend/app/Models/Teacher.php` - Perbaikan tabel dan global scope

### Requests
- ✅ `backend/app/Http/Requests/StoreTeacherRequest.php` - Perbaikan validasi tabel
- ✅ `backend/app/Http/Requests/UpdateTeacherRequest.php` - Perbaikan validasi tabel

### Controllers
- ✅ `backend/app/Http/Controllers/API/TeacherController.php` - Memastikan type diset

---

## 5. Status Keseluruhan

✅ **SEMUA BUG KRITIS TELAH DIPERBAIKI**
✅ **SEMUA RELASI SUDAH SESUAI DAN KONSISTEN**

- ✅ Semua relasi `hasMany` dan `belongsTo` sudah didefinisikan dengan benar
- ✅ Semua foreign key constraints sudah konsisten
- ✅ Semua relasi menggunakan foreign key yang tepat
- ✅ Tidak ada relasi yang hilang atau tidak konsisten
- ✅ Model Teacher sudah disesuaikan dengan perubahan tabel employee

---

**Terakhir diperbarui:** 19 Januari 2026
