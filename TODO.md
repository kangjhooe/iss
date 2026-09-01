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

## Peningkatan operasional sekolah

- [ ] Template raport sesuai format resmi (jika belum lengkap)

- [ ] Banyak kode mungkin butuh direfactor, tapi pastikan tidak merusak aplikasi

### Penggajian (`/penggajian/*`) — MVP selesai
- [x] Komponen, profil gaji, periode, proses batch, slip PDF, portal pegawai
- [x] Integrasi pengeluaran otomatis ke Keuangan saat gaji dibayar
- [x] Tunjangan jabatan struktural otomatis + THR opsional