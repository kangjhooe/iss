# Modul Bank Soal

> Status: dokumentasi kondisi sekarang + rencana pengembangan  
> Diperbarui: 2026-10-16  
> Modul induk: **Ujian Online** (`online_exam`) — Bank Soal tampil di menu jika modul/entitlement aktif

---

## 1. Ringkasan

Bank Soal adalah **gudang soal jangka panjang per institusi**, dikelompokkan per **mata pelajaran** (dan opsional label tingkat/kelas). Soal di bank dipakai untuk menyusun **paket ujian** (snapshot): mengubah soal di bank **tidak** mengubah ujian yang sudah dipasang.

Alur utama:

```
Buat bank (mapel + kode)
  → isi stimulus (opsional)
  → isi soal (PG / PG kompleks / matching / isian / uraian)
  → (opsional) share / backup / import
  → buat ujian (mapel wajib) → pilih blok/set ke paket → snapshot
  → acak unit di sesi (set stimulus tetap berurutan)
```

---

## 2. Kondisi sekarang

### 2.1 Konsep yang berlaku

| Aturan | Perilaku |
|--------|----------|
| Multi-tenant | Semua data terikat `institution_id` |
| Satu bank | Satu `subject_id` (mapel) |
| Tingkat (`grade`) | Opsional; **label rak saja**, bukan batasan pemakaian di ujian |
| Soft delete | Ada pada `bank_soal` dan `question_bank` |
| Snapshot ujian | `exam_questions.snapshot` membekukan isi soal saat dipasang |
| Skor ujian | Bobot relatif di bank → dihitung mentah → **dinormalisasi ke 0–100** |
| Level kognitif | Opsional `cognitive_level` C1–C6 (taksonomi Bloom) di soal |
| Hapus bank/soal | Ditolak jika soal masih terpasang di paket ujian |
| Akses modul | Via `canAccessModule('online_exam')` + entitlement online exam |

### 2.2 Entitas & relasi

```
Institution
  └── BankSoal (code unik per institusi, subject, grade?, keterangan, created_by)
        ├── QuestionStimulus (title, content, type, attachment?)
        ├── QuestionBank (body, type, weight, cognitive_level?, sort_order, stimulus?, kunci…)
        │     ├── QuestionOption (untuk pg / pg_kompleks)
        │     └── ExamQuestion (paket ujian + snapshot)
        └── BankSoalShare (user kolaborator)
```

| Entitas | Tabel / model | Field penting |
|---------|----------------|---------------|
| Bank | `bank_soal` / `BankSoal` | `code`, `name`, `subject_id`, `grade`, `keterangan`, `created_by_user_id` |
| Soal | `question_bank` / `QuestionBank` | `bank_soal_id`, `subject_id`, `stimulus_id`, `type`, `body`, `weight`, `cognitive_level`, `sort_order`, `key_answer`, `key_answer_aliases`, `matching_data` |
| Opsi | `question_options` / `QuestionOption` | `option_key`, `body`, `is_correct`, `option_weight`, `sort_order` |
| Stimulus | `question_stimuli` / `QuestionStimulus` | `bank_soal_id`, `title`, `content`, `type`, `attachment_path`, `media` (JSON: image/audio/video/youtube) |
| Share | `bank_soal_shares` / `BankSoalShare` | `bank_soal_id`, `user_id` |
| Paket ujian | `exam_questions` / `ExamQuestion` | `exam_id`, `question_bank_id`, `sort_order`, `snapshot` |

Trait: `Auditable` + `SoftDeletes` pada bank & soal.

### 2.3 Tipe soal

| Kode | Label UI | Kunci / skor |
|------|----------|--------------|
| `pg` | Pilihan ganda | Satu opsi benar (`is_correct`); bobot di soal |
| `pg_kompleks` | PG kompleks | Multi benar; bobot soal = jumlah `option_weight` opsi benar |
| `matching` | Mencocokkan | `matching_data` (JSON pasangan) |
| `isian` | Isian | `key_answer` + `key_answer_aliases` (normalisasi lowercase/trim) |
| `uraian` | Uraian | Koreksi manual di sesi ujian |

Body soal/opsi: HTML disanitasi (`HtmlPurifier::sanitizeQuestion`). Editor frontend: `RichTextEditor`.

### 2.3a Model skor (keputusan dikunci 2026-10-16)

| Lapisan | Apa yang disimpan | Peran |
|---------|-------------------|--------|
| **Bank soal** | `weight` (relatif) + `cognitive_level` (C1–C6, opsional) | Bobot & metadata kualitas; **bukan** nilai akhir 100 |
| **Paket ujian** | Snapshot menyalin `weight` + `cognitive_level` | Edit bank setelah attach tidak mengubah skor paket |
| **Penilaian** | Skor per jawaban memakai bobot snapshot | PG/isian/matching otomatis; uraian manual |
| **Nilai peserta** | `score` / `score_max` dengan `score_max = 100` | `score = round((raw / raw_max) × 100, 2)` |

**Level kognitif (Bloom):**

| Kode | Label |
|------|--------|
| `c1` | Mengingat |
| `c2` | Memahami |
| `c3` | Menerapkan |
| `c4` | Menganalisis |
| `c5` | Mengevaluasi |
| `c6` | Mencipta |

**Belum (fase berikutnya):** override bobot per soal di paket ujian (default dari bank, bisa diubah tanpa menyentuh bank).

**Migrasi data lama:** hitung ulang nilai sesi (“Hitung nilai otomatis”) agar `score_max` menjadi 100. Salin ke buku nilai & export Excel tetap aman (rumus `score/score_max×100` kompatibel dengan data lama maupun baru).

### 2.4 Akses & share

**Akses bank** (`canAccessBank`):

- Super admin / admin
- User di institusi pemilik bank
- Owner (`created_by_user_id`)
- User yang di-share
- Institution admin yang boleh akses institusi pemilik bank

**Kelola share** (`canManageShare`): owner, institution admin (institusi bank), atau super/admin.

**Invite share:** via **NIK guru** → resolve ke `User`. Ditolak jika guru belum punya akun. Share saat ini **tanpa level permission** (akses penuh sebagai kolaborator).

**Daftar bank (`index`):** bank institusi aktif **atau** bank yang di-share ke user.

### 2.5 Fitur yang sudah jalan

#### Bank soal
- CRUD bank (kode unik per institusi, mapel wajib, grade opsional 1–12 sesuai jenjang)
- Filter: search kode/nama, mapel, tingkat
- Count soal total + per tipe
- Hapus ditolak jika ada soal terpasang di ujian
- Backup ZIP (isi soal + opsi + stimulus + aset; **tanpa** metadata bank)
- Restore ZIP ke bank target yang sudah ada
- Share: list, invite by NIK, revoke

#### Soal dalam bank
- CRUD soal
- Filter: search body, tipe, level kognitif, stimulus (termasuk “tanpa stimulus”)
- Field opsional `cognitive_level` (C1–C6)
- Reorder (drag-and-drop / naik-turun) → `sort_order`
- Duplicate (salinan di bank yang sama, suffix `(salinan)`)
- Preview soal
- Indikator “dipakai di N ujian”
- Import Excel/CSV + unduh template (kolom `cognitive_level`)
- Hapus ditolak jika terpasang di ujian

#### Stimulus
- CRUD per bank
- Satu stimulus bisa dipakai banyak soal
- Filter soal by stimulus; shortcut “tambah soal dengan stimulus ini”
- Media kaya: gambar / audio / video (upload) + embed YouTube; teks tetap via RichTextEditor
- Field `media` (JSON) + snapshot ujian menyertakan `media`

#### Integrasi ujian
- **Gelombang ujian** (`exam_periods`): admin buat UAS/UTS global + jadwal + mapel + peserta kelas
- Di dalam gelombang: banyak **ujian mapel** (`exams.exam_period_id`)
- Admin: jadwal jendela, tambah mapel, terapkan peserta dari kelas
- Guru: buka mapel → pick soal dari bank (jadwal/peserta sudah dari admin)
- Buat ujian lepas (tanpa gelombang) tetap tersedia di Daftar Ujian
- Attach → snapshot; soal se-stimulus digandeng; acak unit-aware; `stimulus_continued` di siswa

#### Import Excel (template)

Kolom: `type`, `body`, `weight`, `cognitive_level`, `key_answer`, `key_aliases`, `option_a`…`option_e`, `correct`, `stimulus_title`.

Contoh baris template: PG, isian, uraian. Matching / PG kompleks lewat form UI (bukan fokus utama template).

### 2.6 API (prefix grup exam / `v1`)

| Method | Endpoint | Keterangan |
|--------|----------|------------|
| GET/POST | `/banks` | List / create |
| GET/PUT/DELETE | `/banks/{bank_soal}` | Show / update / destroy |
| GET | `/banks/{bank_soal}/backup` | Unduh ZIP |
| POST | `/exam/exams/{exam}/questions/suggest` | Usulan ID: `mode=stimulus\|random` (belum attach) |
| POST | `/banks/restore` | Restore ZIP → bank target |
| GET | `/banks/{bank_soal}/shares` | Owner + kolaborator |
| POST | `/banks/{bank_soal}/share` | Invite `{ nik }` |
| DELETE | `/banks/{bank_soal}/share/{user_id}` | Revoke |
| * | `/question-bank` | CRUD soal |
| POST | `/question-bank/reorder` | `{ bank_soal_id, question_ids[] }` |
| GET | `/question-bank/import-template` | Template xlsx |
| POST | `/question-bank/import` | Import file ke bank |
| POST | `/question-bank/{id}/duplicate` | Duplikasi |
| * | `/question-stimuli` | CRUD stimulus |

Client frontend: `frontend/src/api/exam.js`.

### 2.7 UI & route

| Route | View | Fungsi |
|-------|------|--------|
| `/ujian-online/bank-soal` | `BankSoalList.vue` | Daftar bank, CRUD, share, backup, restore |
| `/ujian-online/bank-soal/:bankId/soal` | `QuestionBank.vue` | Kelola soal, import, reorder, preview |
| `/ujian-online/bank-soal/:bankId/stimulus` | `StimulusList.vue` | Kelola stimulus |
| (backup helper) | `QuestionBankBackup.vue` | Alur terkait backup |

Menu: **Ujian Online → Bank Soal** (`Layout.vue`, module key `online_exam`).

Entry lain: dari `TeacherMapel.vue` (tautan bank soal / ujian per mapel) dan dari `ExamDetail.vue` (pilih paket soal).

### 2.8 File kunci

**Backend**

- Controllers: `BankSoalController`, `QuestionBankController`, `QuestionStimulusController`
- Models: `BankSoal`, `QuestionBank`, `QuestionOption`, `QuestionStimulus`, `BankSoalShare`, `ExamQuestion`
- Services: `BankSoalBackupService`, `BankSoalShareService`, `QuestionBankImportService`, `ExamQuestionSnapshot`
- Requests: `StoreBankSoalRequest`, `UpdateBankSoalRequest`, `StoreQuestionBankRequest`, `UpdateQuestionBankRequest`
- Routes: `backend/routes/api/v1.php` (blok banks / question-bank / question-stimuli)

**Frontend**

- `frontend/src/views/OnlineExam/BankSoalList.vue`
- `frontend/src/views/OnlineExam/QuestionBank.vue`
- `frontend/src/views/OnlineExam/StimulusList.vue`
- `frontend/src/views/OnlineExam/ExamDetail.vue` (pemakaian bank → paket)
- `frontend/src/api/exam.js`
- Router: `frontend/src/router/index.js` (path `/ujian-online/bank-soal…`)

### 2.9 Yang belum ada (gap jelas dari codebase)

Dipakai sebagai input rencana di bagian 3 — bukan janji fitur.

| Area | Gap |
|------|-----|
| Organisasi | Tidak ada folder/bab/topik; list flat + filter |
| Metadata soal | Bloom C1–C6 sudah ada; belum kesulitan/tag/KD-CP |
| Media opsi soal | Stimulus sudah dukung gambar/audio/video/YouTube; upload media kaya di opsi soal masih terbatas |
| Share | Tidak ada level (lihat / pakai / edit / copy) |
| Status soal | Tidak ada draft / review / approved |
| Approval | Tidak ada workflow MGMP/kaprodi |
| Pemilihan ke ujian | ~~Manual pick saja~~ → keranjang + filter stimulus + set stimulus + acak N (unit stimulus dijaga); tag/kesulitan masih belum |
| Import | Matching / PG kompleks kurang terwakili di template Excel |
| Export | Ada backup ZIP; belum export Excel daftar soal / PDF printable terpisah |
| Versi | Tidak ada history revisi kunci/body |
| Modul lain | Bank hanya terhubung ke Ujian Online (bukan kuis/PR terpisah) |
| Bulk | Belum bulk hapus / pindah bank / ubah bobot massal |

---

## 3. Rencana pengembangan

Prinsip: **jangan merusak snapshot ujian dan data bank yang sudah dipakai sekolah.** Fitur baru diutamakan additive (kolom/nullable, API baru), migrasi data eksplisit jika mengubah kontrak.

### 3.1 Fase 0 — Stabilisasi & dokumentasi (sekarang)

**Tujuan:** baseline jelas, bug kritis diperbaiki, perilaku terdokumentasi.

| # | Item | Catatan |
|---|------|---------|
| 0.1 | Dokumen ini sebagai acuan | Update jika perilaku berubah |
| 0.2 | Inventaris bug / UX pain dari pemakaian nyata | Isi daftar di §3.5 |
| 0.3 | Pastikan aturan hapus/share/snapshot konsisten di UI copy | Teks bantuan sudah ada di beberapa layar |

**Done when:** tim & pemilik produk sepakat “apa yang sudah ada” = bagian 2 di dokumen ini.

### 3.2 Fase 1 — Produktivitas guru (P0)

**Tujuan:** lebih cepat mengisi dan memakai bank tanpa mengubah model besar.

| # | Item | Usulan teknis singkat | Prioritas |
|---|------|------------------------|-----------|
| 1.1 | Perbaiki / perluas **import Excel** (error per baris lebih jelas; dukung `pg_kompleks` / matching bila perlu) | `QuestionBankImportService` + template | P0 |
| 1.2 | **Export Excel** daftar soal bank (bukan hanya ZIP backup) | Endpoint export + tombol di `QuestionBank.vue` | P0 |
| 1.3 | **Bulk actions** dasar: hapus banyak (yang belum terpasang ujian), duplicate banyak | API batch + checkbox UI | P0 |
| 1.4 | Filter daftar soal: pembuat / “belum punya kunci” / dipakai di ujian | Query params di `QuestionBankController@index` | P1 |
| 1.5 | Preview lebih dekat ke tampilan siswa ujian | Komponen preview bersama dengan `ExamTake` | P1 |

### 3.3 Fase 2 — Kualitas & pencarian (P1)

**Tujuan:** soal lebih mudah dikurasi dan dipilih ke ujian.

| # | Item | Usulan teknis singkat | Prioritas |
|---|------|------------------------|-----------|
| 2.1 | Metadata: **tingkat kesulitan** + **tag bebas** *(Bloom C1–C6 sudah)* | Kolom / tabel pivot; filter di list & picker ujian | P1 |
| 2.1b | Override bobot di paket ujian | Default dari snapshot bank; boleh ubah tanpa mengubah bank | P1 |
| 2.2 | Status soal: `draft` \| `ready` (minimal) | Default `ready` untuk data lama; hanya `ready` yang bisa di-attach ke ujian | P1 |
| 2.3 | Pemilihan soal ujian: **acak N soal** (opsional filter tipe/kesulitan/tag) | ~~Dasar ada~~ (`suggestQuestions` + filter tipe/rak/grade); tag/kesulitan menyusul | P1 (sebagian done) |
| 2.4 | Organisasi ringan: **topik/bab** opsional per bank | `topic` string atau tabel `bank_soal_topics` | P2 |
| 2.5 | Share dengan **level permission** (lihat / pakai / edit) | Kolom `permission` di `bank_soal_shares` | P2 |

### 3.4 Fase 3 — Konten kaya & ekosistem (P2+)

**Tujuan:** konten lebih kaya; bank bisa dipakai lebih luas.

| # | Item | Catatan | Prioritas |
|---|------|---------|-----------|
| 3.1 | Media gambar/audio di body/opsi (pipeline upload + sanitasi) | Stimulus: **sudah** (upload + YouTube). Body/opsi soal: masih terbatas | P2 |
| 3.2 | LaTeX / rumus di editor | Evaluasi library vs kebutuhan mapel | P2 |
| 3.3 | KD/CP/ATP atau tautan kurikulum | Bergantung modul kurikulum | P2 |
| 3.4 | Version history / audit khusus ubah kunci | Di atas `Auditable` | P3 |
| 3.5 | Workflow approval (MGMP / kaprodi) | Hanya jika sekolah membutuhkannya | P3 |
| 3.6 | Pakai bank di luar ujian (kuis/PR) | Modul baru atau perluasan exam | P3 |
| 3.7 | Import Word / QTI / Moodle | Integrasi eksternal | P3 |
| 3.8 | Share lintas institusi (yayasan) | Kebijakan multi-tenant hati-hati | P3 |

### 3.5 Backlog pain point (isi dari pemakaian)

> Catat masalah nyata di sini; naikkan ke fase yang sesuai.

1. ~~Upload gambar stimulus gagal (path API `/v1/exam/question-assets/upload` vs route tanpa prefix exam)~~ — diperbaiki 2026-10-05
2. ~~Stimulus hanya judul + teks; butuh video/audio/YouTube~~ — diperbaiki 2026-10-05 (`media` JSON + panel UI)
3. ~~UI buat ujian membingungkan; pick soal bank ratusan sulit; belum mode 1 stimulus → N soal berurutan~~ — diperbaiki 2026-10-05 (wizard + mapel wajib, keranjang/picker blok, shuffle & tampilan stimulus-aware)
4. ___

### 3.6 Keputusan yang masih perlu dipilih sebelum coding besar

Centang / tulis keputusan; hindari implementasi spekulatif.

| Topik | Opsi | Keputusan |
|-------|------|-----------|
| Snapshot ujian | Tetap snapshot (disarankan) / live link | **Tetap snapshot** *(default rekomendasi)* |
| Skor | Bank bobot relatif / normalisasi 100 di ujian / override paket | **Bobot di bank + normalisasi 0–100 di penilaian** (override paket = P1) |
| Level kognitif | Tidak / C1–C6 / + kesulitan | **C1–C6 opsional di bank** (2026-10-16) |
| Metadata wajib di P1 | kesulitan + tag / hanya salah satu / ditunda | ___ |
| Status soal | `draft`/`ready` / tidak perlu | ___ |
| Random ke ujian | Ya di P1 / ditunda | Sebagian sudah (acak N + filter tipe/rak) |
| Share berlevel | Ya di P2 / cukup seperti sekarang | ___ |
| Topik/bab | String sederhana / tabel / tidak perlu | ___ |

---

## 4. Acceptance criteria (arah pengembangan)

Sesuaikan setelah keputusan §3.6 dikunci.

### Baseline (sudah harus tetap lulus)

- [x] Guru/admin berhak dapat CRUD bank per mapel di institusinya
- [x] Lima tipe soal inti dapat dibuat dari UI
- [x] Stimulus dapat dipakai bersama beberapa soal
- [x] Share by NIK & backup/restore ZIP berfungsi
- [x] Attach ke ujian membuat snapshot; edit bank tidak mengubah paket
- [x] Hapus bank/soal yang masih terpasang di ujian ditolak
- [x] Nilai peserta setelah hitung skor memakai skala 0–100
- [x] Soal dapat diberi level kognitif C1–C6 (opsional)

### Target Fase 1

- [ ] Import menampilkan error baris yang bisa ditindaklanjuti
- [ ] Export Excel isi bank tersedia dari UI
- [ ] Bulk hapus/duplicate untuk soal yang memenuhi aturan

### Target Fase 2 *(jika diputuskan)*

- [ ] Filter soal by kesulitan/tag
- [ ] Hanya soal `ready` yang bisa masuk paket ujian
- [x] Attach acak N soal dengan filter opsional (tipe/rak/grade; tag/kesulitan belum)
- [x] Set stimulus berurutan + shuffle unit-aware + UI siswa `stimulus_continued`

---

## 5. Catatan implementasi

- **Breaking change:** hindari mengubah arti `grade` (bukan ACL tingkat) dan kontrak snapshot tanpa migrasi ujian lama.
- **Migrasi data:** field baru sebaiknya nullable + default aman untuk baris lama.
- **Module toggle:** Bank Soal mengikuti entitlement/modul `online_exam`.
- **Demo seed:** `DemoSchoolService` sudah membuat bank + soal contoh; update seed jika struktur berubah.
- **Keamanan:** tetap sanitasi HTML; share lintas user/institusi perlu validasi ketat.

---

## 6. Log keputusan

| Tanggal | Keputusan | Alasan |
|---------|-----------|--------|
| 2026-10-05 | Dokumen diganti: kondisi aktual + rencana fase | Ganti kerangka kuesioner kosong dengan baseline codebase |
| | Snapshot tetap (rekomendasi) | Integritas ujian yang sudah berjalan |
| 2026-10-05 | Stimulus media kaya (image/audio/video/YouTube) + fix upload path | Pain point pemakaian nyata; Fase 3.1 sebagian untuk stimulus |
| 2026-10-05 | Mapel wajib saat buat ujian; picker keranjang + set stimulus + acak N; shuffle/tampil menjaga set | UX buat ujian & bank besar; soal se-bacaan harus berurutan |
| 2026-10-05 | Gelombang ujian (UAS wadah) + ujian mapel di dalamnya; admin jadwal/peserta, guru pick soal | Pembagian tugas admin vs guru mapel |
| 2026-10-16 | Bobot relatif di bank; nilai ujian dinormalisasi 0–100; level kognitif C1–C6 | Praktik sekolah (maks 100) + beda bentuk/level soal |

---

*Edit bebas bagian keputusan (§3.5–3.6) dan prioritas fase. Setelah dikunci, implementasi bisa mengikuti urutan Fase 0 → 1 → 2.*
