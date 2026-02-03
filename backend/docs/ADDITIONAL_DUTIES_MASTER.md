# Master Tugas Tambahan (Additional Duties)

Tabel `additional_duties` dan mapping ke modul (permissions). Satu guru bisa punya lebih dari satu tugas tambahan; setiap tugas memberi akses ke modul yang terdaftar.

## Daftar final: key, label, permission_keys

| key | label | permission_keys (modul akses) |
|-----|--------|------------------------------|
| kepala_sekolah | Kepala Sekolah | institution, student, teacher, facility, inventory, class, correspondence, report, violation, schedule, counseling, teaching_journal, grade_book, digital_archive, attendance, guest_book |
| waka_kurikulum | Wakil Kepala Sekolah Kurikulum | schedule, class, teaching_journal, grade_book, report |
| waka_kesiswaan | Wakil Kepala Sekolah Kesiswaan | student, violation, counseling, class, report |
| waka_sarpras | Wakil Kepala Sekolah Sarana Prasarana | facility, inventory, report |
| waka_humas | Wakil Kepala Sekolah Humas | correspondence, institution, report |
| kepala_tata_usaha | Kepala Tata Usaha | correspondence, report, institution |
| bendahara | Bendahara | report |
| ketua_perpus | Ketua/Kepala Perpustakaan | digital_archive, report |
| kepala_lab | Kepala Lab | facility, inventory, report |
| koordinator_bk | Koordinator BK | counseling, student, report |
| koordinator_uks | Koordinator UKS | student, report |
| koordinator_osis | Koordinator OSIS | student, violation, report |
| koordinator_pramuka | Koordinator Pramuka | student, report |
| koordinator_literasi | Koordinator Literasi | digital_archive, report |
| operator_sekolah | Operator Sekolah | report, institution, student, class, teacher |
| koordinator_ekstrakurikuler | Koordinator Ekstrakurikuler | student, report |
| pembina_ekstrakurikuler | Pembina Ekstrakurikuler | student, report |

## Tabel database

- **additional_duties**: id, key, label, description, sort_order
- **additional_duty_permissions**: additional_duty_id, permission_id (pivot)
- **employee_additional_duties**: employee_id, additional_duty_id, started_at, ended_at (pivot)

## API

- `GET /api/v1/additional-duties` — list semua tugas tambahan beserta `permission_keys` (untuk form guru).

## Catatan

- Permission keys harus ada di tabel `permissions`. Mapping disimpan di migration `2026_02_03_130001_create_additional_duty_permissions_table.php`.
- Untuk menambah tugas baru atau mengubah mapping: buat migration baru yang insert/update `additional_duties` dan `additional_duty_permissions`, atau kelola lewat seeder/admin jika nanti ada fitur master data.
