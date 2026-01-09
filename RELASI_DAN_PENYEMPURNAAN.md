# Penyempurnaan Relasi dan Codebase - Indonesia Smart School (ISS)

## Ringkasan
Dokumen ini menjelaskan semua perbaikan yang telah dilakukan pada relasi database dan penyempurnaan codebase secara keseluruhan.

## Tanggal: 9 Januari 2026

---

## 1. Penambahan Relasi yang Hilang ✅

### 1.1 Relasi Institution ke InstitutionChangeRequest
**File:** `backend/app/Models/Institution.php`

**Perubahan:**
- Menambahkan relasi `changeRequests()` menggunakan `hasMany(InstitutionChangeRequest::class)`
- Memungkinkan Institution untuk mengakses semua change requests yang terkait

```php
public function changeRequests()
{
    return $this->hasMany(InstitutionChangeRequest::class);
}
```

### 1.2 Relasi User ke Student dan Teacher (Berdasarkan Email)
**File:** `backend/app/Models/User.php`

**Perubahan:**
- Menambahkan relasi `studentProfile()` menggunakan `hasOne(Student::class, 'email', 'email')`
- Menambahkan relasi `teacherProfile()` menggunakan `hasOne(Teacher::class, 'email', 'email')`
- Memungkinkan User untuk mengakses profil Student atau Teacher jika email cocok

```php
public function studentProfile()
{
    return $this->hasOne(Student::class, 'email', 'email');
}

public function teacherProfile()
{
    return $this->hasOne(Teacher::class, 'email', 'email');
}
```

### 1.3 Relasi Student dan Teacher ke User
**File:** 
- `backend/app/Models/Student.php`
- `backend/app/Models/Teacher.php`

**Perubahan:**
- Menambahkan relasi `userAccount()` menggunakan `belongsTo(User::class, 'email', 'email')`
- Memungkinkan Student/Teacher untuk mengakses akun User terkait jika email cocok

```php
// Di Student.php
public function userAccount()
{
    return $this->belongsTo(User::class, 'email', 'email');
}

// Di Teacher.php
public function userAccount()
{
    return $this->belongsTo(User::class, 'email', 'email');
}
```

---

## 2. Penyempurnaan API Resources ✅

### 2.1 InstitutionResource
**File:** `backend/app/Http/Resources/InstitutionResource.php`

**Perubahan:**
- Menambahkan field untuk menampilkan relasi ketika dimuat:
  - `users_count` - Jumlah users
  - `students_count` - Jumlah students
  - `teachers_count` - Jumlah teachers
  - `users` - Array data users (ketika dimuat)
  - `students` - Array data students (ketika dimuat)
  - `teachers` - Array data teachers (ketika dimuat)
  - `change_requests` - Array data change requests (ketika dimuat)

**Contoh Response:**
```json
{
  "id": 1,
  "name": "Sekolah ABC",
  "users_count": 5,
  "students_count": 150,
  "teachers_count": 20,
  "users": [...],
  "students": [...],
  "teachers": [...]
}
```

### 2.2 UserResource
**File:** `backend/app/Http/Resources/UserResource.php`

**Perubahan:**
- Menambahkan field:
  - `email_verified_at` - Tanggal verifikasi email
  - `is_locked` - Status kunci akun
  - `failed_login_attempts` - Jumlah percobaan login gagal
  - `student_profile` - Profil student jika ada (ketika dimuat)
  - `teacher_profile` - Profil teacher jika ada (ketika dimuat)
  - `change_requests_count` - Jumlah change requests (ketika dimuat)

### 2.3 StudentResource dan TeacherResource
**File:** 
- `backend/app/Http/Resources/StudentResource.php`
- `backend/app/Http/Resources/TeacherResource.php`

**Perubahan:**
- Menambahkan field:
  - `has_user_account` - Boolean apakah memiliki akun user
  - `user_account` - Data akun user terkait (ketika dimuat)

---

## 3. Optimasi Eager Loading ✅

### 3.1 InstitutionController
**File:** `backend/app/Http/Controllers/API/InstitutionController.php`

**Perubahan:**
- Menambahkan parameter `with` untuk eager load relasi secara opsional
- Contoh: `GET /api/institution?with=users,students,teachers`
- Menggunakan `array_intersect()` untuk memastikan hanya relasi yang diizinkan yang dimuat

**Implementasi:**
```php
$with = [];
if ($request->has('with')) {
    $with = explode(',', $request->get('with'));
    $allowedRelations = ['users', 'students', 'teachers', 'changeRequests'];
    $with = array_intersect($with, $allowedRelations);
}

$institutions = $query->when(!empty($with), function ($q) use ($with) {
    return $q->with($with);
})->paginate($perPage);
```

---

## 4. Penambahan Scope Methods ✅

### 4.1 Institution Model
**File:** `backend/app/Models/Institution.php`

**Scope Methods Baru:**
- `scopeByLevel($query, string $level)` - Filter berdasarkan level
- `scopeByType($query, string $type)` - Filter berdasarkan type

**Helper Methods Baru:**
- `getStatistics()` - Mengembalikan statistik institusi:
  - users_count
  - students_count
  - teachers_count
  - active_students_count
  - active_teachers_count
  - pending_change_requests_count

### 4.2 Student Model
**File:** `backend/app/Models/Student.php`

**Scope Methods Baru:**
- `scopeByClass($query, string $class)` - Filter berdasarkan kelas
- `scopeByGender($query, string $gender)` - Filter berdasarkan gender
- `scopeByAcademicYear($query, string $academicYear)` - Filter berdasarkan tahun ajaran

**Helper Methods Baru:**
- `hasUserAccount()` - Cek apakah student memiliki akun user

### 4.3 Teacher Model
**File:** `backend/app/Models/Teacher.php`

**Scope Methods Baru:**
- `scopeByEmploymentStatus($query, string $status)` - Filter berdasarkan status kepegawaian
- `scopeByEducationLevel($query, string $level)` - Filter berdasarkan tingkat pendidikan
- `scopeBySubject($query, string $subject)` - Filter berdasarkan mata pelajaran

**Helper Methods Baru:**
- `hasUserAccount()` - Cek apakah teacher memiliki akun user

### 4.4 User Model
**File:** `backend/app/Models/User.php`

**Scope Methods Baru:**
- `scopeByRole($query, string $role)` - Filter berdasarkan role
- `scopeForInstitution($query, ?int $institutionId)` - Filter berdasarkan institusi
- `scopeActive($query)` - Filter user yang tidak terkunci

**Helper Methods Baru:**
- `isTeacher()` - Cek apakah user adalah teacher
- `isStudent()` - Cek apakah user adalah student

### 4.5 InstitutionChangeRequest Model
**File:** `backend/app/Models/InstitutionChangeRequest.php`

**Scope Methods Baru:**
- `scopeForInstitution($query, int $institutionId)` - Filter berdasarkan institusi
- `scopeForField($query, string $fieldName)` - Filter berdasarkan field name

**Helper Methods Baru:**
- `canBeApproved()` - Cek apakah request bisa disetujui
- `canBeRejected()` - Cek apakah request bisa ditolak

---

## 5. Verifikasi Foreign Key Constraints ✅

### 5.1 Konsistensi Constraints

**User Table:**
- `institution_id` → `onDelete('set null')` ✅
- Cocok untuk super_admin yang tidak memiliki institution_id

**Student Table:**
- `institution_id` → `onDelete('cascade')` ✅
- Ketika institution dihapus, semua students juga dihapus

**Teacher Table:**
- `institution_id` → `onDelete('cascade')` ✅
- Ketika institution dihapus, semua teachers juga dihapus

**InstitutionChangeRequest Table:**
- `institution_id` → `onDelete('cascade')` ✅
- `requested_by` → `onDelete('cascade')` ✅
- `approved_by` → `onDelete('set null')` ✅
- Cocok karena approved_by bisa null

---

## 6. Struktur Relasi Lengkap

### 6.1 Institution
```
Institution
├── hasMany(User)
├── hasMany(Student)
├── hasMany(Teacher)
└── hasMany(InstitutionChangeRequest)
```

### 6.2 User
```
User
├── belongsTo(Institution)
├── hasOne(Student) [by email]
├── hasOne(Teacher) [by email]
├── hasMany(InstitutionChangeRequest) [as requester]
└── hasMany(InstitutionChangeRequest) [as approver]
```

### 6.3 Student
```
Student
├── belongsTo(Institution)
└── belongsTo(User) [by email]
```

### 6.4 Teacher
```
Teacher
├── belongsTo(Institution)
└── belongsTo(User) [by email]
```

### 6.5 InstitutionChangeRequest
```
InstitutionChangeRequest
├── belongsTo(Institution)
├── belongsTo(User) [as requester]
└── belongsTo(User) [as approver]
```

---

## 7. Best Practices yang Diterapkan

### 7.1 Eager Loading
- ✅ Menggunakan `with()` untuk menghindari N+1 queries
- ✅ Menggunakan `whenLoaded()` di Resources untuk conditional loading
- ✅ Parameter `with` untuk kontrol relasi yang dimuat

### 7.2 Query Optimization
- ✅ Menggunakan `select()` untuk memilih kolom spesifik
- ✅ Menggunakan index yang tepat di migrations
- ✅ Menggunakan scope methods untuk query yang sering digunakan

### 7.3 Resource Transformation
- ✅ Menggunakan `whenLoaded()` untuk conditional data
- ✅ Menyediakan count fields untuk statistik
- ✅ Menyediakan nested resources untuk relasi

### 7.4 Model Methods
- ✅ Helper methods untuk business logic
- ✅ Scope methods untuk query filtering
- ✅ Type hints untuk better IDE support

---

## 8. Cara Menggunakan Fitur Baru

### 8.1 Eager Load Relasi di Institution
```php
// Di controller atau service
$institution = Institution::with(['users', 'students', 'teachers', 'changeRequests'])
    ->findOrFail($id);

// Atau melalui API
GET /api/institution/1?with=users,students,teachers
```

### 8.2 Menggunakan Scope Methods
```php
// Filter students by class
$students = Student::byClass('XII-A')->get();

// Filter teachers by employment status
$teachers = Teacher::byEmploymentStatus('PNS')->get();

// Filter institutions by level
$institutions = Institution::byLevel('SMA')->get();
```

### 8.3 Menggunakan Helper Methods
```php
// Cek apakah student memiliki akun user
if ($student->hasUserAccount()) {
    // ...
}

// Cek apakah user adalah teacher
if ($user->isTeacher()) {
    // ...
}

// Get statistics
$stats = $institution->getStatistics();
```

### 8.4 Mengakses Relasi Baru
```php
// Dari User ke Student/Teacher
$user = User::with('studentProfile')->find(1);
$studentProfile = $user->studentProfile;

// Dari Student/Teacher ke User
$student = Student::with('userAccount')->find(1);
$userAccount = $student->userAccount;

// Dari Institution ke ChangeRequests
$institution = Institution::with('changeRequests')->find(1);
$changeRequests = $institution->changeRequests;
```

---

## 9. Testing Recommendations

### 9.1 Test Relasi Baru
- ✅ Test relasi Institution → InstitutionChangeRequest
- ✅ Test relasi User → Student/Teacher (by email)
- ✅ Test relasi Student/Teacher → User (by email)

### 9.2 Test Eager Loading
- ✅ Test parameter `with` di InstitutionController
- ✅ Test N+1 query prevention
- ✅ Test conditional loading di Resources

### 9.3 Test Scope Methods
- ✅ Test semua scope methods baru
- ✅ Test kombinasi scope methods
- ✅ Test dengan pagination

### 9.4 Test Helper Methods
- ✅ Test `hasUserAccount()` untuk Student dan Teacher
- ✅ Test `getStatistics()` untuk Institution
- ✅ Test `canBeApproved()` dan `canBeRejected()` untuk ChangeRequest

---

## 10. Catatan Penting

### 10.1 Relasi Berdasarkan Email
- Relasi antara User dan Student/Teacher menggunakan email sebagai key
- Email harus unik dan cocok untuk relasi ini bekerja
- Jika email tidak cocok, relasi akan mengembalikan null

### 10.2 Soft Deletes
- Institution, Student, dan Teacher menggunakan soft deletes
- Foreign key constraints masih menggunakan `onDelete('cascade')` yang akan hard delete
- Pertimbangkan untuk menggunakan event listeners jika ingin soft delete behavior

### 10.3 Performance
- Eager loading sangat penting untuk menghindari N+1 queries
- Gunakan parameter `with` dengan bijak
- Monitor query performance dengan Laravel Debugbar atau Telescope

---

## 11. File yang Dimodifikasi

### Models
- ✅ `backend/app/Models/Institution.php`
- ✅ `backend/app/Models/User.php`
- ✅ `backend/app/Models/Student.php`
- ✅ `backend/app/Models/Teacher.php`
- ✅ `backend/app/Models/InstitutionChangeRequest.php`

### Resources
- ✅ `backend/app/Http/Resources/InstitutionResource.php`
- ✅ `backend/app/Http/Resources/UserResource.php`
- ✅ `backend/app/Http/Resources/StudentResource.php`
- ✅ `backend/app/Http/Resources/TeacherResource.php`

### Controllers
- ✅ `backend/app/Http/Controllers/API/InstitutionController.php`

---

## 12. Kesimpulan

Semua relasi telah diperbaiki dan disempurnakan:
- ✅ Relasi yang hilang telah ditambahkan
- ✅ API Resources telah diperbaiki untuk menampilkan relasi
- ✅ Eager loading telah dioptimasi
- ✅ Scope dan helper methods telah ditambahkan
- ✅ Foreign key constraints telah diverifikasi
- ✅ Best practices telah diterapkan

Codebase sekarang lebih konsisten, efisien, dan mudah digunakan!
