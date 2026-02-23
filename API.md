# API Documentation – servr.in

Semua endpoint memakai prefix **`/api/v1`**. Route protected membutuhkan header: **`Authorization: Bearer <token>`**.  
Rate limit: auth 5 req/menit, protected 60 req/menit.

**Info ringkas**: `GET http://localhost:8000/api/v1`

---

## Authentication

| Method | Endpoint | Keterangan |
|--------|----------|------------|
| POST | `/api/v1/register` | Registrasi institusi baru |
| POST | `/api/v1/login` | Login |
| POST | `/api/v1/forgot-password` | Minta reset password |
| POST | `/api/v1/reset-password` | Reset password |
| POST | `/api/v1/verify-email` | Verifikasi email |
| POST | `/api/v1/resend-verification` | Kirim ulang verifikasi |
| POST | `/api/v1/refresh-token` | Refresh token |
| POST | `/api/v1/logout` | Logout (Protected) |
| GET | `/api/v1/me` | User saat ini (Protected) |

## Public (tanpa auth)

- `GET /api/v1/public/ppdb/periods` – Daftar periode PPDB yang buka (query: `npsn` atau `institution_id`)
- `GET /api/v1/public/ppdb/channels` – Daftar jalur PPDB (query: `npsn`, `institution_id`, `ppdb_period_id`)
- `GET /api/v1/public/ppdb/prefill` – Data prefill pendaftaran (query: `npsn`, `nik`, dll.; throttle 15/1 menit)
- `GET /api/v1/public/ppdb/check-result` – Cek hasil seleksi (query: `npsn`, `registration_number`, `birth_date`)
- `POST /api/v1/public/ppdb/register` – Daftar PPDB (throttle 10/1 menit)
- `GET /api/v1/public/school` – Info sekolah publik (query: `npsn` atau `slug`)
- `POST /api/v1/public/guest-visit` – Buku tamu (throttle 5/1 menit)

## Umum (Protected)

- `GET /api/v1/notifications` – List notifikasi
- `GET /api/v1/notifications/unread-count` – Jumlah belum dibaca
- `POST /api/v1/notifications/{id}/read` – Tandai dibaca
- `POST /api/v1/notifications/read-all` – Tandai semua dibaca
- `GET /api/v1/permissions` – List permission
- `GET /api/v1/permissions/teachers` – Daftar guru (untuk assign)
- `PUT /api/v1/permissions/users/{userId}` – Update permission user
- `GET /api/v1/additional-duties` – Tugas tambahan
- `GET /api/v1/audit-logs` – Audit log (filter, export)
- `GET /api/v1/teacher/dashboard` – Dashboard guru

## Institution

- `GET /api/v1/institution` – List institusi (Admin only)
- `GET /api/v1/institution/my` – Institusi sendiri
- `GET|POST|PUT|DELETE /api/v1/institution` – CRUD (detail: `/api/v1/institution/{id}`)
- `PUT /api/v1/institution/{id}/active-academic-year` – Set tahun ajaran aktif
- `POST|GET /api/v1/institution/{id}/logo` – Upload / get logo

## Student

- `GET|POST|PUT|DELETE /api/v1/student` – CRUD (list: cache 300s)
- `POST /api/v1/student/{id}/restore` – Restore
- `POST /api/v1/student/promote` – Naik kelas
- `POST /api/v1/student/import` – Import
- `POST /api/v1/student/{id}/documents` – Upload dokumen
- `DELETE /api/v1/student/{id}/documents/{documentId}` – Hapus dokumen
- `GET /api/v1/student/{id}/documents/{documentId}/download` – Download dokumen
- `GET /api/v1/student/{id}/buku-induk` – Buku induk
- `GET /api/v1/student/{id}/buku-induk/pdf` – Buku induk PDF

## Alumni & Destinasi

- `GET /api/v1/alumni`, `GET /api/v1/alumni/graduation-years`
- `POST /api/v1/student/{id}/graduate`, `POST /api/v1/student/graduate-bulk`
- `GET /api/v1/alumni/destination-types`
- `GET /api/v1/alumni/students/{studentId}/destinations`
- `POST|PUT|DELETE /api/v1/alumni-destinations` – CRUD destinasi

## Mutasi Siswa

- `GET|POST /api/v1/student-mutations`
- `GET /api/v1/student-mutations/{id}`
- `GET /api/v1/student-mutations/target-institutions`, `origin-institutions`
- `POST /api/v1/student-mutations/pull`, `POST .../approve`
- `GET /api/v1/student-mutations/report`, `export`, `by-student/{id}`, `history-by-nisn`

## Employee (Pegawai/Guru)

- `GET|POST|PUT|DELETE /api/v1/employee` – CRUD (list: cache 300s)
- `POST /api/v1/employee/{id}/restore`
- `POST /api/v1/employee/{id}/reset-password`
- `POST /api/v1/employee/import`
- `POST|DELETE|GET .../documents` – Dokumen pegawai (upload/hapus/download)
- `GET /api/v1/employee-assignments/pending`
- `POST /api/v1/employee/{id}/assignments`, `PUT /api/v1/employee-assignments/{id}`
- `POST .../approve`, `.../reject`, `.../end`

## Class

- `GET|POST|PUT|DELETE /api/v1/class` – CRUD
- `GET /api/v1/class/{id}/available-students` – Siswa yang bisa ditambah
- `GET|POST /api/v1/class/{id}/students` – List / tambah siswa
- `DELETE /api/v1/class/{id}/students/{studentId}` – Hapus dari kelas

## Tahun Ajaran & Semester

- `GET /api/v1/academic-years`, `GET /api/v1/academic-years/{id}`
- Super Admin: `GET /api/v1/academic-years/active`, `current`, `POST .../activate`, `POST|PUT|DELETE`
- `GET /api/v1/semesters/active`, `GET /api/v1/semesters/academic-year/{academicYearId}`
- `POST /api/v1/semesters/academic-year/{id}/auto-generate`, `POST /api/v1/semesters/{id}/activate`
- `GET|POST|PUT|DELETE /api/v1/semesters` – CRUD semester

## Mata Pelajaran & Jadwal

- `GET|POST|PUT|DELETE /api/v1/subjects` – CRUD mapel
- `GET|POST|PUT|DELETE /api/v1/lesson-schedules` – CRUD jadwal
- `GET /api/v1/lesson-schedules/by-class/{classId}`, `by-teacher/{employeeId}`, `by-room/{roomId}`
- `POST /api/v1/lesson-schedules/copy-semester` – Copy jadwal per semester
- `DELETE .../by-class/{classId}`, `.../by-semester/{semesterId}`

## Jurnal Mengajar & Absensi Siswa

- `GET|POST|PUT|DELETE /api/v1/teaching-journals`
- `GET /api/v1/teaching-journals/{id}/attendances` – Absensi per jurnal
- `POST /api/v1/teaching-journals/attendances`
- `PUT /api/v1/student-attendances/{id}`

## Absensi Pegawai

- `GET /api/v1/employee-attendances`, `GET .../status-options`
- `POST /api/v1/employee-attendances`, `POST .../bulk`
- `PUT /api/v1/employee-attendances/{id}`

## Buku Nilai (Grade)

- `GET|POST|PUT|DELETE /api/v1/grades`
- `GET /api/v1/grades/by-class-subject-semester`, `by-student-semester`
- `POST /api/v1/grades/bulk`
- `GET /api/v1/grades/export`, `export-student-raport`

## Pelanggaran, Prestasi & Poin

- `GET|POST|PUT|DELETE /api/v1/violations`, `GET .../by-student/{studentId}`
- `GET|POST|PUT|DELETE /api/v1/violation-types`
- `GET|POST|PUT|DELETE /api/v1/achievements`, `GET .../by-student/{studentId}`
- `GET|POST|PUT|DELETE /api/v1/achievement-types`
- `GET|POST|PUT|DELETE /api/v1/point-thresholds`
- `GET|POST /api/v1/student-action-logs`, `GET .../by-student/{studentId}`
- `GET /api/v1/student-points`, `GET .../{studentId}/summary`

## Konseling

- `GET|POST|PUT|DELETE /api/v1/counseling`
- `GET /api/v1/counseling/stats`, `upcoming`, `export`, `counselors`, `by-student/{studentId}`
- `GET|POST|PUT|DELETE /api/v1/counseling-types`

## Facility (Sarana Prasarana)

- `GET|POST|PUT|DELETE /api/v1/facility/lands`
- `GET|POST|PUT|DELETE /api/v1/facility/buildings`
- `GET|POST|PUT|DELETE /api/v1/facility/rooms`
- `GET /api/v1/facility/lab-report`, `my-labs`

## Inventory

- `GET|POST|PUT|DELETE /api/v1/inventory/categories`, `items`
- `POST /api/v1/inventory/items/{id}/restore`
- `GET|POST /api/v1/inventory/transactions`, `GET .../{id}`
- `GET|POST /api/v1/inventory/maintenances`, `GET|PUT .../{id}`
- `GET|POST /api/v1/inventory/loans`, `GET .../{id}`, `POST .../{id}/return`
- `GET /api/v1/inventory/reports/statistics`, `by-category`, `by-location`, `damaged-missing`, `loaned`, `asset-value`, `maintenance`, `transactions`

## Correspondence (Persuratan)

- `GET|POST|PUT|DELETE /api/v1/correspondence`
- `GET /api/v1/correspondence/categories`, `letter-types`, `users`
- `GET /api/v1/correspondence/statistics`
- `GET /api/v1/correspondence/export/excel`, `export/pdf`, `export/download/{filePath}`, `export/pdf/{filePath}`
- `POST /api/v1/correspondence/import`, `GET .../import/template`
- `POST /api/v1/correspondence/{id}/approve`, `send`, `archive`, `restore`, `GET .../print`
- `GET|POST|PUT|DELETE /api/v1/correspondence/{id}/attachments`
- `GET|POST|PUT|DELETE /api/v1/correspondence/{id}/dispositions`
- `GET /api/v1/dispositions/pending`

## Arsip Digital

- `GET|POST|PUT|DELETE /api/v1/digital-archives`
- `GET|POST /api/v1/digital-archives/categories`
- `GET /api/v1/digital-archives/{id}/download`

## Buku Tamu

- `GET|POST|PUT|DELETE /api/v1/guest-visits`
- `POST /api/v1/guest-visits/{id}/checkout`
- `GET /api/v1/guest-visits/export`

## Pengambilan Ijazah

- `GET|POST|PUT|DELETE /api/v1/document-pickups`

## Perpustakaan

- `GET|POST|PUT|DELETE /api/v1/library/categories`, `books`, `copies`
- `GET|POST /api/v1/library/loans`, `GET .../{id}`, `POST .../{id}/return`, `POST .../{id}/renew`
- `GET /api/v1/library/loans/{id}/calculate-fine`
- `GET|POST /api/v1/library/fine-payments`
- `GET /api/v1/library/reports/statistics`, `top-books`, `loans-by-month`, `export/loans-pdf`

## Ekstrakurikuler

- `GET|POST|PUT|DELETE /api/v1/extracurriculars`
- `GET /api/v1/extracurriculars/by-student/{studentId}`
- `GET /api/v1/extracurriculars/{id}/students`, `available-students`
- `POST /api/v1/extracurriculars/{id}/students` – Tambah peserta
- `PUT /api/v1/extracurriculars/{id}/enrollments/{enrollmentId}`
- `DELETE /api/v1/extracurriculars/{id}/students/{studentId}`
- `GET /api/v1/extracurriculars/{id}/students/export`

## Permintaan Perubahan Instansi

- `GET|POST /api/v1/institution-change-requests`
- `GET /api/v1/institution-change-requests/pending-count`
- `POST /api/v1/institution-change-requests/{id}/approve`

## QR Absensi

- `GET /api/v1/qr-attendance/student/{student}/generate` – Generate QR siswa
- `GET /api/v1/qr-attendance/employee/{employee}/generate` – Generate QR pegawai
- `POST /api/v1/qr-attendance/scan` – Scan QR untuk absensi

## Kalender Akademik

- `GET|POST|PUT|DELETE /api/v1/academic-calendar` – CRUD event
- `GET /api/v1/academic-calendar/calendar` – Data kalender
- `GET /api/v1/academic-calendar/upcoming` – Event mendatang

## Report

- `GET /api/v1/report/institution/{institutionId?}` – Statistik laporan

## PPDB (Protected, modul PPDB)

- `GET|POST|PUT|DELETE /api/v1/ppdb-periods` – CRUD periode PPDB
- `GET /api/v1/ppdb-periods/{id}/statistics` – Statistik periode
- `GET|POST|PUT|DELETE /api/v1/ppdb-channels` – CRUD jalur PPDB
- `GET /api/v1/ppdb-applicants` – Daftar pendaftar (filter)
- `GET /api/v1/ppdb-applicants/export` – Export pendaftar
- `GET|POST|PUT|DELETE /api/v1/ppdb-applicants/{id}` – CRUD pendaftar
- `POST /api/v1/ppdb-applicants/{id}/verification` – Set verifikasi
- `POST /api/v1/ppdb-applicants/{id}/submit` – Submit pendaftaran
- `POST /api/v1/ppdb-applicants/{id}/result` – Set hasil seleksi
- `POST /api/v1/ppdb-applicants/{id}/confirm-re-registration` – Konfirmasi daftar ulang
- `POST /api/v1/ppdb-applicants/{id}/convert-to-student` – Konversi ke data siswa
- `POST /api/v1/ppdb-applicants/{id}/documents` – Upload dokumen
- `DELETE /api/v1/ppdb-applicants/{id}/documents/{documentId}` – Hapus dokumen
- `GET /api/v1/ppdb-applicants/{id}/documents/{documentId}/download` – Download dokumen
