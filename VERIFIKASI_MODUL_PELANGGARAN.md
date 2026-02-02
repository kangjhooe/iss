# Verifikasi Modul Pelanggaran

Dokumen ini merangkum hasil pengecekan keseluruhan modul pelanggaran: konsistensi, relasi, dan alur data.

---

## 1. Ringkasan Logika Bisnis

- **Skor pelanggaran** = poin pelanggaran − poin prestasi. Makin besar skor = makin buruk.
- **Poin pelanggaran**: setiap jenis pelanggaran punya `point_weight` (positif); dijumlah dari semua catatan pelanggaran siswa.
- **Poin prestasi**: setiap prestasi punya `point_value` (positif); dijumlah dari semua prestasi siswa; mengurangi skor pelanggaran.
- **Tabung prestasi** = total poin prestasi yang “ditabung” (nilai sama dengan achievement_points), untuk siswa berprestasi.
- **Aturan tindakan**: jika skor pelanggaran masuk rentang `point_min`–`point_max` (nilai positif, mis. 40–999), tindakan wajib (mis. Panggilan orang tua) berlaku. Threshold dengan `sort_order` lebih kecil = lebih berat (dicek lebih dulu).

---

## 2. Backend – Model & Relasi

| Model | Tabel | Relasi |
|-------|--------|--------|
| **Violation** | `violations` | `belongsTo` Institution, Student, ViolationType, User (reporter), AcademicYear, Semester, SchoolClass |
| **ViolationType** | `violation_types` | `belongsTo` Institution; `hasMany` Violation |
| **Achievement** | `achievements` | `belongsTo` Institution, Student, AchievementType, User (giver) |
| **AchievementType** | `achievement_types` | `belongsTo` Institution; `hasMany` Achievement |
| **PointThreshold** | `point_thresholds` | `belongsTo` Institution; `containsPoint(totalPoint)` |
| **StudentActionLog** | `student_action_logs` | `belongsTo` Institution, Student, PointThreshold, User (recorder) |
| **Student** | `student` | `hasMany` Violation, Achievement, StudentActionLog |

- Semua model modul pelanggaran memakai `institution_id`; data terisolasi per institusi.
- Student punya relasi lengkap: `violations()`, `achievements()`, `actionLogs()`.

---

## 3. Backend – Service & Logika

- **ViolationService**
  - `listForInstitution`: filter by student_id, violation_type_id, status, date_from, date_to, search (nama/NIS/NISN).
  - `create`: set `reported_by` = user yang login; isi `academic_year_id`, `semester_id`, `class_id` dari data siswa.
  - Eager load: student (id,name,nis,nisn,class), violationType, reporter.

- **StudentPointService**
  - `getViolationPoints`: sum `violation_types.point_weight` untuk semua pelanggaran siswa di institusi.
  - `getAchievementPoints`: sum `point_value` dari achievements siswa.
  - `getTotalPoint` = violation_points − achievement_points.
  - `getPointSummary`: mengembalikan violation_points, achievement_points, total_points, achievement_bank.
  - `getRequiredAction(institutionId, totalPoint)`: ambil threshold aktif pertama (urut sort_order, point_min) yang memenuhi `point_min <= totalPoint <= point_max`.

---

## 4. Backend – API Routes (module:violation)

| Method | Endpoint | Keterangan |
|--------|----------|------------|
| GET | /violations | Daftar pelanggaran (filter, pagination) |
| POST | /violations | Tambah pelanggaran |
| GET | /violations/by-student/{studentId} | Pelanggaran per siswa |
| GET/PUT/DELETE | /violations/{id} | Detail / update / hapus |
| GET/POST | /violation-types | Daftar / tambah jenis pelanggaran |
| GET/PUT/DELETE | /violation-types/{id} | Detail / update / hapus jenis |
| GET/POST | /achievements | Daftar / tambah prestasi |
| GET/DELETE | /achievements/{id} | Detail / hapus prestasi |
| GET/POST | /achievement-types | Daftar / tambah jenis prestasi |
| GET/PUT/DELETE | /achievement-types/{id} | Detail / update / hapus jenis |
| GET/POST | /point-thresholds | Daftar / tambah aturan tindakan |
| GET/PUT/DELETE | /point-thresholds/{id} | Detail / update / hapus aturan |
| GET/POST | /student-action-logs | Daftar / catat tindakan |
| GET | /student-action-logs/by-student/{studentId} | Log tindakan per siswa |
| GET | /student-points | Daftar siswa + ringkasan poin + required_action |
| GET | /student-points/{studentId}/summary | Ringkasan poin satu siswa |

---

## 5. Frontend – Konsistensi

- **Router**: `/violation` → `Violation.vue`, meta `requiresModule: 'violation'`.
- **Layout**: Menu "Pelanggaran" tampil jika `canAccessModule('violation')`.
- **API** (`violation.js`): violationApi, violationTypeApi, achievementApi, achievementTypeApi, pointThresholdApi, studentActionLogApi, studentPointApi; path mengikuti backend (`/v1/...`).
- **ConfirmDialog**: Semua pakai prop `confirmText` (bukan `confirmLabel`); pesan dinamis lewat computed (mis. `deleteTypeMessage`) untuk menghindari string template yang putus/escape error.

---

## 6. Validasi & Keamanan

- **StoreViolationRequest**: student_id, violation_type_id, violation_date wajib; sanction, description opsional.
- **UpdateViolationRequest**: violation_type_id, violation_date, status opsional (sometimes); status enum: dicatat, sanksi_diberikan, follow_up, selesai.
- ViolationService memastikan siswa dan violation_type milik institusi yang sama sebelum create.
- Reporter otomatis dari user yang login.

---

## 7. Kesimpulan

- **Relasi**: Semua relasi model (Violation ↔ Student, ViolationType, User; Achievement ↔ Student, AchievementType, User; PointThreshold, StudentActionLog) konsisten dengan migration dan penggunaan di service/controller.
- **Konsistensi**: Naming (violation_types, violations, achievements, point_thresholds, student_action_logs), fillable, dan resource API selaras dengan frontend (Violation.vue, violation.js).
- **Logika poin**: total_points = violation − achievement; threshold dengan rentang positif (mis. 40–999) untuk “skor buruk”; tabung prestasi = achievement_bank; required_action dari PointThreshold yang containsPoint(total_points).

Modul siap dipakai; tidak ada inkonsistensi atau relasi yang salah yang ditemukan dalam pengecekan ini.
