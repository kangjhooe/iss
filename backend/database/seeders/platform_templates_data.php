<?php

/**
 * Shared platform template definitions (used by TemplateSuratSeeder + CLI seed).
 */

if (!function_exists('iss_tpl_biodata_lengkap')) {
    function iss_tpl_biodata_lengkap(): string
    {
        return <<<'HTML'
<table style="width:100%; border:none; margin:16px 0;">
  <tr><td style="border:none; width:140px;">Nama</td><td style="border:none;">: {{nama}}</td></tr>
  <tr><td style="border:none;">NIS</td><td style="border:none;">: {{nis}}</td></tr>
  <tr><td style="border:none;">NISN</td><td style="border:none;">: {{nisn}}</td></tr>
  <tr><td style="border:none;">Kelas</td><td style="border:none;">: {{kelas}}</td></tr>
  <tr><td style="border:none;">Tempat, Tgl Lahir</td><td style="border:none;">: {{ttl}}</td></tr>
  <tr><td style="border:none;">Agama</td><td style="border:none;">: {{agama}}</td></tr>
  <tr><td style="border:none;">Alamat</td><td style="border:none;">: {{alamat}}</td></tr>
  <tr><td style="border:none;">Nama Ayah</td><td style="border:none;">: {{ayah}}</td></tr>
  <tr><td style="border:none;">Nama Ibu</td><td style="border:none;">: {{ibu}}</td></tr>
</table>
HTML;
    }
}

if (!function_exists('iss_tpl_biodata_singkat')) {
    function iss_tpl_biodata_singkat(): string
    {
        return <<<'HTML'
<table style="width:100%; border:none; margin:16px 0;">
  <tr><td style="border:none; width:140px;">Nama</td><td style="border:none;">: {{nama}}</td></tr>
  <tr><td style="border:none;">NIS / NISN</td><td style="border:none;">: {{nis}} / {{nisn}}</td></tr>
  <tr><td style="border:none;">Kelas</td><td style="border:none;">: {{kelas}}</td></tr>
  <tr><td style="border:none;">Tempat, Tgl Lahir</td><td style="border:none;">: {{ttl}}</td></tr>
  <tr><td style="border:none;">Alamat</td><td style="border:none;">: {{alamat}}</td></tr>
</table>
HTML;
    }
}

if (!function_exists('iss_tpl_wrap')) {
    function iss_tpl_wrap(string $title, ?string $perihal, string $body): string
    {
        $perihalHtml = $perihal
            ? '<p>Perihal : ' . htmlspecialchars($perihal, ENT_QUOTES, 'UTF-8') . '</p>'
            : '';

        $body = str_replace(
            ['{{BIODATA_LENGKAP}}', '{{BIODATA_SINGKAT}}'],
            [iss_tpl_biodata_lengkap(), iss_tpl_biodata_singkat()],
            $body
        );

        return <<<HTML
<div style="text-align:center; margin-bottom:24px;">
  <h2 style="margin:0; text-transform:uppercase;">{$title}</h2>
</div>
<p>Nomor : <strong>{{nomor_surat}}</strong></p>
{$perihalHtml}
{$body}
<p style="text-align:right; margin-top:48px;">
  {{kota}}, {{tanggal_lengkap}}<br><br>
  Kepala {{nama_institusi}},<br><br><br><br>
  <strong>{{kepala_madrasah}}</strong><br>
  NIP. {{nip}}
</p>
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
  Yang bertanda tangan di bawah ini, Kepala {{nama_institusi}}, menerangkan bahwa:
</p>
{{BIODATA_LENGKAP}}
<p style="text-align:justify;">
  Adalah benar siswa aktif belajar di {{nama_institusi}} pada tahun ajaran berjalan.
  Surat keterangan ini dibuat untuk dipergunakan sebagaimana mestinya.
</p>
HTML),
    ],
    [
        'kode' => 'SKBK',
        'nama' => 'Surat Keterangan Berkelakuan Baik',
        'letter_type_code' => '09',
        'isi_html' => iss_tpl_wrap('Surat Keterangan Berkelakuan Baik', null, <<<'HTML'
<p style="text-align:justify;">
  Yang bertanda tangan di bawah ini, Kepala {{nama_institusi}}, menerangkan dengan sesungguhnya bahwa:
</p>
{{BIODATA_SINGKAT}}
<p style="text-align:justify;">
  Adalah benar siswa/siswi {{nama_institusi}} yang selama menjadi peserta didik
  berkelakuan baik dan tidak pernah terlibat pelanggaran tata tertib sekolah yang berat.
  Surat keterangan ini dibuat untuk dipergunakan sebagaimana mestinya.
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
<table style="width:100%; border:none; margin:0 0 16px;">
  <tr><td style="border:none; width:140px;">Asal Sekolah</td><td style="border:none;">: ................................</td></tr>
  <tr><td style="border:none;">Diterima di Kelas</td><td style="border:none;">: {{kelas}}</td></tr>
  <tr><td style="border:none;">Mulai Tanggal</td><td style="border:none;">: {{tanggal_lengkap}}</td></tr>
</table>
<p style="text-align:justify;">
  Adalah benar telah diterima sebagai peserta didik pindahan di {{nama_institusi}}.
  Surat keterangan ini dibuat untuk dipergunakan sebagaimana mestinya.
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
<table style="width:100%; border:none; margin:0 0 16px;">
  <tr><td style="border:none; width:140px;">Kelas Terakhir</td><td style="border:none;">: {{kelas}}</td></tr>
  <tr><td style="border:none;">Alasan Pindah</td><td style="border:none;">: ................................</td></tr>
  <tr><td style="border:none;">Tujuan Sekolah</td><td style="border:none;">: ................................</td></tr>
</table>
<p style="text-align:justify;">
  Adalah benar peserta didik {{nama_institusi}} yang mengajukan pindah/keluar sekolah
  dan telah menyelesaikan kewajiban administrasi sesuai ketentuan yang berlaku.
  Surat keterangan ini dibuat untuk dipergunakan sebagaimana mestinya.
</p>
HTML),
    ],
    [
        'kode' => 'SKLL',
        'nama' => 'Surat Keterangan Lulus / Selesai Belajar',
        'letter_type_code' => '09',
        'isi_html' => iss_tpl_wrap('Surat Keterangan Lulus', null, <<<'HTML'
<p style="text-align:justify;">
  Yang bertanda tangan di bawah ini, Kepala {{nama_institusi}}, menerangkan bahwa:
</p>
{{BIODATA_SINGKAT}}
<p style="text-align:justify;">
  Adalah benar telah menyelesaikan pendidikan / dinyatakan lulus dari {{nama_institusi}}
  pada tahun .......................... Surat keterangan ini dibuat untuk dipergunakan sebagaimana mestinya.
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
  Berdasarkan data yang ada pada kami, orang tua/wali peserta didik tersebut
  termasuk keluarga yang kurang mampu. Surat keterangan ini dibuat untuk keperluan
  ................................ dan dipergunakan sebagaimana mestinya.
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
  Adalah benar berdomisili / bertempat tinggal di alamat tersebut di atas
  bersama orang tua/wali. Surat keterangan ini dibuat untuk dipergunakan sebagaimana mestinya.
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
<table style="width:100%; border:none; margin:0 0 16px;">
  <tr><td style="border:none; width:140px;">Keperluan</td><td style="border:none;">: ................................</td></tr>
  <tr><td style="border:none;">Tanggal / Waktu</td><td style="border:none;">: ................................</td></tr>
  <tr><td style="border:none;">Tempat</td><td style="border:none;">: ................................</td></tr>
</table>
<p style="text-align:justify;">
  Demikian surat keterangan izin ini dibuat untuk diketahui dan dipergunakan sebagaimana mestinya.
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
<table style="width:100%; border:none; margin:16px 0;">
  <tr><td style="border:none; width:140px;">Nama Ayah</td><td style="border:none;">: {{ayah}}</td></tr>
  <tr><td style="border:none;">Nama Ibu</td><td style="border:none;">: {{ibu}}</td></tr>
  <tr><td style="border:none;">Alamat</td><td style="border:none;">: {{alamat}}</td></tr>
</table>
<p style="text-align:justify;">Adalah benar orang tua/wali dari peserta didik:</p>
{{BIODATA_SINGKAT}}
<p style="text-align:justify;">
  Surat keterangan ini dibuat untuk dipergunakan sebagaimana mestinya.
</p>
HTML),
    ],
    [
        'kode' => 'SKPR',
        'nama' => 'Surat Peringatan Siswa',
        'letter_type_code' => '09',
        'isi_html' => iss_tpl_wrap('Surat Peringatan', 'Peringatan kepada Orang Tua / Wali Murid', <<<'HTML'
<p>Kepada Yth.<br>Bapak/Ibu Orang Tua / Wali dari<br><strong>{{nama}}</strong> ({{kelas}})<br>di tempat</p>
<p style="text-align:justify;">
  Dengan hormat, bersama ini kami sampaikan bahwa peserta didik atas nama
  <strong>{{nama}}</strong>, NIS <strong>{{nis}}</strong>, kelas <strong>{{kelas}}</strong>
  telah melakukan pelanggaran / memerlukan perhatian terkait:
</p>
<p style="text-align:justify;">................................</p>
<p style="text-align:justify;">
  Kami mengharapkan kerja sama Bapak/Ibu agar putra/putri dapat memperbaiki sikap
  dan mematuhi tata tertib {{nama_institusi}}.
</p>
HTML),
    ],
    [
        'kode' => 'UNDG',
        'nama' => 'Surat Undangan Orang Tua / Wali',
        'letter_type_code' => '02',
        'isi_html' => iss_tpl_wrap('Surat Undangan', 'Undangan Orang Tua / Wali Murid', <<<'HTML'
<p>Kepada Yth.<br>Bapak/Ibu Orang Tua / Wali dari<br><strong>{{nama}}</strong> ({{kelas}})<br>di tempat</p>
<p style="text-align:justify;">
  Dengan hormat, bersama ini kami mengundang Bapak/Ibu untuk hadir pada pertemuan
  yang akan diselenggarakan di {{nama_institusi}} dengan keterangan sebagai berikut:
</p>
<table style="width:100%; border:none; margin:16px 0;">
  <tr><td style="border:none; width:140px;">Hari / Tanggal</td><td style="border:none;">: {{tanggal_lengkap}}</td></tr>
  <tr><td style="border:none;">Tempat</td><td style="border:none;">: {{nama_institusi}}</td></tr>
  <tr><td style="border:none;">Keperluan</td><td style="border:none;">: ................................</td></tr>
</table>
<p style="text-align:justify;">Atas perhatian dan kehadiran Bapak/Ibu, kami ucapkan terima kasih.</p>
HTML),
    ],
    [
        'kode' => 'SUND',
        'nama' => 'Surat Undangan Rapat / Kegiatan',
        'letter_type_code' => '02',
        'isi_html' => iss_tpl_wrap('Surat Undangan', 'Undangan Rapat / Kegiatan', <<<'HTML'
<p>Kepada Yth.<br>................................<br>di tempat</p>
<p style="text-align:justify;">
  Dengan hormat, mengharapkan kehadiran Bapak/Ibu/Saudara pada:
</p>
<table style="width:100%; border:none; margin:16px 0;">
  <tr><td style="border:none; width:140px;">Hari / Tanggal</td><td style="border:none;">: {{tanggal_lengkap}}</td></tr>
  <tr><td style="border:none;">Waktu</td><td style="border:none;">: ........ WIB</td></tr>
  <tr><td style="border:none;">Tempat</td><td style="border:none;">: {{nama_institusi}}</td></tr>
  <tr><td style="border:none;">Acara</td><td style="border:none;">: ................................</td></tr>
</table>
<p style="text-align:justify;">
  Demikian undangan ini disampaikan. Atas kehadiran dan kerja samanya, kami ucapkan terima kasih.
</p>
HTML),
    ],
    [
        'kode' => 'SPMB',
        'nama' => 'Surat Pemberitahuan',
        'letter_type_code' => '04',
        'isi_html' => iss_tpl_wrap('Surat Pemberitahuan', 'Pemberitahuan', <<<'HTML'
<p>Kepada Yth.<br>Bapak/Ibu Orang Tua / Wali dari<br><strong>{{nama}}</strong> ({{kelas}})<br>di tempat</p>
<p style="text-align:justify;">
  Dengan hormat, bersama ini kami sampaikan pemberitahuan terkait siswa/siswi
  atas nama <strong>{{nama}}</strong>, NIS <strong>{{nis}}</strong>, kelas <strong>{{kelas}}</strong>
  di {{nama_institusi}}.
</p>
<p style="text-align:justify;">Isi pemberitahuan: ................................</p>
<p style="text-align:justify;">
  Demikian pemberitahuan ini disampaikan untuk diketahui dan ditindaklanjuti sebagaimana mestinya.
</p>
HTML),
    ],
    [
        'kode' => 'SREK',
        'nama' => 'Surat Rekomendasi',
        'letter_type_code' => '10',
        'isi_html' => iss_tpl_wrap('Surat Rekomendasi', null, <<<'HTML'
<p style="text-align:justify;">
  Yang bertanda tangan di bawah ini, Kepala {{nama_institusi}}, memberikan rekomendasi kepada:
</p>
{{BIODATA_SINGKAT}}
<p style="text-align:justify;">
  Sebagai peserta didik {{nama_institusi}} yang kami nilai layak untuk
  ................................ Demikian surat rekomendasi ini dibuat dengan sebenarnya
  untuk dipergunakan sebagaimana mestinya.
</p>
HTML),
    ],
    [
        'kode' => 'STGS',
        'nama' => 'Surat Tugas',
        'letter_type_code' => '08',
        'isi_html' => iss_tpl_wrap('Surat Tugas', null, <<<'HTML'
<p style="text-align:justify;">
  Yang bertanda tangan di bawah ini, Kepala {{nama_institusi}}, menugaskan:
</p>
<table style="width:100%; border:none; margin:16px 0;">
  <tr><td style="border:none; width:140px;">Nama</td><td style="border:none;">: ................................</td></tr>
  <tr><td style="border:none;">NIP / NUPTK</td><td style="border:none;">: ................................</td></tr>
  <tr><td style="border:none;">Jabatan</td><td style="border:none;">: ................................</td></tr>
</table>
<table style="width:100%; border:none; margin:0 0 16px;">
  <tr><td style="border:none; width:140px;">Tugas</td><td style="border:none;">: ................................</td></tr>
  <tr><td style="border:none;">Tempat</td><td style="border:none;">: ................................</td></tr>
  <tr><td style="border:none;">Waktu</td><td style="border:none;">: ................................</td></tr>
</table>
<p style="text-align:justify;">
  Demikian surat tugas ini dibuat untuk dilaksanakan dengan penuh tanggung jawab.
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
<table style="width:100%; border:none; margin:16px 0;">
  <tr><td style="border:none; width:140px;">Nama</td><td style="border:none;">: ................................</td></tr>
  <tr><td style="border:none;">NIP / NUPTK</td><td style="border:none;">: ................................</td></tr>
  <tr><td style="border:none;">Jabatan</td><td style="border:none;">: ................................</td></tr>
</table>
<p style="text-align:justify;">
  Untuk mewakili {{nama_institusi}} dalam keperluan:
  ................................ pada tanggal ................................
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
<p>Kepada Yth.<br>................................<br>di tempat</p>
<p style="text-align:justify;">
  Dengan hormat, bersama ini kami sampaikan:
</p>
<table style="width:100%; border:none; margin:16px 0;">
  <tr><td style="border:none; width:140px;">Perihal</td><td style="border:none;">: ................................</td></tr>
  <tr><td style="border:none;">Lampiran</td><td style="border:none;">: ................................</td></tr>
  <tr><td style="border:none;">Terkait</td><td style="border:none;">: {{nama}} ({{kelas}}) — NIS {{nis}}</td></tr>
</table>
<p style="text-align:justify;">
  Demikian surat pengantar ini kami sampaikan. Atas perhatiannya diucapkan terima kasih.
</p>
HTML),
    ],
    [
        'kode' => 'SPJN',
        'nama' => 'Surat Perjanjian / Kesepakatan',
        'letter_type_code' => '14',
        'isi_html' => iss_tpl_wrap('Surat Perjanjian / Kesepakatan', null, <<<'HTML'
<p style="text-align:justify;">
  Pada hari ini, tanggal {{tanggal_lengkap}}, yang bertanda tangan di bawah ini:
</p>
<p><strong>Pihak Pertama</strong></p>
<table style="width:100%; border:none; margin:8px 0 16px;">
  <tr><td style="border:none; width:140px;">Nama</td><td style="border:none;">: {{kepala_madrasah}}</td></tr>
  <tr><td style="border:none;">Jabatan</td><td style="border:none;">: Kepala {{nama_institusi}}</td></tr>
</table>
<p><strong>Pihak Kedua</strong></p>
<table style="width:100%; border:none; margin:8px 0 16px;">
  <tr><td style="border:none; width:140px;">Nama Orang Tua</td><td style="border:none;">: {{ayah}} / {{ibu}}</td></tr>
  <tr><td style="border:none;">Orang Tua dari</td><td style="border:none;">: {{nama}} ({{kelas}})</td></tr>
  <tr><td style="border:none;">Alamat</td><td style="border:none;">: {{alamat}}</td></tr>
</table>
<p style="text-align:justify;">
  Kedua belah pihak sepakat mengenai: ................................
</p>
<p style="text-align:justify;">
  Demikian perjanjian/kesepakatan ini dibuat untuk ditaati bersama.
</p>
HTML),
    ],
    [
        'kode' => 'SKPT',
        'nama' => 'Surat Keputusan (Template Singkat)',
        'letter_type_code' => '01',
        'isi_html' => iss_tpl_wrap('Surat Keputusan', null, <<<'HTML'
<p style="text-align:center;"><strong>TENTANG<br>................................</strong></p>
<p style="text-align:justify;">
  Kepala {{nama_institusi}}, dengan mempertimbangkan ketentuan yang berlaku, memutuskan:
</p>
<p style="text-align:justify;"><strong>KESATU</strong> : ................................</p>
<p style="text-align:justify;"><strong>KEDUA</strong> : ................................</p>
<p style="text-align:justify;"><strong>KETIGA</strong> : Keputusan ini berlaku sejak tanggal ditetapkan.</p>
<p style="text-align:justify;">
  Ditetapkan di : {{kota}}<br>
  Pada tanggal : {{tanggal_lengkap}}
</p>
HTML),
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
<table style="width:100%; border:none; margin:16px 0;">
  <tr><td style="border:none; width:140px;">Nama</td><td style="border:none;">: {{nama}}</td></tr>
  <tr><td style="border:none;">NIP / NUPTK</td><td style="border:none;">: {{nip_pegawai}} / {{nuptk}}</td></tr>
  <tr><td style="border:none;">Jabatan</td><td style="border:none;">: {{jabatan}}</td></tr>
  <tr><td style="border:none;">Status</td><td style="border:none;">: {{status_kepegawaian}}</td></tr>
  <tr><td style="border:none;">Unit Kerja</td><td style="border:none;">: {{nama_institusi}}</td></tr>
</table>
<p style="text-align:justify;">
  Adalah benar tenaga pendidik/kependidikan yang masih aktif bertugas di {{nama_institusi}}.
  Surat keterangan ini dibuat untuk dipergunakan sebagaimana mestinya.
</p>
HTML),
    ],
    [
        'kode' => 'SIZN',
        'nama' => 'Surat Izin Penelitian / Magang / Observasi',
        'letter_type_code' => '16',
        'isi_html' => iss_tpl_wrap('Surat Izin', 'Izin Penelitian / Magang / Observasi', <<<'HTML'
<p>Kepada Yth.<br>................................<br>di tempat</p>
<p style="text-align:justify;">
  Dengan hormat, menindaklanjuti permohonan yang diajukan, bersama ini Kepala {{nama_institusi}}
  memberikan izin kepada:
</p>
<table style="width:100%; border:none; margin:16px 0;">
  <tr><td style="border:none; width:140px;">Nama</td><td style="border:none;">: {{nama}}</td></tr>
  <tr><td style="border:none;">NIS / NISN</td><td style="border:none;">: {{nis}} / {{nisn}}</td></tr>
  <tr><td style="border:none;">Kelas</td><td style="border:none;">: {{kelas}}</td></tr>
  <tr><td style="border:none;">Keperluan</td><td style="border:none;">: Penelitian / Magang / Observasi</td></tr>
  <tr><td style="border:none;">Waktu</td><td style="border:none;">: ................................</td></tr>
  <tr><td style="border:none;">Tempat</td><td style="border:none;">: ................................</td></tr>
</table>
<p style="text-align:justify;">
  Demikian surat izin ini dibuat untuk dipergunakan sebagaimana mestinya.
  Atas perhatian dan kerja samanya diucapkan terima kasih.
</p>
HTML),
    ],
];
