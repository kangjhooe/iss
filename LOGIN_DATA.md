# Data Login - servr.in

## Login

**Endpoint:** `POST /api/v1/login`

**Request:**
```json
{
  "email": "admin@sekolah.com",
  "password": "password123"
}
```

**Response:** Mengembalikan `user`, `token`, dan `institution` (jika ada).

## Data Login Super Admin

**Email:** `superadmin@iss.id`  
**Password:** `admin123`  
**Role:** `super_admin`

**Cara membuat / reset password:** Jalankan seeder (akan reset password & buka lock jika sudah ada):
```bash
php artisan db:seed --class=SuperAdminSeeder
```

## Sekolah Demo Publik (SMA 1 Demo Servrin)

Sekolah sandbox untuk calon pengguna mencoba sebelum mendaftar. Data fiktif; **di-reset setiap hari pukul 03:00 WIB** (`php artisan demo:reset` via scheduler).

| | |
|--|--|
| **Nama** | SMA 1 Demo Servrin |
| **NPSN** | `99990001` |
| **Password staf** | `DemoServrin1!` (semua akun guru/admin di bawah) |
| **Admin (akses penuh)** | `admin@demo.servrin.id` |
| **Siswa** | NIK `3201990000000001` / `15052008` |
| **Orang tua** | `ortu@demo.servrin.id` (tertaut ke siswa demo di atas) |

**Akun guru per peran** (password sama `DemoServrin1!`):

| Email | Peran / duty | Modul utama yang bisa dicoba |
|--------|----------------|------------------------------|
| `guru01@demo.servrin.id` | Kepala Sekolah | Apresiasi & **pelanggaran guru** (approve), piket, laporan |
| `guru02@demo.servrin.id` | Guru Piket | **Catat pelanggaran guru**, modul Guru Piket (jadwal Senin–Rabu & Jumat) |
| `guru03@demo.servrin.id` | Waka Kurikulum | Jadwal, kelas, jurnal, nilai |
| `guru04@demo.servrin.id` | Waka Sarpras | Inventaris, sarana prasarana |
| `guru05@demo.servrin.id` | Bendahara | Keuangan / SPP |
| `guru06@demo.servrin.id` | Ketua Perpus | Perpustakaan |
| `bk@demo.servrin.id` | Koordinator BK | Pelanggaran & konseling **siswa** |
| `uks@demo.servrin.id` | Koordinator UKS | UKS |
| `kesiswaan@demo.servrin.id` | Waka Kesiswaan | Data siswa, BK, UKS |
| `ppdb@demo.servrin.id` | Koordinator PPDB | PPDB |
| `ekskul@demo.servrin.id` | Koordinator Ekskul | Ekstrakurikuler |
| `operator@demo.servrin.id` | Operator Sekolah | Data siswa/guru/kelas, laporan, kepegawaian |
| `humas@demo.servrin.id` | Waka Humas | Berita & galeri, persuratan |
| `tu@demo.servrin.id` | Kepala TU | Kepegawaian (cuti/SK/jabatan), persuratan |
| `lab@demo.servrin.id` | Kepala Lab | Lab & booking, sarpras |

**Isi data demo (ringkas):** 6 kelas · 15 guru (ber-duty) · 60 siswa + 6 alumni · 1 akun orang tua · jadwal Senin–Jumat (template 5 JP) · jadwal piket + log/insiden · jurnal + absensi siswa/pegawai · nilai UH/UTS/UAS + KKM · pelanggaran siswa/guru · konseling · prestasi · ekskul (anggota, sesi, nilai) · lahan/gedung/ruang · inventaris (transaksi, peminjaman, perawatan) · perpustakaan · SPP + uang kegiatan · UKS (kunjungan + stok obat) · kalender akademik · berita & galeri · PPDB · cuti/SK/jabatan · surat + disposisi · lab + booking · arsip digital · buku tamu · permintaan ubah data · ujian online contoh · pengambilan ijazah.

**Ujian online (contoh):** PIN sesi `DEMONA` · nomor urut siswa demo = `1` (halaman Ikuti Ujian).

**Provision / reset manual:**
```bash
php artisan demo:reset
# atau
php artisan db:seed --class=DemoSchoolSeeder
```

Pastikan cron hosting menjalankan `php artisan schedule:run` setiap menit. Nonaktifkan reset dengan `DEMO_SCHOOL_RESET_ENABLED=false` di `.env` (pakai `--force` untuk reset manual).

## Registrasi

**Endpoint:** `POST /api/v1/register`

**Request:**
```json
{
  "institution_name": "Nama Sekolah",
  "institution_type": "sd|smp|sma|smk|ma",
  "institution_address": "Alamat Sekolah",
  "institution_phone": "081234567890",
  "institution_email": "sekolah@email.com",
  "name": "Nama Admin",
  "email": "admin@sekolah.com",
  "password": "password123",
  "password_confirmation": "password123"
}
```

## Role

| Role | Deskripsi |
|------|-----------|
| `super_admin` | Akses penuh ke semua institusi |
| `institution_admin` | Akses penuh ke data institusi sendiri |
| `teacher` | Akses terbatas ke data kelas/mata pelajaran |
| `student` | Akses terbatas ke data pribadi |

## Login Siswa

- **Username:** NIK (16 digit)
- **Sandi awal:** tanggal lahir `DDMMYYYY` (contoh: 15 Maret 2010 → `15032010`)
- Akun dibuat otomatis saat admin menambah/mengedit/import siswa (NIK + tanggal lahir wajib).
- Setelah login pertama, siswa **wajib ganti sandi**.
- Admin dapat reset sandi ke tanggal lahir dari biodata siswa (Data Siswa → Lihat → Akun Login).
- Request login menerima `login` (NIK atau email). Field `email` masih didukung untuk kompatibilitas.

## Membuat Akun Manual (Database)

Password harus di-hash dengan bcrypt. Generate hash:
```bash
php artisan tinker
>>> Hash::make('password123')
```

Contoh SQL:
```sql
INSERT INTO `user` (`institution_id`, `name`, `email`, `password`, `role`, `created_at`, `updated_at`)
VALUES (NULL, 'Super Admin', 'superadmin@iss.id', '$2y$12$...', 'super_admin', NOW(), NOW());
```

## Validasi & Keamanan

- Email: wajib, format valid, terdaftar di database
- Password: wajib, di-hash dengan bcrypt
- Institusi: harus aktif (`is_active = true`)
- Akun terkunci: setelah 5 percobaan gagal (30 menit)
- Rate limiting: 5 requests/minute per IP
- Token: Laravel Sanctum, simpan untuk request selanjutnya
- Logout: `POST /api/v1/logout` dengan header `Authorization: Bearer {token}`
