# Master Tugas Tambahan (Additional Duties)

Tabel `additional_duties` dan mapping ke modul (permissions). Satu guru bisa punya lebih dari satu tugas tambahan; setiap tugas memberi akses ke modul yang terdaftar.

## Daftar final: key, label, permission_keys

| key | label | permission_keys (modul akses) |
|-----|--------|------------------------------|
| kepala_sekolah | Kepala Sekolah | institution, correspondence, report, teacher_appreciation, guru_piket, bk_report, kepegawaian |
| waka_kurikulum | Wakil Kepala Sekolah Kurikulum | schedule, class, teaching_journal, grade_book, report |
| waka_kesiswaan | Wakil Kepala Sekolah Kesiswaan | student, violation, counseling, uks, class, report |
| waka_sarpras | Wakil Kepala Sekolah Sarana Prasarana | facility, inventory, report |
| waka_humas | Wakil Kepala Sekolah Humas | correspondence, institution, report |
| kepala_tata_usaha | Kepala Tata Usaha | correspondence, report, institution, kepegawaian |
| bendahara | Bendahara | report, finance |
| ketua_perpus | Ketua/Kepala Perpustakaan | digital_archive, report, library |
| kepala_lab | Kepala Lab | facility, inventory, report |
| koordinator_bk | Koordinator BK | counseling, violation |
| koordinator_uks | Koordinator UKS | uks |
| koordinator_osis | Koordinator OSIS | violation |
| koordinator_pramuka | Koordinator Pramuka | *(belum ada — label tugas)* |
| koordinator_literasi | Koordinator Literasi | digital_archive, report |
| operator_sekolah | Operator Sekolah | report, institution, student, class, teacher, kepegawaian |
| koordinator_ekstrakurikuler | Koordinator Ekstrakurikuler | extracurricular |
| pembina_ekstrakurikuler | Pembina Ekstrakurikuler | extracurricular |
| guru_piket | Guru Piket | guru_piket, teacher_violation_report |
| kepala_program_keahlian | Kepala Program Keahlian | schedule, class, teaching_journal, grade_book, pkl |

**Scope Kaprog (SMK/MAK):** duty ini di-scope ke jurusan lewat pivot `employee_program_keahlian`. Kelas harus punya `program_keahlian_id`. Kaprog hanya melihat kelas (modul `class`) dan penempatan PKL siswa di jurusan yang diampu. Admin/institution_admin tidak di-scope. Duty SMK/MAK (Kaprog, Kepala Bengkel, Hubin, PKL, BKK) **tidak ditampilkan** dan tidak bisa di-assign di SMA/MA/jenjang lain.
| kepala_bengkel | Kepala Bengkel | facility, inventory, report |
| koordinator_hubin | Koordinator Hubin | correspondence, institution, pkl, bkk |
| koordinator_pkl | Koordinator PKL | pkl |
| koordinator_bkk | Koordinator BKK | bkk |
| koordinator_ppdb | Koordinator PPDB | ppdb |

Catatan tambahan permission (bukan duty terpisah):
- Modul `correspondence` **bukan** paket default guru (`TeacherAccess`). Hanya admin / duty di atas yang memetakannya. Migrasi cabut dari guru tanpa duty: `2026_07_26_150000_revoke_correspondence_from_teachers_without_duty.php`. Penerima disposisi memakai inbox `/dispositions/pending` tanpa modul penuh.
- `guru_piket_manage` — kelola jadwal/pengaturan/review log; dipetakan ke duty `kepala_sekolah`, `waka_kesiswaan`, `operator_sekolah` (bersama `guru_piket`).
- `koordinator_bk` — hanya modul BK (`counseling`, `violation`). Mapping: `2026_07_25_100000_narrow_koordinator_bk_permissions.php`.
- Koordinator UKS / Pramuka / OSIS / Ekskul + Pembina Ekskul — `student`/`report` dicabut di `2026_07_25_110000_narrow_coordinator_duty_permissions.php`.
- Modul `uks` + mapping `koordinator_uks` / `waka_kesiswaan`: `2026_07_26_140001_add_uks_permission_and_map_duties.php`.
- `kepala_sekolah` dipersempit di `2026_07_18_083500_narrow_kepala_sekolah_permissions.php`.
- `library` untuk `ketua_perpus`: `2026_07_23_160000_grant_library_to_ketua_perpus.php`.
- `finance` untuk `bendahara`: `2026_07_24_140001_grant_finance_to_bendahara.php`.
- `extracurricular` untuk duty ekskul: `2026_07_15_080000_grant_extracurricular_to_pembina_duties.php`.
- Duty SMK (Kaprog, Kepala Bengkel, Hubin, PKL, BKK): `2026_07_25_120000_add_smk_additional_duties.php`. Hanya muncul di form guru institusi `SMK`/`MAK`.
- Modul `pkl` / `bkk` (Beta) + mapping duty: `2026_07_25_130001_add_pkl_bkk_permissions_and_map_duties.php`. Kaprog juga dapat `pkl`; Hubin dapat `pkl`+`bkk`. API dan menu hanya untuk SMK/MAK (`EnsureVocationalInstitution`).
- `koordinator_ppdb` → `ppdb`: `2026_07_26_100000_add_koordinator_ppdb_additional_duty.php`.
- Modul `kepegawaian` (cuti, SK, jabatan struktural, riwayat) + mapping `kepala_tata_usaha` / `operator_sekolah` / `kepala_sekolah`: `2026_07_26_180001_add_kepegawaian_permission_and_map_duties.php`.

## Tabel database

- **additional_duties**: id, key, label, description, sort_order
- **additional_duty_permissions**: additional_duty_id, permission_id (pivot)
- **employee_additional_duties**: employee_id, additional_duty_id, started_at, ended_at (pivot)

## API

- `GET /api/v1/additional-duties` — list semua tugas tambahan beserta `permission_keys` (untuk form guru).

## Catatan

- Permission keys harus ada di tabel `permissions`. Mapping awal di migration `2026_02_03_130001_create_additional_duty_permissions_table.php`; penyempitan lewat migrasi lanjutan.
- Untuk menambah tugas baru atau mengubah mapping: buat migration baru yang insert/update `additional_duties` dan `additional_duty_permissions`, atau kelola lewat seeder/admin jika nanti ada fitur master data.
- Modul `student` = CRUD kesiswaan penuh (mutasi, naik kelas, luluskan). Jangan dipetakan ke koordinator domain kecuali Waka Kesiswaan / Operator.
