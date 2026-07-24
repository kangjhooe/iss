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
| PUT | `/api/v1/me` | Update profil (nama, email) – Protected; body: `name`, `email` |
| PUT | `/api/v1/me/password` | Ubah sandi – Protected; body: `current_password`, `password`, `password_confirmation` |

## Public (tanpa auth)

- `GET /api/v1/public/ppdb/periods` – Daftar periode PPDB yang buka (query: `npsn` atau `institution_id`)
- `GET /api/v1/public/ppdb/channels` – Daftar jalur PPDB (query: `npsn`, `institution_id`, `ppdb_period_id`)
- `GET /api/v1/public/ppdb/prefill` – Data prefill pendaftaran (query: `npsn`, `nik`, dll.; throttle 15/1 menit)
- `GET /api/v1/public/ppdb/check-result` – Cek hasil seleksi (query: `npsn`, `registration_number`, `birth_date`)
- `POST /api/v1/public/ppdb/register` – Daftar PPDB (throttle 10/1 menit)
- `GET /api/v1/public/school` – Info sekolah publik (query: `npsn` atau `slug`)
- `GET /api/v1/public/npsn-lookup` – Lookup NPSN referensi Kemendikbud (throttle 30/1 menit)
- `POST /api/v1/public/guest-visit` – Buku tamu (throttle 5/1 menit)
- `GET /api/v1/public/library/ebooks` – Katalog ebook publik (query: `npsn`)
- `GET /api/v1/public/library/ebooks/categories` – Kategori ebook publik
- `GET /api/v1/public/library/books/{book}/viewer` – Issue viewer token
- `GET /api/v1/public/library/books/{book}/ebook` – Stream PDF ebook publik
- `GET /api/v1/public/stats` – Statistik landing
- `GET /api/v1/public/institutions/recent` – Institusi terbaru
- `GET /api/v1/public/releases` – Catatan rilis publik
- `GET /api/v1/app-branding` – Logo & favicon aplikasi

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

## Feedback Tickets

Admin sekolah lapor bug / request fitur; Super Admin update status.

- `GET|POST /api/v1/feedback-tickets` – List / buat ticket
- `GET|PUT /api/v1/feedback-tickets/{id}` – Detail / update (update: Super Admin)
- `GET /api/v1/feedback-tickets/open-count` – Jumlah terbuka (Super Admin)

## Permintaan Ubah Data (Siswa / Guru)

- `GET /api/v1/student-change-requests/allowed-fields`, `pending-count`
- `GET|POST /api/v1/student-change-requests`, `GET .../{id}`, `POST .../{id}/approve`
- `GET /api/v1/teacher-change-requests/allowed-fields`, `pending-count`
- `GET|POST /api/v1/teacher-change-requests`, `GET .../{id}`, `POST .../{id}/approve`

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

## Mutasi Guru (modul `teacher`)

Mutasi antar institusi berdasarkan NIK (tidak ada batasan jenjang). Push dari sekolah asal atau pull dari sekolah tujuan.

- `GET|POST /api/v1/teacher-mutations`
- `GET /api/v1/teacher-mutations/{id}`
- `GET /api/v1/teacher-mutations/target-institutions`, `origin-institutions`
- `GET /api/v1/teacher-mutations/lookup-teacher`, `lookup-teacher-at-origin`
- `POST /api/v1/teacher-mutations/pull`
- `POST /api/v1/teacher-mutations/{id}/approve`, `cancel`, `cancel-decision`
- `GET /api/v1/teacher-mutations/report`, `export`
- `GET /api/v1/teacher-mutations/by-employee/{employee_id}`, `history-by-nik` (identitas guru: NIK, bukan NUPTK)

## Employee (Pegawai/Guru)

- `GET|POST|PUT|DELETE /api/v1/employee` – CRUD (list: cache 300s)
- `GET /api/v1/employee/search` – Cari by NIK
- `GET /api/v1/employee/export` – Export pegawai (baris lengkap untuk Excel)
- `GET /api/v1/employee/export/pdf` – Cetak daftar pegawai PDF
- `POST /api/v1/employee/{id}/restore`
- `POST /api/v1/employee/{id}/reset-password`
- `POST /api/v1/employee/import`
- `POST|DELETE|GET .../documents` – Dokumen pegawai (upload/hapus/download)
- `GET /api/v1/employee-assignments/pending`
- `POST /api/v1/employee/{id}/assignments`, `PUT /api/v1/employee-assignments/{id}`
- `POST .../approve`, `.../reject`, `.../end`

## Class

- `GET|POST|PUT|DELETE /api/v1/class` – CRUD
- `GET /api/v1/class/export/pdf`
- `POST /api/v1/class/clone-to-year` – Clone kelas ke tahun ajaran lain
- `GET /api/v1/class/{id}/available-students` – Siswa yang bisa ditambah
- `GET|POST /api/v1/class/{id}/students` – List / tambah siswa
- `DELETE /api/v1/class/{id}/students/{studentId}` – Hapus dari kelas

## Tahun Ajaran & Semester

- `GET /api/v1/academic-years`, `GET /api/v1/academic-years/{id}`
- Super Admin: `GET /api/v1/academic-years/active`, `current`, `POST .../activate`, `POST|PUT|DELETE`
- `GET /api/v1/semesters/active`, `GET /api/v1/semesters/academic-year/{academicYearId}`
- `POST /api/v1/semesters/academic-year/{id}/auto-generate`, `POST /api/v1/semesters/{id}/activate`
- `GET|POST|PUT|DELETE /api/v1/semesters` – CRUD semester

## Mata Pelajaran & Jadwal (modul `schedule`)

- `GET|POST|PUT|DELETE /api/v1/subjects` – CRUD mapel
- `GET|POST|PUT|DELETE /api/v1/lesson-schedules` – CRUD jadwal
- `GET /api/v1/lesson-schedules/by-class/{classId}`, `by-teacher/{employeeId}`, `by-room/{roomId}`
- `GET /api/v1/lesson-schedules/export-pdf`, `my-teaching-load`
- `POST /api/v1/lesson-schedules/copy-semester` – Copy jadwal per semester
- `DELETE .../by-class/{classId}`, `.../by-semester/{semesterId}`

### Template Jadwal (multi)

Beberapa template per institusi+semester (mis. 8 JP / 9 JP), flag default, assign ke kelas.

- `GET|POST /api/v1/lesson-schedule-templates`
- `GET /api/v1/lesson-schedule-templates/default`
- `PUT|DELETE /api/v1/lesson-schedule-templates/{id}`
- `PUT /api/v1/lesson-schedule-templates` – Upsert
- `PUT /api/v1/lesson-schedules/by-class/{classId}/template` – Assign template ke kelas
- `GET /api/v1/lesson-schedules/by-class/{classId}/template/preview`

## Dashboard Wali Kelas

Scoped by homeroom ownership (tidak butuh grant modul ekstra). Prefix: `/api/v1/teacher/wali`

- `GET .../classes/{classId}/dashboard` – Ringkasan hub
- `GET .../classes/{classId}/attendance-summary` – Rekap absensi 7/30 hari
- `GET .../classes/{classId}/grades-overview` – Monitoring nilai/KKM
- `GET .../classes/{classId}/schedule`, `.../schedule/export-pdf`
- `GET .../classes/{classId}/students/{studentId}` – Profil siswa 360°
- `GET|POST|PUT|DELETE .../classes/{classId}/students/{studentId}/notes`
- `GET|POST .../violations`, `GET .../violation-types`
- `GET|POST .../achievements`, `GET .../achievement-types`
- `GET|POST .../mutations`
- `GET .../classes/{classId}/export/roster|contacts|attendance`

## Jurnal Mengajar & Absensi Siswa

- `GET|POST|PUT|DELETE /api/v1/teaching-journals`
- `GET /api/v1/teaching-journals/{id}/attendances` – Absensi per jurnal
- `POST /api/v1/teaching-journals/attendances`
- `PUT /api/v1/student-attendances/{id}`

## Absensi Pegawai

- `GET /api/v1/employee-attendances`, `GET .../status-options`
- `POST /api/v1/employee-attendances`, `POST .../bulk`
- `PUT /api/v1/employee-attendances/{id}`

## Buku Nilai (Grade) — modul `grade_book`

- `GET|POST|PUT|DELETE /api/v1/grades`
- `GET /api/v1/grades/by-class-subject-semester`, `by-student-semester`
- `GET /api/v1/grades/completeness`
- `POST /api/v1/grades/bulk`
- `POST /api/v1/grades/kkm` – Upsert KKM per mapel + tingkat + semester
- `POST /api/v1/grades/weights` – Upsert bobot (penilaian/UTS/UAS, total 100%)
- `GET /api/v1/grades/remedials/below-kkm` – Siswa di bawah KKM
- `GET|POST /api/v1/grades/remedials`
- `POST /api/v1/grades/remedials/{id}/complete`, `cancel`
- `GET /api/v1/grades/export`, `export-pdf`, `export-student-raport`
- `GET /api/v1/grades/export-class-raport`, `export-class-raport-pdf`

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
- `GET /api/v1/facility/export/pdf` – Cetak laporan sarana prasarana (DomPDF + kop + TTD)
- `GET /api/v1/facility/lab-report`, `my-labs`
- `GET /api/v1/facility/lab-report/export`, `labs/{id}/export` – Cetak laporan lab PDF
- Lab booking (modul facility): lihat bagian **Lab Booking**

## Inventory

- `GET|POST|PUT|DELETE /api/v1/inventory/categories`, `items`
- `POST /api/v1/inventory/items/{id}/restore`
- `GET|POST /api/v1/inventory/transactions`, `GET .../{id}`
- `GET|POST /api/v1/inventory/maintenances`, `GET|PUT .../{id}`
- `GET|POST /api/v1/inventory/loans`, `GET .../{id}`, `POST .../{id}/return`
- `GET /api/v1/inventory/reports/statistics`, `by-category`, `by-location`, `damaged-missing`, `loaned`, `asset-value`, `maintenance`, `transactions`
- `GET /api/v1/inventory/reports/export/pdf` – Cetak laporan inventaris (DomPDF + kop + TTD)

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

### Ebook (baca — siswa & staf, tanpa modul library)

- `GET /api/v1/library/ebooks`, `ebooks/categories`
- `GET /api/v1/library/books/{book}/ebook` – Stream PDF

### Kelola (modul `library`)

- `GET|POST|PUT|DELETE /api/v1/library/categories`, `books`, `copies`
- `GET /api/v1/library/books/import/template`, `POST .../import`
- `GET /api/v1/library/books/export/csv`, `export/pdf`
- `GET|POST /api/v1/library/loans`, `GET .../{id}`, `POST .../{id}/return`, `POST .../{id}/renew`
- `GET /api/v1/library/loans/{id}/calculate-fine`
- `GET|POST /api/v1/library/fine-payments`
- `GET /api/v1/library/reports/statistics`, `top-books`, `top-ebooks`, `loans-by-month`, `ebook-views-by-month`
- `GET /api/v1/library/reports/export/loans-pdf`, `export/fines-pdf`

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

## Keuangan (modul `finance`, Beta)

- `GET /api/v1/finance/summary` – Ringkasan tagihan / penerimaan / tunggakan
- `GET|POST|PUT|DELETE /api/v1/finance/fee-types` – Jenis biaya
- `GET /api/v1/finance/invoices` – Daftar tagihan (filter status, kelas, frekuensi)
- `GET /api/v1/finance/invoices/export` – Export CSV tagihan
- `POST /api/v1/finance/invoices/generate` – Generate tagihan (target: `student_ids` / `class_id` / `all_students`; `period_label` wajib untuk monthly/yearly)
- `PUT /api/v1/finance/invoices/{id}` – Update tagihan (judul, nominal, jatuh tempo; atau batalkan)
- `DELETE /api/v1/finance/invoices/{id}` – Hapus / batalkan jika sudah ada pembayaran
- `GET|POST|DELETE /api/v1/finance/payments` – Pembayaran
- `GET /api/v1/finance/payments/export` – Export CSV pembayaran
- `GET /api/v1/finance/payments/{id}/receipt` – Kwitansi PDF (DomPDF + kop + TTD, selaras laporan lain)

## Report

- `GET /api/v1/report/institution/{institutionId?}` – Statistik laporan

## PPDB (Protected, modul PPDB)

- `GET|POST|PUT|DELETE /api/v1/ppdb-periods` – CRUD periode PPDB
- `GET /api/v1/ppdb-periods/{id}/statistics` – Statistik periode
- `GET|POST|PUT|DELETE /api/v1/ppdb-channels` – CRUD jalur PPDB
- `GET /api/v1/ppdb/summary` – Ringkasan dashboard PPDB
- `GET /api/v1/ppdb-applicants` – Daftar pendaftar (filter status, payment_status, dll.)
- `GET /api/v1/ppdb-applicants/export` – Export pendaftar (CSV/XLSX; kolom termasuk payment_status)
- `GET|POST|PUT|DELETE /api/v1/ppdb-applicants/{id}` – CRUD pendaftar
- `POST /api/v1/ppdb-applicants/{id}/verification` – Set verifikasi
- `POST /api/v1/ppdb-applicants/{id}/submit` – Submit pendaftaran
- `POST /api/v1/ppdb-applicants/{id}/result` – Set hasil seleksi (email ke calon jika ada)
- `POST /api/v1/ppdb-applicants/{id}/payment` – Set status pembayaran
- `POST /api/v1/ppdb-applicants/{id}/confirm-re-registration` – Konfirmasi daftar ulang (admin)
- `POST /api/v1/ppdb-applicants/{id}/convert-to-student` – Konversi ke data siswa
- `POST /api/v1/ppdb-applicants/{id}/documents` – Upload dokumen
- `POST /api/v1/ppdb-applicants/bulk-verification` – Bulk verifikasi
- `POST /api/v1/ppdb-applicants/bulk-result` – Bulk hasil seleksi
- `DELETE /api/v1/ppdb-applicants/{id}/documents/{documentId}` – Hapus dokumen
- `GET /api/v1/ppdb-applicants/{id}/documents/{documentId}/download` – Download dokumen

Notifikasi: admin (in-app) saat daftar baru & daftar ulang; calon (email) saat daftar sukses & hasil diumumkan.

## Guru Piket (modul `guru_piket` / `guru_piket_manage`)

- `GET /api/v1/piket/dashboard`, `today`, `settings`, `PUT .../settings`
- `GET /api/v1/piket/employees-lite`, `students-lite`, `classes-lite`
- `GET|POST /api/v1/piket-schedules`, `PUT|DELETE .../{id}`
- `GET|POST /api/v1/piket-logs`, `PUT .../{id}`, `POST .../{id}/review`, `DELETE .../{id}`
- `GET|POST /api/v1/piket-incidents`, `PUT|DELETE .../{id}`
- `POST .../piket-incidents/{id}/propose-violation`, `propose-teacher-violation`
- `POST /api/v1/piket/scan`, `scan/empty-classes`, `scan/teacher-lateness`
- `GET /api/v1/piket/report`, `weekly-report`, `report-pdf`

## Apresiasi & Poin Guru (modul `teacher_appreciation`)

- `GET|POST|PUT|DELETE /api/v1/teacher-point-rewards`
- `GET /api/v1/teacher-points`, `.../leaderboard`, `.../report-summary`, `.../{employeeId}/summary`
- `GET /api/v1/teacher-appreciation/employees`, `employees-lite`, `pending-counts`, `bootstrap`
- Portal guru sendiri: `GET /api/v1/teacher/my-points/summary`, `leaderboard`; `GET|POST /api/v1/teacher/my-achievements`; `GET /api/v1/teacher/my-violations`

## Lab Booking

- Facility (modul `facility`): `GET|POST /api/v1/facility/lab-bookings`, `POST .../{id}/approve|reject|cancel`
- Guru (tanpa modul facility): prefix `/api/v1/lab-booking` – request & status sendiri

## Ujian Online (modul `online_exam`, Beta)

- Prefix `/api/v1/exam` – bank soal, ujian, sesi, peserta, kartu login, kendali mulai/akhir/reset, koreksi, rilis nilai
- `GET /api/v1/exam/sessions/{id}/monitor` – snapshot monitoring (summary + peserta)
- `GET /api/v1/exam/sessions/{id}/export-results` – export Excel hasil sesi
- `POST /api/v1/exam/sessions/{id}/release-scores` – rilis semua nilai yang siap
- `POST /api/v1/exam/participants/{id}/reset` – reset satu peserta
- Bank soal: filter `search` / `type` / `stimulus_id` (`none` = tanpa stimulus); `POST /api/v1/question-bank/{id}/duplicate`
- `POST /api/v1/question-bank/reorder`, `POST /api/v1/question-bank/import`, `GET /api/v1/question-bank/import-template`
- Soal isian: `key_answer` + `key_answer_aliases` (banyak jawaban benar)
- Attempt publik (token peserta) di luar auth group — lihat route public exam di `v1.php`

## Super Admin

- `GET /api/v1/super-admin/dashboard`
- `GET|POST /api/v1/super-admin/institution-admins`, `POST .../{id}/reset-password`, `.../status`, `.../impersonate`
- `POST /api/v1/super-admin/onboard`
- `POST /api/v1/super-admin/impersonate/stop`
- `GET /api/v1/super-admin/adoption`
- `GET|POST /api/v1/super-admin/broadcasts`, `GET .../{id}`
- `GET|POST|PUT|DELETE /api/v1/super-admin/releases`
- `GET /api/v1/super-admin/reports/aggregate`, `.../export`
- `PATCH|POST /api/v1/institution/{id}/status` – Aktif/suspend institusi
