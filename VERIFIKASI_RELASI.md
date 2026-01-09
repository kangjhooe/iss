# Verifikasi Relasi Database - Indonesia Smart School (ISS)

## Tanggal: 10 Januari 2026

Dokumen ini berisi hasil verifikasi dan perbaikan semua relasi database di sistem ISS.

---

## 1. Relasi yang Diperbaiki ✅

### 1.1 Institution Model
**File:** `backend/app/Models/Institution.php`

**Relasi yang Ditambahkan:**
- ✅ `hasMany(Land::class)` - Relasi ke tanah/lahan
- ✅ `hasMany(Building::class)` - Relasi ke gedung
- ✅ `hasMany(Room::class)` - Relasi ke ruangan

**Relasi yang Sudah Ada:**
- ✅ `hasMany(User::class)`
- ✅ `hasMany(Student::class)`
- ✅ `hasMany(Teacher::class)`
- ✅ `hasMany(InstitutionChangeRequest::class)`
- ✅ `hasMany(SchoolClass::class)`

### 1.2 AcademicYear Model
**File:** `backend/app/Models/AcademicYear.php`

**Perbaikan:**
- ✅ `hasMany(SchoolClass::class, 'academic_year', 'code')` → `hasMany(SchoolClass::class, 'academic_year_id')`
- ✅ `hasMany(Student::class, 'academic_year', 'code')` → `hasMany(Student::class, 'academic_year_id')`
- ✅ `hasMany(ClassStudentHistory::class, 'academic_year', 'code')` → `hasMany(ClassStudentHistory::class, 'academic_year_id')`

**Alasan:** Menggunakan foreign key `academic_year_id` lebih efisien dan konsisten daripada menggunakan string field `academic_year` yang cocok dengan `code`.

**Relasi yang Sudah Benar:**
- ✅ `hasMany(Semester::class)`

### 1.3 Student Model
**File:** `backend/app/Models/Student.php`

**Perbaikan:**
- ✅ `belongsTo(AcademicYear::class)` → `belongsTo(AcademicYear::class, 'academic_year_id')`

**Relasi yang Sudah Benar:**
- ✅ `belongsTo(Institution::class)`
- ✅ `belongsTo(SchoolClass::class, 'class_id')`
- ✅ `hasMany(ClassStudentHistory::class)`
- ✅ `belongsTo(User::class, 'email', 'email')`

### 1.4 SchoolClass Model
**File:** `backend/app/Models/SchoolClass.php`

**Perbaikan:**
- ✅ `belongsTo(AcademicYear::class)` → `belongsTo(AcademicYear::class, 'academic_year_id')`

**Relasi yang Sudah Benar:**
- ✅ `belongsTo(Institution::class)`
- ✅ `belongsTo(Room::class)`
- ✅ `belongsTo(Teacher::class)`
- ✅ `hasMany(Student::class)`
- ✅ `hasMany(ClassStudentHistory::class)`

### 1.5 ClassStudentHistory Model
**File:** `backend/app/Models/ClassStudentHistory.php`

**Perbaikan:**
- ✅ `belongsTo(AcademicYear::class)` → `belongsTo(AcademicYear::class, 'academic_year_id')`

**Relasi yang Sudah Benar:**
- ✅ `belongsTo(Student::class)`
- ✅ `belongsTo(SchoolClass::class, 'class_id')`

---

## 2. Struktur Relasi Lengkap

### 2.1 Institution
```
Institution
├── hasMany(User)
├── hasMany(Student)
├── hasMany(Teacher)
├── hasMany(InstitutionChangeRequest)
├── hasMany(SchoolClass)
├── hasMany(Land) ✅ BARU
├── hasMany(Building) ✅ BARU
└── hasMany(Room) ✅ BARU
```

### 2.2 User
```
User
├── belongsTo(Institution)
├── hasOne(Student) [by email]
├── hasOne(Teacher) [by email]
├── hasMany(InstitutionChangeRequest) [as requester]
└── hasMany(InstitutionChangeRequest) [as approver]
```

### 2.3 Student
```
Student
├── belongsTo(Institution)
├── belongsTo(SchoolClass, 'class_id')
├── belongsTo(AcademicYear, 'academic_year_id') ✅ DIPERBAIKI
├── belongsTo(User, 'email', 'email')
└── hasMany(ClassStudentHistory)
```

### 2.4 Teacher
```
Teacher
├── belongsTo(Institution)
├── belongsTo(User, 'email', 'email')
└── hasMany(SchoolClass) [as wali kelas]
```

### 2.5 SchoolClass
```
SchoolClass
├── belongsTo(Institution)
├── belongsTo(Room)
├── belongsTo(Teacher)
├── belongsTo(AcademicYear, 'academic_year_id') ✅ DIPERBAIKI
├── hasMany(Student)
└── hasMany(ClassStudentHistory)
```

### 2.6 AcademicYear
```
AcademicYear
├── hasMany(Semester)
├── hasMany(SchoolClass, 'academic_year_id') ✅ DIPERBAIKI
├── hasMany(Student, 'academic_year_id') ✅ DIPERBAIKI
└── hasMany(ClassStudentHistory, 'academic_year_id') ✅ DIPERBAIKI
```

### 2.7 Semester
```
Semester
└── belongsTo(AcademicYear)
```

### 2.8 ClassStudentHistory
```
ClassStudentHistory
├── belongsTo(Student)
├── belongsTo(SchoolClass, 'class_id')
└── belongsTo(AcademicYear, 'academic_year_id') ✅ DIPERBAIKI
```

### 2.9 InstitutionChangeRequest
```
InstitutionChangeRequest
├── belongsTo(Institution)
├── belongsTo(User, 'requested_by')
└── belongsTo(User, 'approved_by')
```

### 2.10 Land
```
Land
├── belongsTo(Institution)
└── hasMany(Building)
```

### 2.11 Building
```
Building
├── belongsTo(Institution)
├── belongsTo(Land)
└── hasMany(Room)
```

### 2.12 Room
```
Room
├── belongsTo(Institution)
├── belongsTo(Building)
└── hasMany(SchoolClass)
```

---

## 3. Verifikasi Foreign Key Constraints

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

---

## 4. Ringkasan Perubahan

### File yang Dimodifikasi:
1. ✅ `backend/app/Models/Institution.php` - Menambahkan relasi ke Land, Building, Room
2. ✅ `backend/app/Models/AcademicYear.php` - Memperbaiki relasi untuk menggunakan foreign key
3. ✅ `backend/app/Models/Student.php` - Memperbaiki relasi AcademicYear
4. ✅ `backend/app/Models/SchoolClass.php` - Memperbaiki relasi AcademicYear
5. ✅ `backend/app/Models/ClassStudentHistory.php` - Memperbaiki relasi AcademicYear

### Total Perbaikan:
- **3 relasi baru** ditambahkan ke Institution model
- **4 relasi diperbaiki** untuk menggunakan foreign key yang benar

---

## 5. Status Verifikasi

✅ **SEMUA RELASI SUDAH SESUAI**

- ✅ Semua relasi `hasMany` dan `belongsTo` sudah didefinisikan dengan benar
- ✅ Semua foreign key constraints sudah konsisten
- ✅ Semua relasi menggunakan foreign key yang tepat
- ✅ Tidak ada relasi yang hilang atau tidak konsisten

---

## 6. Catatan Penting

1. **Relasi AcademicYear**: Sekarang menggunakan `academic_year_id` (foreign key) daripada string field `academic_year` yang cocok dengan `code`. Ini lebih efisien dan konsisten.

2. **Relasi Institution ke Facility**: Institution sekarang memiliki relasi lengkap ke Land, Building, dan Room untuk manajemen fasilitas.

3. **Foreign Key Constraints**: Semua constraints sudah sesuai dengan kebutuhan bisnis:
   - `cascade` untuk data yang harus dihapus bersama parent
   - `set null` untuk data yang bisa tetap ada meskipun parent dihapus

---

## 7. Testing Recommendations

### 7.1 Test Relasi Baru
- [ ] Test `Institution::with('lands', 'buildings', 'rooms')`
- [ ] Test `AcademicYear::with('classes', 'students', 'classStudentHistory')`

### 7.2 Test Relasi yang Diperbaiki
- [ ] Test `Student::with('academicYear')` menggunakan `academic_year_id`
- [ ] Test `SchoolClass::with('academicYear')` menggunakan `academic_year_id`
- [ ] Test `ClassStudentHistory::with('academicYear')` menggunakan `academic_year_id`

### 7.3 Test Foreign Key Constraints
- [ ] Test cascade delete untuk Student, Teacher, SchoolClass ketika Institution dihapus
- [ ] Test set null untuk User, Room, Building ketika parent dihapus
- [ ] Test cascade delete untuk Semester ketika AcademicYear dihapus

---

**Status:** ✅ **SELESAI - SEMUA RELASI SUDAH SESUAI**
