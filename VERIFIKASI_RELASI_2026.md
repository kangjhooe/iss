# Verifikasi Relasi dan Perbaikan Bug - ISS

**Tanggal:** 2 Februari 2026

Dokumen ini merangkum hasil pengecekan relasi di seluruh proyek dan perbaikan bug yang dilakukan.

---

## 1. Relasi yang Diperbaiki / Ditambah

### 1.1 Model Achievement
**File:** `backend/app/Models/Achievement.php`

**Perbaikan:** Menambahkan relasi yang hilang ke `AcademicYear` dan `Semester`.

- `academicYear()` → `belongsTo(AcademicYear::class, 'academic_year_id')`
- `semester()` → `belongsTo(Semester::class, 'semester_id')`

Tabel `achievements` sudah memiliki kolom `academic_year_id` dan `semester_id`, tetapi relasi di model belum didefinisikan.

### 1.2 ViolationService – Eager load student.class
**File:** `backend/app/Services/ViolationService.php`

**Bug:** Eager load memakai `'student:id,name,nis,nisn,class'`. Di sini `class` dipakai sebagai nama kolom, sementara di resource yang dipakai adalah relasi `student->class` (SchoolClass). Akibatnya:
- Saat relasi tidak dimuat, `$this->student->class` bisa mengembalikan nilai kolom string, bukan objek kelas.
- Nama relasi di Student adalah `class()` (FK `class_id`), bukan kolom `class` untuk select.

**Perbaikan:** Eager load diubah menjadi:
- `'student' => fn ($q) => $q->select('id','name','nis','nisn','class_id')->with('class:id,name')`
Sehingga relasi `class` (SchoolClass) selalu dimuat dan resource mendapat objek kelas yang konsisten.

### 1.3 ViolationResource – Format student.class
**File:** `backend/app/Http/Resources/ViolationResource.php`

**Perbaikan:**
- `student.class` di-response hanya ditampilkan jika relasi `class` dimuat, dalam bentuk `{ id, name }`, agar response konsisten dan tidak mengekspos seluruh model.
- Menambah field opsional (whenLoaded): `academic_year`, `semester`, `school_class` agar API bisa mengembalikan data tahun ajaran, semester, dan kelas pelanggaran bila dimuat.

### 1.4 ViolationController – Eager load show
**File:** `backend/app/Http/Controllers/API/ViolationController.php`

**Perbaikan:** Pada `show()`, relasi yang dimuat diperluas menjadi:
- `$violation->load(['student.class:id,name', 'violationType', 'reporter'])`
Agar response ViolationResource untuk student.class tidak N+1 dan format kelas konsisten.

### 1.5 StudentResource – Field `class` konsisten
**File:** `backend/app/Http/Resources/StudentResource.php`

**Bug:** Field `'class' => $this->class` bisa berisi:
- Nilai string kolom `class` jika relasi `class` tidak dimuat, atau
- Objek SchoolClass (relasi) jika relasi dimuat.
Response API jadi tidak konsisten (kadang string, kadang object).

**Perbaikan:** `'class'` diisi dari nilai kolom saja:
- `'class' => $this->getRawOriginal('class')`
Detail kelas tetap lewat `class_detail` (whenLoaded `class`).

### 1.6 AchievementResource
**File:** `backend/app/Http/Resources/AchievementResource.php`

**Perbaikan:**
- Menambah field `academic_year_id` dan `semester_id` di response.
- Menambah `academic_year` dan `semester` (whenLoaded) agar API bisa mengembalikan objek tahun ajaran dan semester bila dimuat.

---

## 2. Relasi yang Diverifikasi (Sudah Benar)

- **Institution** – hasMany: User, Student, Employee (teachers), InstitutionChangeRequest, SchoolClass, Land, Building, Room, Correspondence, Subjects, LessonSchedule, Violation, ViolationType, Achievement, AchievementType, PointThreshold, StudentActionLog, StudentMutation (origin/target). belongsTo: AcademicYear, Semester (active).
- **User** – belongsTo Institution; hasOne Student/Employee (by email); hasMany InstitutionChangeRequest (requester, approver).
- **Student** – belongsTo Institution, SchoolClass (class_id), AcademicYear, Semester, User (email); hasMany ClassStudentHistory, StudentDocument, Violation, Achievement, StudentActionLog, StudentMutation.
- **Employee** – belongsTo Institution, User (email); hasMany SchoolClass (teacher_id), LessonSchedule, EmployeeEducation, EmployeeDocument.
- **SchoolClass** – belongsTo Institution, Room, Employee (teacher_id), AcademicYear, Semester; hasMany Student, ClassStudentHistory, LessonSchedule (class_id).
- **AcademicYear** – hasMany Semester, SchoolClass, Student, ClassStudentHistory.
- **Semester** – belongsTo AcademicYear; hasMany LessonSchedule.
- **Violation** – belongsTo Institution, Student, ViolationType, User (reported_by), AcademicYear, Semester, SchoolClass (class_id).
- **ViolationType** – belongsTo Institution; hasMany Violation.
- **Achievement** – belongsTo Institution, Student, AchievementType, User (given_by), AcademicYear, Semester.
- **AchievementType** – belongsTo Institution; hasMany Achievement.
- **LessonSchedule** – belongsTo Institution, Semester, SchoolClass (class_id), Subject, Employee, Room.
- **Subject** – belongsTo Institution; hasMany LessonSchedule.
- **StudentMutation** – belongsTo Institution (origin/target), Student, User (requester, approver).
- **ClassStudentHistory** – belongsTo Student, SchoolClass (class_id), AcademicYear, Semester.
- **Room, Building, Land** – relasi ke Institution/Building/Land dan SchoolClass/LessonSchedule sesuai struktur fasilitas.

Tidak ada relasi yang salah arah atau foreign key yang tidak sesuai.

---

## 3. Ringkasan File yang Diubah

| File | Perubahan |
|------|-----------|
| `app/Models/Achievement.php` | Tambah relasi `academicYear()`, `semester()` |
| `app/Models/Semester.php` | Tambah relasi invers: `classes()`, `students()`, `violations()`, `achievements()`, `classStudentHistory()` |
| `app/Models/AcademicYear.php` | Tambah relasi invers: `violations()`, `achievements()` |
| `app/Models/PointThreshold.php` | Tambah relasi `studentActionLogs()` |
| `app/Models/User.php` | Tambah relasi invers: `violationsReported()`, `achievementsGiven()`, `studentActionLogsRecorded()` |
| `app/Services/ViolationService.php` | Perbaikan eager load student + class |
| `app/Http/Resources/ViolationResource.php` | Format student.class, tambah whenLoaded academic_year, semester, school_class |
| `app/Http/Controllers/API/ViolationController.php` | Eager load student.class di show() |
| `app/Http/Controllers/API/AchievementController.php` | Eager load academicYear, semester di index/show/store |
| `app/Http/Resources/StudentResource.php` | Field `class` pakai getRawOriginal('class') |
| `app/Http/Resources/AchievementResource.php` | Tambah academic_year_id, semester_id, whenLoaded academicYear, semester |
| *(3 Feb 2026)* | |
| `app/Models/SchoolClass.php` | Perbaikan `studentHistory()` FK → `class_id`; tambah `grades()`, `teachingJournals()` |
| `app/Models/AcademicYear.php` | Tambah `grades()` |
| `app/Models/Semester.php` | Tambah `grades()`, `teachingJournals()` |
| `app/Models/Institution.php` | Tambah grades, teachingJournals, guestVisits, digitalArchiveCategories, digitalArchives, studentAttendances, employeeAttendances |
| `app/Models/User.php` | Tambah `guestVisitsCreated()`, `digitalArchivesCreated()` |
| `app/Models/Subject.php` | Tambah `grades()` |
| `app/Models/Employee.php` | Tambah `teachingJournals()`, `grades()` |
| `app/Models/LessonSchedule.php` | Tambah `teachingJournals()` |
| `app/Models/Student.php` | Tambah `grades()` |

---

## 4. Relasi Invers yang Ditambahkan (Lanjutan)

### 4.1 Semester
**File:** `backend/app/Models/Semester.php`

- `classes()` → `hasMany(SchoolClass::class, 'semester_id')`
- `students()` → `hasMany(Student::class, 'semester_id')`
- `violations()` → `hasMany(Violation::class, 'semester_id')`
- `achievements()` → `hasMany(Achievement::class, 'semester_id')`
- `classStudentHistory()` → `hasMany(ClassStudentHistory::class, 'semester_id')`

### 4.2 AcademicYear
**File:** `backend/app/Models/AcademicYear.php`

- `violations()` → `hasMany(Violation::class, 'academic_year_id')`
- `achievements()` → `hasMany(Achievement::class, 'academic_year_id')`

### 4.3 PointThreshold
**File:** `backend/app/Models/PointThreshold.php`

- `studentActionLogs()` → `hasMany(StudentActionLog::class, 'point_threshold_id')`

### 4.4 User
**File:** `backend/app/Models/User.php`

- `violationsReported()` → `hasMany(Violation::class, 'reported_by')`
- `achievementsGiven()` → `hasMany(Achievement::class, 'given_by')`
- `studentActionLogsRecorded()` → `hasMany(StudentActionLog::class, 'recorded_by')`

### 4.5 AchievementController
**File:** `backend/app/Http/Controllers/API/AchievementController.php`

- Eager load `academicYear` dan `semester` ditambahkan di index(), show(), dan store() agar response AchievementResource menampilkan tahun ajaran dan semester secara konsisten.

### 4.6 Relasi Modul Bimbingan Konseling (2 Feb 2026)
- **Institution:** `counselingTypes()`, `counselingSessions()`
- **Student:** `counselingSessions()`
- **User:** `counselingSessionsAsCounselor()` (counselor_id)
- **Semester:** `counselingSessions()`
- **AcademicYear:** `counselingSessions()`
- **SchoolClass:** `counselingSessions()` (class_id)

### 4.7 ClassRepository – students_count
**File:** `backend/app/Repositories/ClassRepository.php`

- `withCount('students')` ditambahkan pada list() agar response ClassResource menampilkan `students_count` tanpa N+1. Relasi `SchoolClass::students()` memakai FK `class_id` (bukan school_class_id).

---

## 5. Perbaikan Relasi (3 Februari 2026)

### 5.1 SchoolClass::studentHistory() – FK eksplisit
**File:** `backend/app/Models/SchoolClass.php`

- **Bug:** `hasMany(ClassStudentHistory::class)` memakai konvensi FK `school_class_id`, sementara tabel `class_student_history` memakai kolom `class_id` (constrained ke `class`).
- **Perbaikan:** `studentHistory()` diubah menjadi `hasMany(ClassStudentHistory::class, 'class_id')`.

### 5.2 Relasi invers modul Nilai, Jurnal Mengajar, Buku Tamu, Arsip Digital, Kehadiran
**File:** berbagai model

- **AcademicYear:** tambah `grades()` → `hasMany(Grade::class, 'academic_year_id')`.
- **Semester:** tambah `grades()` → `hasMany(Grade::class, 'semester_id')`; tambah `teachingJournals()` → `hasMany(TeachingJournal::class, 'semester_id')`.
- **Institution:** tambah `grades()`, `teachingJournals()`, `guestVisits()`, `digitalArchiveCategories()`, `digitalArchives()`, `studentAttendances()`, `employeeAttendances()`.
- **User:** tambah `guestVisitsCreated()` → `hasMany(GuestVisit::class, 'created_by')`; tambah `digitalArchivesCreated()` → `hasMany(DigitalArchive::class, 'created_by')`.
- **Subject:** tambah `grades()` → `hasMany(Grade::class, 'subject_id')`.
- **SchoolClass:** tambah `grades()` → `hasMany(Grade::class, 'class_id')`; tambah `teachingJournals()` → `hasMany(TeachingJournal::class, 'class_id')`.
- **Employee:** tambah `teachingJournals()` → `hasMany(TeachingJournal::class, 'employee_id')`; tambah `grades()` → `hasMany(Grade::class, 'employee_id')`.
- **LessonSchedule:** tambah `teachingJournals()` → `hasMany(TeachingJournal::class, 'lesson_schedule_id')`.
- **Student:** tambah `grades()` → `hasMany(Grade::class)`.

---

## 6. Rekomendasi Lanjutan

1. **Tes regresi:** Jalankan skenario API untuk Violation (list/show), Student (list/show), dan Achievement (list/show) untuk memastikan response `class` / `class_detail` / `academic_year` / `semester` konsisten.
2. **Dokumentasi relasi:** RELASI_DAN_PENYEMPURNAAN.md dan VERIFIKASI_RELASI.md sudah menggambarkan relasi inti; dokumen ini melengkapi untuk modul pelanggaran dan prestasi.

---

**Status:** Verifikasi relasi selesai; relasi invers dilengkapi; bug yang ditemukan sudah diperbaiki; perbaikan FK ClassStudentHistory dan relasi modul Grade/TeachingJournal/GuestVisit/DigitalArchive/Attendance (3 Feb 2026).
