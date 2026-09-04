# TODO

## Belum dibangun (modul / produk)

- [ ] Billing & subscription Super Admin lanjutan (invoice, trial otomatis, suspend tenant) — fondasi paket/add-on/dark-launch sudah ada di `/super-admin/monetisasi`

## Matangkan modul Beta

### Keuangan (`/keuangan/*`)
- [ ] Payment gateway (opsional; saat ini pencatatan pembayaran staff-only)

### Ujian Online (`/ujian-online/*`) — Beta
- [ ] Luluskan dari status Beta (stabilitas beban tinggi — tetap Beta untuk sekarang)

## Monetisasi (add-on / paket)

Fondasi dark launch sudah ada (`/super-admin/monetisasi`). Default **tersembunyi** dari semua sekolah sampai Super Admin centang “Tampilkan monetisasi ke sekolah”.

- [ ] Billing & subscription penuh (invoice, trial otomatis, suspend tenant, gateway)
- [ ] Footer branding PDF (“dicetak melalui servr.in”) sebagai add-on / paket berbayar; audit trail “dicetak oleh” tetap gratis

## Portal Orang Tua (`/parent/*`)

Dasar sudah ada (role `parent`, dashboard, jadwal/nilai/absensi/pelanggaran per anak, `parent_links`). Satu akun multi-anak satu sekolah sudah didukung.

- [x] UI admin untuk buat akun ortu + tautkan/lepaskan anak (`parent_links`) — `/orang-tua`
- [x] Multi-sekolah: pengumuman/kalender gabungan dari semua sekolah anak
- [x] Multi-sekolah: match otomatis via `guardian_phone` tidak terbatas satu `user.institution_id`
- [x] Mobile nav: pintasan Nilai/Absensi tidak hanya anak pertama (picker jika multi-anak)

## Peningkatan operasional sekolah

- [ ] Template raport sesuai format resmi (jika belum lengkap)

- [ ] Banyak kode mungkin butuh direfactor, tapi pastikan tidak merusak aplikasi
