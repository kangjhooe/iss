<?php

/**
 * Shared platform template definitions (used by TemplateSuratSeeder + CLI seed).
 */

if (!function_exists('iss_tpl_biodata_lengkap')) {
    function iss_tpl_biodata_lengkap(): string
    {
        return <<<'HTML'
<table style="width:100%; border:none; border-collapse:collapse; margin:12px 0 16px;">
  <tr><td style="border:none; width:160px; padding:3px 0; vertical-align:top;">Nama</td><td style="border:none; padding:3px 0;">: {{nama}}</td></tr>
  <tr><td style="border:none; padding:3px 0; vertical-align:top;">NIS</td><td style="border:none; padding:3px 0;">: {{nis}}</td></tr>
  <tr><td style="border:none; padding:3px 0; vertical-align:top;">NISN</td><td style="border:none; padding:3px 0;">: {{nisn}}</td></tr>
  <tr><td style="border:none; padding:3px 0; vertical-align:top;">Kelas</td><td style="border:none; padding:3px 0;">: {{kelas}}</td></tr>
  <tr><td style="border:none; padding:3px 0; vertical-align:top;">Tempat, Tanggal Lahir</td><td style="border:none; padding:3px 0;">: {{ttl}}</td></tr>
  <tr><td style="border:none; padding:3px 0; vertical-align:top;">Jenis Kelamin</td><td style="border:none; padding:3px 0;">: ................................</td></tr>
  <tr><td style="border:none; padding:3px 0; vertical-align:top;">Agama</td><td style="border:none; padding:3px 0;">: {{agama}}</td></tr>
  <tr><td style="border:none; padding:3px 0; vertical-align:top;">Alamat</td><td style="border:none; padding:3px 0;">: {{alamat}}</td></tr>
  <tr><td style="border:none; padding:3px 0; vertical-align:top;">Nama Ayah</td><td style="border:none; padding:3px 0;">: {{ayah}}</td></tr>
  <tr><td style="border:none; padding:3px 0; vertical-align:top;">Nama Ibu</td><td style="border:none; padding:3px 0;">: {{ibu}}</td></tr>
</table>
HTML;
    }
}

if (!function_exists('iss_tpl_biodata_singkat')) {
    function iss_tpl_biodata_singkat(): string
    {
        return <<<'HTML'
<table style="width:100%; border:none; border-collapse:collapse; margin:12px 0 16px;">
  <tr><td style="border:none; width:160px; padding:3px 0; vertical-align:top;">Nama</td><td style="border:none; padding:3px 0;">: {{nama}}</td></tr>
  <tr><td style="border:none; padding:3px 0; vertical-align:top;">NIS / NISN</td><td style="border:none; padding:3px 0;">: {{nis}} / {{nisn}}</td></tr>
  <tr><td style="border:none; padding:3px 0; vertical-align:top;">Kelas</td><td style="border:none; padding:3px 0;">: {{kelas}}</td></tr>
  <tr><td style="border:none; padding:3px 0; vertical-align:top;">Tempat, Tanggal Lahir</td><td style="border:none; padding:3px 0;">: {{ttl}}</td></tr>
  <tr><td style="border:none; padding:3px 0; vertical-align:top;">Alamat</td><td style="border:none; padding:3px 0;">: {{alamat}}</td></tr>
</table>
HTML;
    }
}

if (!function_exists('iss_tpl_biodata_pegawai')) {
    function iss_tpl_biodata_pegawai(): string
    {
        return <<<'HTML'
<table style="width:100%; border:none; border-collapse:collapse; margin:12px 0 16px;">
  <tr><td style="border:none; width:160px; padding:3px 0; vertical-align:top;">Nama</td><td style="border:none; padding:3px 0;">: {{nama}}</td></tr>
  <tr><td style="border:none; padding:3px 0; vertical-align:top;">NIP / NUPTK</td><td style="border:none; padding:3px 0;">: {{nip_pegawai}} / {{nuptk}}</td></tr>
  <tr><td style="border:none; padding:3px 0; vertical-align:top;">Jabatan</td><td style="border:none; padding:3px 0;">: {{jabatan}}</td></tr>
  <tr><td style="border:none; padding:3px 0; vertical-align:top;">Mata Pelajaran</td><td style="border:none; padding:3px 0;">: {{mata_pelajaran}}</td></tr>
  <tr><td style="border:none; padding:3px 0; vertical-align:top;">Status Kepegawaian</td><td style="border:none; padding:3px 0;">: {{status_kepegawaian}}</td></tr>
  <tr><td style="border:none; padding:3px 0; vertical-align:top;">Unit Kerja</td><td style="border:none; padding:3px 0;">: {{nama_institusi}}</td></tr>
</table>
HTML;
    }
}

if (!function_exists('iss_tpl_ttd_standard')) {
    function iss_tpl_ttd_standard(string $jabatan = 'Kepala {{nama_institusi}}'): string
    {
        return <<<HTML
<table style="width:100%; border:none; border-collapse:collapse; margin-top:36px;">
  <tr>
    <td style="border:none; width:50%; vertical-align:top;">&nbsp;</td>
    <td style="border:none; width:50%; vertical-align:top; text-align:center;">
      <p style="margin:0 0 6px;">{{kota}}, {{tanggal_lengkap}}</p>
      <p style="margin:0 0 80px;">{$jabatan},</p>
      <p style="margin:0; font-weight:bold; text-decoration:underline;">{{kepala_madrasah}}</p>
      <p style="margin:6px 0 0; font-size:11pt;">NIP. {{nip}}</p>
    </td>
  </tr>
</table>
HTML;
    }
}

if (!function_exists('iss_tpl_ttd_sk')) {
    function iss_tpl_ttd_sk(): string
    {
        return <<<'HTML'
<table style="width:100%; border:none; border-collapse:collapse; margin-top:36px;">
  <tr>
    <td style="border:none; width:50%; vertical-align:top;">&nbsp;</td>
    <td style="border:none; width:50%; vertical-align:top; text-align:center;">
      <p style="margin:0 0 80px;">Kepala {{nama_institusi}},</p>
      <p style="margin:0; font-weight:bold; text-decoration:underline;">{{kepala_madrasah}}</p>
      <p style="margin:6px 0 0; font-size:11pt;">NIP. {{nip}}</p>
    </td>
  </tr>
</table>
HTML;
    }
}

if (!function_exists('iss_tpl_ttd_dua_pihak')) {
    function iss_tpl_ttd_dua_pihak(): string
    {
        return <<<'HTML'
<table style="width:100%; border:none; border-collapse:collapse; margin-top:36px;">
  <tr>
    <td style="border:none; width:50%; vertical-align:top; text-align:center; padding-right:12px;">
      <p style="margin:0 0 6px;">Pihak Kedua,</p>
      <p style="margin:0 0 80px;">Orang Tua / Wali Murid</p>
      <p style="margin:0; font-weight:bold; text-decoration:underline;">................................</p>
    </td>
    <td style="border:none; width:50%; vertical-align:top; text-align:center; padding-left:12px;">
      <p style="margin:0 0 6px;">{{kota}}, {{tanggal_lengkap}}</p>
      <p style="margin:0 0 80px;">Kepala {{nama_institusi}},</p>
      <p style="margin:0; font-weight:bold; text-decoration:underline;">{{kepala_madrasah}}</p>
      <p style="margin:6px 0 0; font-size:11pt;">NIP. {{nip}}</p>
    </td>
  </tr>
</table>
HTML;
    }
}

if (!function_exists('iss_tpl_kepada')) {
    function iss_tpl_kepada(string $target = 'Bapak/Ibu Orang Tua / Wali Murid'): string
    {
        return <<<HTML
<table style="width:100%; border:none; border-collapse:collapse; margin:0 0 16px;">
  <tr>
    <td style="border:none; width:80px; vertical-align:top;">Kepada</td>
    <td style="border:none; vertical-align:top;">Yth.</td>
  </tr>
  <tr>
    <td style="border:none;">&nbsp;</td>
    <td style="border:none;">
      {$target}<br>
      <strong>{{nama}}</strong> (Kelas {{kelas}})<br>
      di tempat
    </td>
  </tr>
</table>
HTML;
    }
}

if (!function_exists('iss_tpl_wrap')) {
    function iss_tpl_wrap(string $title, ?string $perihal, string $body, string $signature = 'standard'): string
    {
        $perihalHtml = $perihal
            ? '<p style="margin:8px 0 16px;">Perihal&nbsp;&nbsp;&nbsp;: <strong>' . htmlspecialchars($perihal, ENT_QUOTES, 'UTF-8') . '</strong></p>'
            : '';

        $body = str_replace(
            ['{{BIODATA_LENGKAP}}', '{{BIODATA_SINGKAT}}', '{{BIODATA_PEGAWAI}}'],
            [iss_tpl_biodata_lengkap(), iss_tpl_biodata_singkat(), iss_tpl_biodata_pegawai()],
            $body
        );

        $signatureHtml = match ($signature) {
            'none' => '',
            'sk' => iss_tpl_ttd_sk(),
            'dual' => iss_tpl_ttd_dua_pihak(),
            default => iss_tpl_ttd_standard(),
        };

        return <<<HTML
<div style="text-align:center; margin-bottom:20px;">
  <h2 style="margin:0; font-size:14pt; text-transform:uppercase; letter-spacing:0.5px;">{$title}</h2>
</div>
<p style="margin:0 0 8px;">Nomor&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;: <strong>{{nomor_surat}}</strong></p>
{$perihalHtml}
{$body}
{$signatureHtml}
HTML;
    }
}

return [
    [
        'kode' => 'SKAB',
        'nama' => 'Surat Keterangan Aktif Belajar',
        'letter_type_code' => '09',
        'isi_html' => iss_tpl_wrap('Surat Keterangan Aktif Belajar', null, <<<'HTML'
<p style="text-align:justify;">
  Yang bertanda tangan di bawah ini, Kepala {{nama_institusi}}, dengan ini menerangkan bahwa:
</p>
{{BIODATA_LENGKAP}}
<p style="text-align:justify;">
  Adalah benar-benar siswa/i yang masih aktif belajar di {{nama_institusi}} pada tahun pelajaran berjalan,
  terdaftar dalam buku induk peserta didik, dan mengikuti kegiatan pembelajaran sesuai jadwal yang berlaku.
</p>
<p style="text-align:justify;">
  Surat keterangan ini diberikan atas permohonan yang bersangkutan untuk keperluan
  ................................ dan dapat dipergunakan sebagaimana mestinya.
</p>
<p style="text-align:justify;">
  Demikian surat keterangan ini dibuat dengan sebenarnya, berdasarkan data yang tercatat pada institusi kami.
</p>
HTML),
    ],
    [
        'kode' => 'SKBK',
        'nama' => 'Surat Keterangan Berkelakuan Baik',
        'letter_type_code' => '09',
        'isi_html' => iss_tpl_wrap('Surat Keterangan Berkelakuan Baik', null, <<<'HTML'
<p style="text-align:justify;">
  Yang bertanda tangan di bawah ini, Kepala {{nama_institusi}}, dengan sesungguhnya menerangkan bahwa:
</p>
{{BIODATA_SINGKAT}}
<p style="text-align:justify;">
  Adalah benar-benar siswa/i {{nama_institusi}} yang selama menjadi peserta didik di institusi kami
  berperilaku baik, taat tata tertib, dan tidak pernah terlibat pelanggaran berat yang memerlukan
  sanksi administrasi tingkat sekolah.
</p>
<p style="text-align:justify;">
  Surat keterangan ini diberikan untuk keperluan
  ................................ dan dapat dipergunakan sebagaimana mestinya.
</p>
<p style="text-align:justify;">
  Demikian surat keterangan ini dibuat dengan sebenarnya untuk dapat dipertanggungjawabkan apabila diperlukan.
</p>
HTML),
    ],
    [
        'kode' => 'SKMP',
        'nama' => 'Surat Keterangan Menerima Siswa Pindahan',
        'letter_type_code' => '09',
        'isi_html' => iss_tpl_wrap('Surat Keterangan Menerima Siswa Pindahan', null, <<<'HTML'
<p style="text-align:justify;">
  Yang bertanda tangan di bawah ini, Kepala {{nama_institusi}}, menerangkan bahwa:
</p>
{{BIODATA_LENGKAP}}
<table style="width:100%; border:none; border-collapse:collapse; margin:0 0 16px;">
  <tr><td style="border:none; width:160px; padding:3px 0;">Asal Sekolah</td><td style="border:none; padding:3px 0;">: ................................</td></tr>
  <tr><td style="border:none; padding:3px 0;">Alamat Sekolah Asal</td><td style="border:none; padding:3px 0;">: ................................</td></tr>
  <tr><td style="border:none; padding:3px 0;">Diterima di Kelas</td><td style="border:none; padding:3px 0;">: {{kelas}}</td></tr>
  <tr><td style="border:none; padding:3px 0;">Tanggal Diterima</td><td style="border:none; padding:3px 0;">: {{tanggal_lengkap}}</td></tr>
  <tr><td style="border:none; padding:3px 0;">Nomor Surat Pindah</td><td style="border:none; padding:3px 0;">: ................................</td></tr>
</table>
<p style="text-align:justify;">
  Adalah benar telah diterima sebagai peserta didik pindahan di {{nama_institusi}} setelah
  menyelesaikan persyaratan administrasi dan verifikasi data sesuai ketentuan yang berlaku.
</p>
<p style="text-align:justify;">
  Surat keterangan ini dibuat untuk keperluan administrasi, pelaporan, dan keperluan lain
  yang sah terkait status peserta didik pindahan tersebut.
</p>
<p style="text-align:justify;">
  Demikian surat keterangan ini dibuat dengan sebenarnya untuk dipergunakan sebagaimana mestinya.
</p>
HTML),
    ],
    [
        'kode' => 'SKKP',
        'nama' => 'Surat Keterangan Pindah / Keluar Sekolah',
        'letter_type_code' => '09',
        'isi_html' => iss_tpl_wrap('Surat Keterangan Pindah / Keluar Sekolah', null, <<<'HTML'
<p style="text-align:justify;">
  Yang bertanda tangan di bawah ini, Kepala {{nama_institusi}}, menerangkan bahwa:
</p>
{{BIODATA_LENGKAP}}
<table style="width:100%; border:none; border-collapse:collapse; margin:0 0 16px;">
  <tr><td style="border:none; width:160px; padding:3px 0;">Kelas Terakhir</td><td style="border:none; padding:3px 0;">: {{kelas}}</td></tr>
  <tr><td style="border:none; padding:3px 0;">Alasan Pindah / Keluar</td><td style="border:none; padding:3px 0;">: ................................</td></tr>
  <tr><td style="border:none; padding:3px 0;">Sekolah Tujuan</td><td style="border:none; padding:3px 0;">: ................................</td></tr>
  <tr><td style="border:none; padding:3px 0;">Alamat Sekolah Tujuan</td><td style="border:none; padding:3px 0;">: ................................</td></tr>
  <tr><td style="border:none; padding:3px 0;">Tanggal Efektif</td><td style="border:none; padding:3px 0;">: {{tanggal_lengkap}}</td></tr>
</table>
<p style="text-align:justify;">
  Adalah benar peserta didik {{nama_institusi}} yang mengajukan permohonan pindah/keluar sekolah.
  Hingga tanggal surat ini diterbitkan, yang bersangkutan telah menyelesaikan kewajiban administrasi,
  peminjaman buku/perangkat, dan kewajiban lain sesuai ketentuan institusi.
</p>
<p style="text-align:justify;">
  Surat keterangan ini dibuat untuk keperluan administrasi pindah sekolah dan dapat dipergunakan
  sebagaimana mestinya oleh institusi tujuan maupun pihak yang berwenang.
</p>
<p style="text-align:justify;">
  Demikian surat keterangan ini dibuat dengan sebenarnya berdasarkan data yang tercatat pada kami.
</p>
HTML),
    ],
    [
        'kode' => 'SKLL',
        'nama' => 'Surat Keterangan Lulus / Selesai Belajar',
        'letter_type_code' => '09',
        'isi_html' => iss_tpl_wrap('Surat Keterangan Lulus / Selesai Belajar', null, <<<'HTML'
<p style="text-align:justify;">
  Yang bertanda tangan di bawah ini, Kepala {{nama_institusi}}, menerangkan bahwa:
</p>
{{BIODATA_SINGKAT}}
<table style="width:100%; border:none; border-collapse:collapse; margin:0 0 16px;">
  <tr><td style="border:none; width:160px; padding:3px 0;">Tahun Lulus / Selesai</td><td style="border:none; padding:3px 0;">: ................................</td></tr>
  <tr><td style="border:none; padding:3px 0;">Program / Jurusan</td><td style="border:none; padding:3px 0;">: ................................</td></tr>
  <tr><td style="border:none; padding:3px 0;">Status Kelulusan</td><td style="border:none; padding:3px 0;">: Lulus / Selesai Belajar</td></tr>
</table>
<p style="text-align:justify;">
  Adalah benar-benar peserta didik {{nama_institusi}} yang telah menyelesaikan seluruh kewajiban
  pembelajaran dan administrasi pada jenjang pendidikan yang ditetapkan, serta dinyatakan
  lulus / selesai belajar sesuai ketentuan yang berlaku.
</p>
<p style="text-align:justify;">
  Surat keterangan ini diberikan untuk keperluan melanjutkan pendidikan, melamar pekerjaan,
  atau keperluan administrasi lain yang sah.
</p>
<p style="text-align:justify;">
  Demikian surat keterangan ini dibuat dengan sebenarnya untuk dipergunakan sebagaimana mestinya.
</p>
HTML),
    ],
    [
        'kode' => 'SKTN',
        'nama' => 'Surat Keterangan Tidak Mampu',
        'letter_type_code' => '09',
        'isi_html' => iss_tpl_wrap('Surat Keterangan Tidak Mampu', null, <<<'HTML'
<p style="text-align:justify;">
  Yang bertanda tangan di bawah ini, Kepala {{nama_institusi}}, menerangkan bahwa:
</p>
{{BIODATA_LENGKAP}}
<p style="text-align:justify;">
  Berdasarkan data peserta didik dan keterangan yang ada pada kami, orang tua/wali dari
  peserta didik tersebut termasuk dalam kategori keluarga tidak mampu / kurang mampu secara ekonomi.
</p>
<p style="text-align:justify;">
  Surat keterangan ini diberikan untuk keperluan pengajuan bantuan pendidikan, beasiswa,
  keringanan biaya, atau keperluan administrasi lain, yaitu:
  .................................
</p>
<p style="text-align:justify;">
  Apabila di kemudian hari terdapat perubahan kondisi ekonomi keluarga, pihak sekolah tidak
  bertanggung jawab atas perubahan status tersebut di luar data yang tercatat saat surat diterbitkan.
</p>
<p style="text-align:justify;">
  Demikian surat keterangan ini dibuat dengan sebenarnya untuk dipergunakan sebagaimana mestinya.
</p>
HTML),
    ],
    [
        'kode' => 'SKDT',
        'nama' => 'Surat Keterangan Domisili Siswa',
        'letter_type_code' => '09',
        'isi_html' => iss_tpl_wrap('Surat Keterangan Domisili', null, <<<'HTML'
<p style="text-align:justify;">
  Yang bertanda tangan di bawah ini, Kepala {{nama_institusi}}, menerangkan bahwa:
</p>
{{BIODATA_SINGKAT}}
<p style="text-align:justify;">
  Adalah benar-benar peserta didik {{nama_institusi}} yang bertempat tinggal / berdomisili
  pada alamat tersebut di atas, bersama orang tua/wali yang bertanggung jawab atas pendidikannya.
</p>
<p style="text-align:justify;">
  Surat keterangan domisili ini diberikan untuk keperluan administrasi, verifikasi alamat,
  pengurusan dokumen resmi, atau keperluan lain yang sah, yaitu:
  .................................
</p>
<p style="text-align:justify;">
  Demikian surat keterangan ini dibuat berdasarkan data yang tercatat pada institusi kami
  dan dapat dipergunakan sebagaimana mestinya.
</p>
HTML),
    ],
    [
        'kode' => 'SKIK',
        'nama' => 'Surat Keterangan Izin Kegiatan / Tidak Masuk',
        'letter_type_code' => '09',
        'isi_html' => iss_tpl_wrap('Surat Keterangan Izin', null, <<<'HTML'
<p style="text-align:justify;">
  Yang bertanda tangan di bawah ini, Kepala {{nama_institusi}}, memberikan izin kepada:
</p>
{{BIODATA_SINGKAT}}
<table style="width:100%; border:none; border-collapse:collapse; margin:0 0 16px;">
  <tr><td style="border:none; width:160px; padding:3px 0;">Keperluan</td><td style="border:none; padding:3px 0;">: ................................</td></tr>
  <tr><td style="border:none; padding:3px 0;">Hari / Tanggal</td><td style="border:none; padding:3px 0;">: ................................</td></tr>
  <tr><td style="border:none; padding:3px 0;">Waktu</td><td style="border:none; padding:3px 0;">: ................................</td></tr>
  <tr><td style="border:none; padding:3px 0;">Tempat / Lokasi</td><td style="border:none; padding:3px 0;">: ................................</td></tr>
  <tr><td style="border:none; padding:3px 0;">Pendamping</td><td style="border:none; padding:3px 0;">: ................................</td></tr>
</table>
<p style="text-align:justify;">
  Untuk tidak mengikuti kegiatan pembelajaran di sekolah / mengikuti kegiatan di luar sekolah
  sebagaimana keterangan di atas, dengan catatan peserta didik tetap bertanggung jawab
  mengejar materi yang ketinggalan sesuai kesepakatan dengan wali kelas / guru mata pelajaran.
</p>
<p style="text-align:justify;">
  Demikian surat keterangan izin ini dibuat untuk diketahui pihak terkait dan dipergunakan
  sebagaimana mestinya.
</p>
HTML),
    ],
    [
        'kode' => 'SKOR',
        'nama' => 'Surat Keterangan Orang Tua / Wali',
        'letter_type_code' => '09',
        'isi_html' => iss_tpl_wrap('Surat Keterangan Orang Tua / Wali', null, <<<'HTML'
<p style="text-align:justify;">
  Yang bertanda tangan di bawah ini, Kepala {{nama_institusi}}, menerangkan bahwa:
</p>
<table style="width:100%; border:none; border-collapse:collapse; margin:12px 0 8px;">
  <tr><td style="border:none; width:160px; padding:3px 0;">Nama Ayah</td><td style="border:none; padding:3px 0;">: {{ayah}}</td></tr>
  <tr><td style="border:none; padding:3px 0;">Nama Ibu</td><td style="border:none; padding:3px 0;">: {{ibu}}</td></tr>
  <tr><td style="border:none; padding:3px 0;">Alamat</td><td style="border:none; padding:3px 0;">: {{alamat}}</td></tr>
</table>
<p style="text-align:justify;">Adalah benar orang tua/wali dari peserta didik:</p>
{{BIODATA_SINGKAT}}
<p style="text-align:justify;">
  Surat keterangan ini diberikan untuk keperluan administrasi, verifikasi hubungan keluarga,
  pengurusan dokumen, atau keperluan lain yang sah terkait orang tua/wali peserta didik tersebut.
</p>
<p style="text-align:justify;">
  Demikian surat keterangan ini dibuat berdasarkan data yang tercatat pada institusi kami
  dan dapat dipergunakan sebagaimana mestinya.
</p>
HTML),
    ],
    [
        'kode' => 'SKPR',
        'nama' => 'Surat Peringatan Siswa',
        'letter_type_code' => '09',
        'isi_html' => iss_tpl_wrap('Surat Peringatan', 'Peringatan kepada Orang Tua / Wali Murid', <<<'HTML'
HTML
. iss_tpl_kepada() . <<<'HTML'

<p style="text-align:justify;">
  Dengan hormat,
</p>
<p style="text-align:justify;">
  Bersama surat ini kami sampaikan peringatan resmi dari {{nama_institusi}} terkait peserta didik
  atas nama <strong>{{nama}}</strong>, NIS <strong>{{nis}}</strong>, kelas <strong>{{kelas}}</strong>,
  yang telah melakukan pelanggaran / memerlukan perhatian khusus karena:
</p>
<p style="text-align:justify; margin:12px 0;">
  ................................................................................................................................
</p>
<p style="text-align:justify;">
  Pelanggaran/perilaku tersebut bertentangan dengan tata tertib dan budaya positif sekolah.
  Kami mengharapkan kerja sama Bapak/Ibu untuk memberikan bimbingan, pengawasan, dan pendampingan
  agar putra/putri dapat memperbaiki sikap dan kembali menyesuaikan diri dengan aturan sekolah.
</p>
<p style="text-align:justify;">
  Apabila dalam waktu yang ditentukan tidak terdapat perbaikan, sekolah akan mengambil tindak lanjut
  sesuai ketentuan tata tertib dan peraturan yang berlaku.
</p>
<p style="text-align:justify;">
  Demikian surat peringatan ini kami sampaikan. Atas perhatian dan kerja sama Bapak/Ibu, kami ucapkan terima kasih.
</p>
HTML),
    ],
    [
        'kode' => 'SPOT',
        'nama' => 'Surat Panggilan Orang Tua / Wali',
        'letter_type_code' => '02',
        'subject_type' => 'siswa',
        'isi_html' => iss_tpl_wrap('Surat Panggilan', 'Panggilan Orang Tua / Wali Murid', <<<'HTML'
HTML
. iss_tpl_kepada() . <<<'HTML'

<p style="text-align:justify;">
  Dengan hormat,
</p>
<p style="text-align:justify;">
  Sehubungan dengan adanya permasalahan yang memerlukan penanganan bersama terkait putra/putri Bapak/Ibu
  atas nama <strong>{{nama}}</strong>, NIS <strong>{{nis}}</strong>, kelas <strong>{{kelas}}</strong>,
  dengan ini kami memanggil Bapak/Ibu Orang Tua / Wali untuk hadir di {{nama_institusi}}
  guna membahas dan mencari penyelesaian secara kekeluargaan.
</p>
<p style="text-align:justify; margin:12px 0 4px;">
  Ringkasan permasalahan / alasan panggilan:
</p>
<p style="text-align:justify; margin:0 0 16px;">
  ................................................................................................................................
</p>
<table style="width:100%; border:none; border-collapse:collapse; margin:12px 0 16px;">
  <tr><td style="border:none; width:160px; padding:3px 0;">Hari / Tanggal</td><td style="border:none; padding:3px 0;">: {{tanggal_lengkap}}</td></tr>
  <tr><td style="border:none; padding:3px 0;">Waktu</td><td style="border:none; padding:3px 0;">: ........ WIB</td></tr>
  <tr><td style="border:none; padding:3px 0;">Tempat</td><td style="border:none; padding:3px 0;">: {{nama_institusi}}</td></tr>
  <tr><td style="border:none; padding:3px 0;">Menghadap</td><td style="border:none; padding:3px 0;">: Wali Kelas / Guru BK / Kepala Sekolah</td></tr>
  <tr><td style="border:none; padding:3px 0;">Yang dibawa</td><td style="border:none; padding:3px 0;">: Surat panggilan ini</td></tr>
</table>
<p style="text-align:justify;">
  Kehadiran Bapak/Ibu sangat kami harapkan. Kerja sama orang tua/wali sangat penting agar
  putra/putri mendapatkan bimbingan yang tepat dan dapat kembali menyesuaikan diri dengan
  tata tertib serta budaya positif sekolah.
</p>
<p style="text-align:justify;">
  Apabila pada waktu tersebut Bapak/Ibu berhalangan hadir, mohon menghubungi pihak sekolah
  untuk mengatur jadwal pengganti.
</p>
<p style="text-align:justify;">
  Demikian surat panggilan ini kami sampaikan. Atas perhatian dan kerja sama Bapak/Ibu, kami ucapkan terima kasih.
</p>
HTML),
    ],
    [
        'kode' => 'UNDG',
        'nama' => 'Surat Undangan Orang Tua / Wali',
        'letter_type_code' => '02',
        'isi_html' => iss_tpl_wrap('Surat Undangan', 'Undangan Orang Tua / Wali Murid', <<<'HTML'
HTML
. iss_tpl_kepada() . <<<'HTML'

<p style="text-align:justify;">
  Dengan hormat,
</p>
<p style="text-align:justify;">
  Sehubungan dengan kegiatan dan kepentingan pendidikan di {{nama_institusi}}, dengan ini kami
  mengundang Bapak/Ibu untuk hadir pada pertemuan dengan rincian sebagai berikut:
</p>
<table style="width:100%; border:none; border-collapse:collapse; margin:12px 0 16px;">
  <tr><td style="border:none; width:160px; padding:3px 0;">Hari / Tanggal</td><td style="border:none; padding:3px 0;">: {{tanggal_lengkap}}</td></tr>
  <tr><td style="border:none; padding:3px 0;">Waktu</td><td style="border:none; padding:3px 0;">: ........ WIB</td></tr>
  <tr><td style="border:none; padding:3px 0;">Tempat</td><td style="border:none; padding:3px 0;">: {{nama_institusi}}</td></tr>
  <tr><td style="border:none; padding:3px 0;">Agenda / Keperluan</td><td style="border:none; padding:3px 0;">: ................................</td></tr>
  <tr><td style="border:none; padding:3px 0;">Pakaian</td><td style="border:none; padding:3px 0;">: ................................</td></tr>
</table>
<p style="text-align:justify;">
  Kehadiran Bapak/Ibu sangat kami harapkan guna membahas perkembangan akademik, kedisiplinan,
  dan hal-hal strategis terkait putra/putri Bapak/Ibu di sekolah.
</p>
<p style="text-align:justify;">
  Demikian undangan ini kami sampaikan. Atas perhatian dan kehadiran Bapak/Ibu, kami ucapkan terima kasih.
</p>
HTML),
    ],
    [
        'kode' => 'SUND',
        'nama' => 'Surat Undangan Rapat / Kegiatan',
        'letter_type_code' => '02',
        'isi_html' => iss_tpl_wrap('Surat Undangan', 'Undangan Rapat / Kegiatan', <<<'HTML'
<table style="width:100%; border:none; border-collapse:collapse; margin:0 0 16px;">
  <tr>
    <td style="border:none; width:80px; vertical-align:top;">Kepada</td>
    <td style="border:none; vertical-align:top;">Yth.<br>................................<br>di tempat</td>
  </tr>
</table>
<p style="text-align:justify;">
  Dengan hormat,
</p>
<p style="text-align:justify;">
  Sehubungan dengan pelaksanaan kegiatan di {{nama_institusi}}, kami mengundang Bapak/Ibu/Saudara
  untuk hadir pada:
</p>
<table style="width:100%; border:none; border-collapse:collapse; margin:12px 0 16px;">
  <tr><td style="border:none; width:160px; padding:3px 0;">Hari / Tanggal</td><td style="border:none; padding:3px 0;">: {{tanggal_lengkap}}</td></tr>
  <tr><td style="border:none; padding:3px 0;">Waktu</td><td style="border:none; padding:3px 0;">: ........ WIB</td></tr>
  <tr><td style="border:none; padding:3px 0;">Tempat</td><td style="border:none; padding:3px 0;">: {{nama_institusi}}</td></tr>
  <tr><td style="border:none; padding:3px 0;">Acara / Agenda</td><td style="border:none; padding:3px 0;">: ................................</td></tr>
  <tr><td style="border:none; padding:3px 0;">Catatan</td><td style="border:none; padding:3px 0;">: ................................</td></tr>
</table>
<p style="text-align:justify;">
  Kehadiran dan partisipasi Bapak/Ibu/Saudara sangat kami harapkan demi kelancaran dan keberhasilan
  kegiatan tersebut.
</p>
<p style="text-align:justify;">
  Demikian undangan ini disampaikan. Atas perhatian dan kerja samanya, kami ucapkan terima kasih.
</p>
HTML),
    ],
    [
        'kode' => 'SPMB',
        'nama' => 'Surat Pemberitahuan',
        'letter_type_code' => '04',
        'isi_html' => iss_tpl_wrap('Surat Pemberitahuan', 'Pemberitahuan', <<<'HTML'
HTML
. iss_tpl_kepada() . <<<'HTML'

<p style="text-align:justify;">
  Dengan hormat,
</p>
<p style="text-align:justify;">
  Bersama surat ini kami sampaikan pemberitahuan resmi dari {{nama_institusi}} terkait peserta didik
  atas nama <strong>{{nama}}</strong>, NIS <strong>{{nis}}</strong>, kelas <strong>{{kelas}}</strong>,
  dengan hal-hal sebagai berikut:
</p>
<p style="text-align:justify; margin:12px 0;">
  ................................................................................................................................
</p>
<p style="text-align:justify;">
  Pemberitahuan ini disampaikan agar Bapak/Ibu mengetahui informasi tersebut dan dapat
  memberikan tanggapan, konfirmasi, atau tindak lanjut yang diperlukan sesuai arahan sekolah.
</p>
<p style="text-align:justify;">
  Demikian pemberitahuan ini kami sampaikan. Atas perhatian dan kerja sama Bapak/Ibu, kami ucapkan terima kasih.
</p>
HTML),
    ],
    [
        'kode' => 'SREK',
        'nama' => 'Surat Rekomendasi',
        'letter_type_code' => '10',
        'isi_html' => iss_tpl_wrap('Surat Rekomendasi', null, <<<'HTML'
<p style="text-align:justify;">
  Yang bertanda tangan di bawah ini, Kepala {{nama_institusi}}, dengan ini memberikan rekomendasi kepada:
</p>
{{BIODATA_SINGKAT}}
<p style="text-align:justify;">
  Adalah peserta didik {{nama_institusi}} yang kami kenal memiliki prestasi, potensi, dan perilaku
  yang baik selama menempuh pendidikan di institusi kami.
</p>
<p style="text-align:justify;">
  Berdasarkan penilaian akademik, kedisiplinan, dan partisipasi kegiatan, kami rekomendasikan
  yang bersangkutan untuk:
  .................................
</p>
<p style="text-align:justify;">
  Surat rekomendasi ini diberikan dengan itikad baik dan dapat dipergunakan sebagaimana mestinya
  untuk keperluan administrasi, seleksi, atau kegiatan yang sah.
</p>
<p style="text-align:justify;">
  Demikian surat rekomendasi ini dibuat dengan sebenarnya untuk dapat dipertanggungjawabkan apabila diperlukan.
</p>
HTML),
    ],
    [
        'kode' => 'STGS',
        'nama' => 'Surat Tugas',
        'letter_type_code' => '08',
        'isi_html' => iss_tpl_wrap('Surat Tugas', null, <<<'HTML'
<p style="text-align:justify;">
  Yang bertanda tangan di bawah ini, Kepala {{nama_institusi}}, menugaskan kepada:
</p>
<table style="width:100%; border:none; border-collapse:collapse; margin:12px 0 16px;">
  <tr><td style="border:none; width:160px; padding:3px 0;">Nama</td><td style="border:none; padding:3px 0;">: ................................</td></tr>
  <tr><td style="border:none; padding:3px 0;">NIP / NUPTK</td><td style="border:none; padding:3px 0;">: ................................</td></tr>
  <tr><td style="border:none; padding:3px 0;">Jabatan</td><td style="border:none; padding:3px 0;">: ................................</td></tr>
  <tr><td style="border:none; padding:3px 0;">Unit Kerja</td><td style="border:none; padding:3px 0;">: {{nama_institusi}}</td></tr>
</table>
<p style="text-align:justify;">Untuk melaksanakan tugas sebagai berikut:</p>
<table style="width:100%; border:none; border-collapse:collapse; margin:0 0 16px;">
  <tr><td style="border:none; width:160px; padding:3px 0;">Uraian Tugas</td><td style="border:none; padding:3px 0;">: ................................</td></tr>
  <tr><td style="border:none; padding:3px 0;">Tempat Pelaksanaan</td><td style="border:none; padding:3px 0;">: ................................</td></tr>
  <tr><td style="border:none; padding:3px 0;">Waktu Pelaksanaan</td><td style="border:none; padding:3px 0;">: ................................</td></tr>
  <tr><td style="border:none; padding:3px 0;">Biaya / Akomodasi</td><td style="border:none; padding:3px 0;">: ................................</td></tr>
</table>
<p style="text-align:justify;">
  Pelaksana tugas wajib melaksanakan tugas dengan penuh tanggung jawab, menjaga nama baik institusi,
  dan menyampaikan laporan hasil pelaksanaan kepada pimpinan setelah kegiatan selesai.
</p>
<p style="text-align:justify;">
  Demikian surat tugas ini dibuat untuk dapat dipergunakan sebagaimana mestinya.
</p>
HTML),
    ],
    [
        'kode' => 'SMND',
        'nama' => 'Surat Mandat',
        'letter_type_code' => '07',
        'isi_html' => iss_tpl_wrap('Surat Mandat', null, <<<'HTML'
<p style="text-align:justify;">
  Yang bertanda tangan di bawah ini, Kepala {{nama_institusi}}, memberikan mandat kepada:
</p>
<table style="width:100%; border:none; border-collapse:collapse; margin:12px 0 16px;">
  <tr><td style="border:none; width:160px; padding:3px 0;">Nama</td><td style="border:none; padding:3px 0;">: ................................</td></tr>
  <tr><td style="border:none; padding:3px 0;">NIP / NUPTK</td><td style="border:none; padding:3px 0;">: ................................</td></tr>
  <tr><td style="border:none; padding:3px 0;">Jabatan</td><td style="border:none; padding:3px 0;">: ................................</td></tr>
</table>
<p style="text-align:justify;">
  Untuk mewakili {{nama_institusi}} dalam kegiatan / pertemuan:
  ................................ pada hari/tanggal ................................
  bertempat di .................................
</p>
<p style="text-align:justify;">
  Penerima mandat berwenang mengambil keputusan operasional sepanjang sesuai kebijakan dan
  arahan institusi, serta wajib melaporkan hasil kegiatan kepada pimpinan.
</p>
<p style="text-align:justify;">
  Demikian surat mandat ini dibuat untuk dipergunakan sebagaimana mestinya.
</p>
HTML),
    ],
    [
        'kode' => 'SPGT',
        'nama' => 'Surat Pengantar',
        'letter_type_code' => '15',
        'isi_html' => iss_tpl_wrap('Surat Pengantar', 'Pengantar', <<<'HTML'
<table style="width:100%; border:none; border-collapse:collapse; margin:0 0 16px;">
  <tr>
    <td style="border:none; width:80px; vertical-align:top;">Kepada</td>
    <td style="border:none; vertical-align:top;">Yth.<br>................................<br>di tempat</td>
  </tr>
</table>
<p style="text-align:justify;">
  Dengan hormat,
</p>
<p style="text-align:justify;">
  Bersama surat ini kami sampaikan hal-hal berikut untuk dapat diketahui dan ditindaklanjuti:
</p>
<table style="width:100%; border:none; border-collapse:collapse; margin:12px 0 16px;">
  <tr><td style="border:none; width:160px; padding:3px 0;">Perihal</td><td style="border:none; padding:3px 0;">: ................................</td></tr>
  <tr><td style="border:none; padding:3px 0;">Lampiran</td><td style="border:none; padding:3px 0;">: ................................</td></tr>
  <tr><td style="border:none; padding:3px 0;">Nama Peserta Didik</td><td style="border:none; padding:3px 0;">: {{nama}}</td></tr>
  <tr><td style="border:none; padding:3px 0;">NIS / Kelas</td><td style="border:none; padding:3px 0;">: {{nis}} / {{kelas}}</td></tr>
  <tr><td style="border:none; padding:3px 0;">Keterangan</td><td style="border:none; padding:3px 0;">: ................................</td></tr>
</table>
<p style="text-align:justify;">
  Surat pengantar ini disampaikan sebagai dasar administrasi dan bukti bahwa yang bersangkutan
  terdaftar sebagai peserta didik {{nama_institusi}}.
</p>
<p style="text-align:justify;">
  Demikian surat pengantar ini kami sampaikan. Atas perhatian dan kerja samanya, kami ucapkan terima kasih.
</p>
HTML),
    ],
    [
        'kode' => 'SPJN',
        'nama' => 'Surat Perjanjian / Kesepakatan',
        'letter_type_code' => '14',
        'isi_html' => iss_tpl_wrap('Surat Perjanjian / Kesepakatan', null, <<<'HTML'
<p style="text-align:justify;">
  Pada hari ini, {{tanggal_lengkap}}, telah disepakati perjanjian / kesepakatan antara:
</p>
<p style="margin:12px 0 4px;"><strong>Pihak Pertama</strong></p>
<table style="width:100%; border:none; border-collapse:collapse; margin:0 0 12px;">
  <tr><td style="border:none; width:160px; padding:3px 0;">Nama</td><td style="border:none; padding:3px 0;">: {{kepala_madrasah}}</td></tr>
  <tr><td style="border:none; padding:3px 0;">Jabatan</td><td style="border:none; padding:3px 0;">: Kepala {{nama_institusi}}</td></tr>
  <tr><td style="border:none; padding:3px 0;">Alamat</td><td style="border:none; padding:3px 0;">: {{alamat_institusi}}</td></tr>
</table>
<p style="margin:12px 0 4px;"><strong>Pihak Kedua</strong></p>
<table style="width:100%; border:none; border-collapse:collapse; margin:0 0 16px;">
  <tr><td style="border:none; width:160px; padding:3px 0;">Nama Orang Tua / Wali</td><td style="border:none; padding:3px 0;">: {{ayah}} / {{ibu}}</td></tr>
  <tr><td style="border:none; padding:3px 0;">Anak / Peserta Didik</td><td style="border:none; padding:3px 0;">: {{nama}} ({{kelas}})</td></tr>
  <tr><td style="border:none; padding:3px 0;">Alamat</td><td style="border:none; padding:3px 0;">: {{alamat}}</td></tr>
</table>
<p style="text-align:justify;">
  Kedua belah pihak sepakat mengenai hal-hal berikut:
</p>
<p style="text-align:justify; margin:12px 0;">
  1. ...............................................................................................................................<br>
  2. ...............................................................................................................................<br>
  3. ................................................................................................................................
</p>
<p style="text-align:justify;">
  Perjanjian ini berlaku sejak ditandatangani dan wajib dipatuhi oleh kedua belah pihak.
  Apabila terdapat pelanggaran, pihak yang melanggar bersedia menerima konsekuensi sesuai
  ketentuan institusi dan kesepakatan ini.
</p>
<p style="text-align:justify;">
  Demikian perjanjian / kesepakatan ini dibuat dalam rangkap secukupnya untuk dipergunakan sebagaimana mestinya.
</p>
HTML, 'dual'),
    ],
    [
        'kode' => 'SKPT',
        'nama' => 'Surat Keputusan (Template Singkat)',
        'letter_type_code' => '01',
        'isi_html' => iss_tpl_wrap('Surat Keputusan', null, <<<'HTML'
<p style="text-align:center; margin:16px 0;">
  <strong>TENTANG<br>................................</strong>
</p>
<p style="text-align:justify;">
  Kepala {{nama_institusi}},
</p>
<table style="width:100%; border:none; border-collapse:collapse; margin:12px 0 16px;">
  <tr><td style="border:none; width:160px; padding:3px 0; vertical-align:top;">Menimbang</td><td style="border:none; padding:3px 0;">: a. ................................<br>&nbsp;&nbsp;: b. ................................</td></tr>
  <tr><td style="border:none; padding:3px 0; vertical-align:top;">Mengingat</td><td style="border:none; padding:3px 0;">: 1. ................................<br>&nbsp;&nbsp;: 2. ................................</td></tr>
  <tr><td style="border:none; padding:3px 0; vertical-align:top;">Memperhatikan</td><td style="border:none; padding:3px 0;">: ................................</td></tr>
</table>
<p style="text-align:center; margin:16px 0;"><strong>MEMUTUSKAN</strong></p>
<p style="text-align:justify;"><strong>Menetapkan</strong> : SURAT KEPUTUSAN KEPALA {{nama_institusi}} TENTANG ................................</p>
<p style="text-align:justify;"><strong>KESATU</strong>&nbsp;&nbsp;&nbsp;&nbsp;: ................................</p>
<p style="text-align:justify;"><strong>KEDUA</strong>&nbsp;&nbsp;&nbsp;&nbsp;: ................................</p>
<p style="text-align:justify;"><strong>KETIGA</strong>&nbsp;&nbsp;&nbsp;: ................................</p>
<p style="text-align:justify;"><strong>KEEMPAT</strong>&nbsp;&nbsp;: Keputusan ini berlaku sejak tanggal ditetapkan dengan ketentuan apabila di kemudian hari terdapat kekeliruan akan diperbaiki sebagaimana mestinya.</p>
<p style="text-align:justify; margin-top:16px;">
  Ditetapkan di : {{kota}}<br>
  Pada tanggal : {{tanggal_lengkap}}
</p>
HTML, 'sk'),
    ],
    [
        'kode' => 'SKAG',
        'nama' => 'Surat Keterangan Aktif Guru / Pegawai',
        'letter_type_code' => '09',
        'subject_type' => 'pegawai',
        'isi_html' => iss_tpl_wrap('Surat Keterangan Aktif', null, <<<'HTML'
<p style="text-align:justify;">
  Yang bertanda tangan di bawah ini, Kepala {{nama_institusi}}, menerangkan bahwa:
</p>
{{BIODATA_PEGAWAI}}
<p style="text-align:justify;">
  Adalah benar-benar tenaga pendidik / kependidikan yang masih aktif bertugas di {{nama_institusi}}
  dan tidak sedang menjalani cuti di luar tanggungan institusi atau status nonaktif lainnya.
</p>
<p style="text-align:justify;">
  Surat keterangan ini diberikan untuk keperluan administrasi kepegawaian, pengajuan pinjaman,
  verifikasi data, atau keperluan resmi lain yang sah.
</p>
<p style="text-align:justify;">
  Demikian surat keterangan ini dibuat dengan sebenarnya berdasarkan data kepegawaian
  yang tercatat pada institusi kami.
</p>
HTML),
    ],
    [
        'kode' => 'SIZN',
        'nama' => 'Surat Izin Penelitian / Magang / Observasi',
        'letter_type_code' => '16',
        'isi_html' => iss_tpl_wrap('Surat Izin', 'Izin Penelitian / Magang / Observasi', <<<'HTML'
<table style="width:100%; border:none; border-collapse:collapse; margin:0 0 16px;">
  <tr>
    <td style="border:none; width:80px; vertical-align:top;">Kepada</td>
    <td style="border:none; vertical-align:top;">Yth.<br>................................<br>di tempat</td>
  </tr>
</table>
<p style="text-align:justify;">
  Dengan hormat,
</p>
<p style="text-align:justify;">
  Menindaklanjuti permohonan yang diajukan, dengan ini Kepala {{nama_institusi}} memberikan izin kepada:
</p>
<table style="width:100%; border:none; border-collapse:collapse; margin:12px 0 16px;">
  <tr><td style="border:none; width:160px; padding:3px 0;">Nama</td><td style="border:none; padding:3px 0;">: {{nama}}</td></tr>
  <tr><td style="border:none; padding:3px 0;">NIS / NISN</td><td style="border:none; padding:3px 0;">: {{nis}} / {{nisn}}</td></tr>
  <tr><td style="border:none; padding:3px 0;">Kelas</td><td style="border:none; padding:3px 0;">: {{kelas}}</td></tr>
  <tr><td style="border:none; padding:3px 0;">Keperluan</td><td style="border:none; padding:3px 0;">: Penelitian / Magang / Observasi</td></tr>
  <tr><td style="border:none; padding:3px 0;">Judul / Tema</td><td style="border:none; padding:3px 0;">: ................................</td></tr>
  <tr><td style="border:none; padding:3px 0;">Waktu Pelaksanaan</td><td style="border:none; padding:3px 0;">: ................................</td></tr>
  <tr><td style="border:none; padding:3px 0;">Tempat / Lokasi</td><td style="border:none; padding:3px 0;">: ................................</td></tr>
  <tr><td style="border:none; padding:3px 0;">Pendamping</td><td style="border:none; padding:3px 0;">: ................................</td></tr>
</table>
<p style="text-align:justify;">
  Pelaksana kegiatan wajib mematuhi tata tertib institusi, menjaga kerahasiaan data,
  dan tidak mengganggu proses pembelajaran. Segala risiko dan tanggung jawab teknis
  kegiatan menjadi tanggung jawab pelaksana dan pihak yang mengajukan permohonan.
</p>
<p style="text-align:justify;">
  Demikian surat izin ini dibuat untuk dipergunakan sebagaimana mestinya.
  Atas perhatian dan kerja samanya, kami ucapkan terima kasih.
</p>
HTML),
    ],
];
