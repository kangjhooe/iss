# Standar Kop Laporan Cetak

Dokumen ini menetapkan bentuk baku kop untuk laporan cetak. Referensi visual utama adalah kop laporan Alumni pada `frontend/src/views/Alumni.vue`.

## Susunan wajib

Urutan isi kop dari atas ke bawah:

1. Logo institusi di sisi kiri.
2. Nama yayasan (opsional), satu baris.
3. Nama institusi.
4. Alamat lengkap.
5. NPSN, NSS, telepon, email, dan situs web.
6. Garis ganda sebagai pembatas kop.

Kolom kosong selebar kolom logo wajib disediakan di sisi kanan agar seluruh teks tetap berada tepat di tengah halaman. Jika nama yayasan kosong, baris tersebut tidak ditampilkan.

## Ukuran dan gaya

- Area kop: `border-bottom: 3px double #111`, padding bawah `8px`, margin bawah `10px`.
- Tata letak: tiga kolom `76px 1fr 76px`, rata tengah vertikal, tinggi minimum `70px`.
- Logo: maksimum `66px × 66px`, `object-fit: contain`.
- Nama yayasan: Times New Roman, `14px`, bobot `600`, huruf kapital, satu baris.
- Nama institusi: Times New Roman, `18px`, bobot `700`, huruf kapital.
- Alamat: Arial/Helvetica, `10px`, tinggi baris `1.35`, margin atas `3px`.
- Identitas dan kontak: `9px`, margin atas `2px`.
- Warna teks: `#111`.

Nama yayasan tidak boleh menggeser atau menimpa logo. Gunakan `min-width: 0` pada kolom teks serta `white-space: nowrap`, `overflow: hidden`, dan `text-overflow: ellipsis` pada nama yayasan.

## CSS acuan frontend

```css
.kop {
  border-bottom: 3px double #111;
  padding: 0 8px 8px;
  margin-bottom: 10px;
}

.kop-inner {
  display: grid;
  grid-template-columns: 76px 1fr 76px;
  align-items: center;
  min-height: 70px;
}

.kop-logo {
  width: 66px;
  height: 66px;
  object-fit: contain;
}

.kop-text {
  min-width: 0;
  text-align: center;
}

.foundation {
  overflow: hidden;
  font-family: "Times New Roman", serif;
  font-size: 14px;
  font-weight: 600;
  line-height: 1.15;
  text-transform: uppercase;
  text-overflow: ellipsis;
  white-space: nowrap;
  letter-spacing: 0.02em;
}

.school {
  font-family: "Times New Roman", serif;
  font-size: 18px;
  font-weight: 700;
  text-transform: uppercase;
}

.school-address {
  font-size: 10px;
  line-height: 1.35;
  margin-top: 3px;
}

.school-info {
  font-size: 9px;
  margin-top: 2px;
}
```

## HTML acuan frontend

```html
<header class="kop">
  <div class="kop-inner">
    <div><!-- logo institusi --></div>
    <div class="kop-text">
      <!-- nama yayasan, hanya jika tersedia -->
      <div class="foundation">NAMA YAYASAN</div>
      <div class="school">NAMA INSTITUSI</div>
      <div class="school-address">ALAMAT LENGKAP</div>
      <div class="school-info">
        NPSN: ... · NSS: ... · Telp: ... · Email: ... · situs-web
      </div>
    </div>
    <div></div>
  </div>
</header>
```

Semua nilai dinamis pada HTML yang dibuat di browser wajib di-escape sebelum dimasukkan ke markup.

## Acuan Blade/PDF

Untuk renderer PDF yang tidak mendukung CSS Grid secara konsisten, gunakan tabel tiga kolom tanpa border:

- kolom kiri dan kanan masing-masing `76px`;
- kolom tengah rata tengah dan memiliki perilaku setara `min-width: 0`;
- logo maksimum `66px × 66px`;
- tipografi dan garis pembatas mengikuti ukuran di atas.

Gunakan path file lokal (`public_path` atau `storage_path`) untuk logo saat renderer PDF tidak dapat mengambil URL HTTP.

## Ukuran kertas

- Default: A4 portrait, margin sekitar `10mm`.
- Landscape hanya untuk tabel lebar yang tidak dapat dibaca dengan layak dalam portrait.
- Orientasi halaman tidak boleh mengubah proporsi kop.

## Identitas guru dalam tabel

Jika laporan menampilkan nama guru/pegawai:

- jangan buat kolom terpisah khusus NIP/NUPTK;
- tampilkan NIP atau NUPTK di baris kecil tepat di bawah nama;
- jika keduanya kosong, tulis `Tanpa NIP/NUPTK`.

Contoh:

```html
<td>
  <div>Nama Guru</div>
  <div class="cell-note">NIP / NUPTK</div>
</td>
```

## Blok tanda tangan

Setiap laporan cetak resmi wajib menyertakan blok tanda tangan penandatangan:

1. Tempat dan tanggal (opsional tetapi disarankan).
2. Jabatan penandatangan — **satu jabatan saja**, tanpa gabungan seperti "Kepala Sekolah/Admin" atau "Kepala Sekolah/Madrasah":
   - sekolah umum (SD, SMP, SMA, SMK, PAUD, TK, dll.): `Kepala Sekolah`
   - madrasah (MI, MTs, MA, MAK): `Kepala Madrasah`
3. Ruang tanda tangan.
4. Nama lengkap (`principal_name`).
5. NIP (`principal_nip`) tepat di bawah nama.

Jika NIP kosong, tetap tampilkan placeholder `NIP. ___________________`.

Frontend: gunakan `getPrincipalTitle(level)` dari `frontend/src/utils/institution.js`.
Backend: gunakan `$institution->principal_title` atau `Institution::principalTitleForLevel($level)`.

## Pengecualian

Modul Surat menggunakan kop yang dapat dikonfigurasi pengguna (`baris_1`, `baris_2`, `baris_3`, atau HTML khusus). Kop Surat tidak ditimpa otomatis oleh standar laporan ini.

