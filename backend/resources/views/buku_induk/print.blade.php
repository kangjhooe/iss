<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buku Induk - {{ $student->name }}</title>
    <style>
        @page { margin: 1.5cm; }
        body { font-family: 'Times New Roman', serif; font-size: 11pt; line-height: 1.4; color: #000; }
        .header { text-align: center; margin-bottom: 20px; }
        .header img { max-width: 70px; height: auto; }
        .header h1 { font-size: 14pt; font-weight: bold; margin: 8px 0 4px 0; text-transform: uppercase; }
        .header .foundation { font-size: 11pt; font-weight: 600; margin: 4px 0 0 0; text-transform: uppercase; }
        .header p { font-size: 10pt; margin: 1px 0; }
        .header .address { font-size: 9pt; margin-top: 4px; }
        .divider { border-top: 2px solid #000; margin: 12px 0; }
        .section-title { font-weight: bold; font-size: 12pt; margin: 14px 0 8px 0; text-decoration: underline; }
        table.data { width: calc(100% - 2px); max-width: calc(100% - 2px); border-collapse: collapse; margin-bottom: 12px; }
        table.data td { padding: 4px 8px; vertical-align: top; }
        table.data td.label { width: 140px; font-weight: bold; }
        table.data tr.border td { border-bottom: 1px solid #ddd; }
        table.list { width: calc(100% - 2px); max-width: calc(100% - 2px); border-collapse: collapse; font-size: 10pt; margin-bottom: 10px; }
        table.list th, table.list td { border: 1px solid #333; padding: 5px 6px; text-align: left; }
        table.list th { background: #f0f0f0; font-weight: bold; }
        .footer { margin-top: 20px; font-size: 9pt; text-align: center; color: #666; }
        .no-data { color: #666; font-style: italic; }
        @include('partials.print-letterhead-styles')
        @include('partials.print-signature-styles')
    </style>
</head>
<body>
    @include('partials.print-letterhead', ['institution' => $institution])
    <div class="section-title">BUKU INDUK SISWA</div>

    <!-- Identitas Siswa -->
    <div class="section-title" style="font-size: 11pt;">A. Identitas Siswa</div>
    <table class="data">
        <tr class="border"><td class="label">NIS</td><td>{{ $student->nis ?? '-' }}</td></tr>
        <tr class="border"><td class="label">NISN</td><td>{{ $student->nisn ?? '-' }}</td></tr>
        <tr class="border"><td class="label">NIK</td><td>{{ $student->nik ?? '-' }}</td></tr>
        <tr class="border"><td class="label">Nama Lengkap</td><td>{{ $student->name ?? '-' }}</td></tr>
        <tr class="border"><td class="label">Jenis Kelamin</td><td>{{ $student->gender ?? '-' }}</td></tr>
        <tr class="border"><td class="label">Tempat, Tanggal Lahir</td><td>{{ $student->birth_place ?? '-' }}, {{ $student->birth_date ? \Carbon\Carbon::parse($student->birth_date)->locale('id')->isoFormat('D MMMM YYYY') : '-' }}</td></tr>
        <tr class="border"><td class="label">Agama</td><td>{{ $student->religion ?? '-' }}</td></tr>
        <tr class="border"><td class="label">No. KK</td><td>{{ $student->no_kk ?? '-' }}</td></tr>
        <tr class="border"><td class="label">Alamat</td><td>{{ $student->address ?? '-' }}</td></tr>
        <tr class="border"><td class="label">Telepon</td><td>{{ $student->phone ?? '-' }}</td></tr>
        <tr class="border"><td class="label">Email</td><td>{{ $student->email ?? '-' }}</td></tr>
        <tr class="border"><td class="label">Tinggi / Berat</td><td>{{ $student->height ?? '-' }} cm / {{ $student->weight ?? '-' }} kg</td></tr>
        <tr class="border"><td class="label">Sekolah Asal</td><td>{{ $student->previous_school ?? '-' }}</td></tr>
        <tr class="border"><td class="label">Kelas / Tahun Ajaran</td><td>{{ ($student->relationLoaded('class') && $student->class ? $student->class->name : ($student->getRawOriginal('class') ?? '-')) }} / {{ $student->academic_year ?? ($student->academicYear->name ?? '-') }}</td></tr>
        <tr class="border"><td class="label">Status</td><td>{{ $student->status ?? '-' }}</td></tr>
        @if($student->graduation_year)
        <tr class="border"><td class="label">Tahun Lulus</td><td>{{ $student->graduation_year }}</td></tr>
        @endif
        @if($student->disability)
        <tr class="border"><td class="label">Kebutuhan Khusus</td><td>{{ $student->disability }}</td></tr>
        @endif
        @if($student->aspiration)
        <tr class="border"><td class="label">Cita-cita</td><td>{{ $student->aspiration }}</td></tr>
        @endif
        @if($student->hobby)
        <tr class="border"><td class="label">Hobi</td><td>{{ $student->hobby }}</td></tr>
        @endif
        @if($student->residence_type)
        <tr class="border"><td class="label">Jenis Tempat Tinggal</td><td>{{ $student->residence_type }}</td></tr>
        @endif
    </table>

    <!-- Data Orang Tua / Wali -->
    <div class="section-title" style="font-size: 11pt;">B. Data Orang Tua / Wali</div>
    <table class="data">
        <tr class="border"><td class="label">Nama Ayah</td><td>{{ $student->father_name ?? '-' }}</td></tr>
        <tr class="border"><td class="label">NIK Ayah</td><td>{{ $student->father_nik ?? '-' }}</td></tr>
        <tr class="border"><td class="label">Pendidikan / Pekerjaan Ayah</td><td>{{ $student->father_education ?? '-' }} / {{ $student->father_occupation ?? '-' }}</td></tr>
        <tr class="border"><td class="label">Nama Ibu</td><td>{{ $student->mother_name ?? '-' }}</td></tr>
        <tr class="border"><td class="label">NIK Ibu</td><td>{{ $student->mother_nik ?? '-' }}</td></tr>
        <tr class="border"><td class="label">Pendidikan / Pekerjaan Ibu</td><td>{{ $student->mother_education ?? '-' }} / {{ $student->mother_occupation ?? '-' }}</td></tr>
        @if($student->guardian_name)
        <tr class="border"><td class="label">Nama Wali</td><td>{{ $student->guardian_name }}</td></tr>
        <tr class="border"><td class="label">Telepon Wali</td><td>{{ $student->guardian_phone ?? '-' }}</td></tr>
        @endif
    </table>

    <!-- Riwayat Kelas -->
    <div class="section-title" style="font-size: 11pt;">C. Riwayat Kelas</div>
    @if($class_history && $class_history->isNotEmpty())
    <table class="list">
        <thead>
            <tr>
                <th>No</th>
                <th>Tahun Ajaran</th>
                <th>Kelas</th>
                <th>Semester</th>
                <th>Tanggal Mulai</th>
                <th>Tanggal Selesai</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach($class_history as $idx => $h)
            <tr>
                <td>{{ $idx + 1 }}</td>
                <td>{{ $h->academic_year ?? ($h->academicYear->name ?? '-') }}</td>
                <td>{{ $h->class->name ?? '-' }}</td>
                <td>{{ $h->semester->name ?? '-' }}</td>
                <td>{{ $h->start_date ? \Carbon\Carbon::parse($h->start_date)->format('d/m/Y') : '-' }}</td>
                <td>{{ $h->end_date ? \Carbon\Carbon::parse($h->end_date)->format('d/m/Y') : '-' }}</td>
                <td>{{ $h->status ?? '-' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @else
    <p class="no-data">Tidak ada riwayat kelas.</p>
    @endif

    <!-- Mutasi -->
    <div class="section-title" style="font-size: 11pt;">D. Riwayat Mutasi</div>
    @if($mutations && $mutations->isNotEmpty())
    <table class="list">
        <thead>
            <tr>
                <th>No</th>
                <th>Asal</th>
                <th>Tujuan</th>
                <th>Status</th>
                <th>Tanggal</th>
            </tr>
        </thead>
        <tbody>
            @foreach($mutations as $idx => $m)
            <tr>
                <td>{{ $idx + 1 }}</td>
                <td>{{ $m->originInstitution?->name ?? $m->origin_school_name ?? '-' }}</td>
                <td>{{ $m->targetInstitution?->name ?? $m->target_school_name ?? '-' }}</td>
                <td>{{ $m->status ?? '-' }}</td>
                <td>{{ $m->created_at ? $m->created_at->format('d/m/Y') : '-' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @else
    <p class="no-data">Tidak ada riwayat mutasi.</p>
    @endif

    <!-- Prestasi -->
    <div class="section-title" style="font-size: 11pt;">E. Prestasi</div>
    @if($achievements && $achievements->isNotEmpty())
    <table class="list">
        <thead>
            <tr>
                <th>No</th>
                <th>Jenis</th>
                <th>Tanggal</th>
                <th>Keterangan</th>
            </tr>
        </thead>
        <tbody>
            @foreach($achievements as $idx => $a)
            <tr>
                <td>{{ $idx + 1 }}</td>
                <td>{{ $a->achievementType->name ?? '-' }}</td>
                <td>{{ $a->achievement_date ? \Carbon\Carbon::parse($a->achievement_date)->format('d/m/Y') : '-' }}</td>
                <td>{{ $a->notes ?? '-' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @else
    <p class="no-data">Tidak ada data prestasi.</p>
    @endif

    <!-- Pelanggaran -->
    <div class="section-title" style="font-size: 11pt;">F. Pelanggaran</div>
    @if($violations && $violations->isNotEmpty())
    <table class="list">
        <thead>
            <tr>
                <th>No</th>
                <th>Jenis</th>
                <th>Tanggal</th>
                <th>Sanksi</th>
                <th>Keterangan</th>
            </tr>
        </thead>
        <tbody>
            @foreach($violations as $idx => $v)
            <tr>
                <td>{{ $idx + 1 }}</td>
                <td>{{ $v->violationType->name ?? '-' }}</td>
                <td>{{ $v->violation_date ? \Carbon\Carbon::parse($v->violation_date)->format('d/m/Y') : '-' }}</td>
                <td>{{ $v->sanction ?? '-' }}</td>
                <td>{{ $v->description ?? '-' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @else
    <p class="no-data">Tidak ada data pelanggaran.</p>
    @endif

    <!-- Bimbingan Konseling -->
    <div class="section-title" style="font-size: 11pt;">G. Bimbingan Konseling</div>
    @if($counseling_sessions && $counseling_sessions->isNotEmpty())
    <table class="list">
        <thead>
            <tr>
                <th>No</th>
                <th>Jenis</th>
                <th>Tanggal</th>
                <th>Hasil / Tindak Lanjut</th>
            </tr>
        </thead>
        <tbody>
            @foreach($counseling_sessions as $idx => $c)
            <tr>
                <td>{{ $idx + 1 }}</td>
                <td>{{ $c->counselingType->name ?? '-' }}</td>
                <td>{{ $c->session_date ? \Carbon\Carbon::parse($c->session_date)->format('d/m/Y') : '-' }}</td>
                <td>{{ $c->follow_up_notes ?? $c->summary ?? '-' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @else
    <p class="no-data">Tidak ada data bimbingan konseling.</p>
    @endif

    <!-- H. Rekap Kehadiran -->
    <div class="section-title" style="font-size: 11pt;">H. Rekap Kehadiran</div>
    @if(isset($attendance_summary) && count($attendance_summary) > 0)
    <table class="list">
        <thead>
            <tr>
                <th>Tahun Ajaran</th>
                <th>Semester</th>
                <th>Hadir</th>
                <th>Sakit</th>
                <th>Izin</th>
                <th>Alpha</th>
                <th>Dinas Luar</th>
            </tr>
        </thead>
        <tbody>
            @foreach($attendance_summary as $a)
            <tr>
                <td>{{ $a['academic_year_name'] ?? '-' }}</td>
                <td>{{ $a['semester_name'] ?? '-' }}</td>
                <td>{{ $a['hadir'] ?? 0 }}</td>
                <td>{{ $a['sakit'] ?? 0 }}</td>
                <td>{{ $a['izin'] ?? 0 }}</td>
                <td>{{ $a['alpha'] ?? 0 }}</td>
                <td>{{ $a['dinas_luar'] ?? 0 }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @else
    <p class="no-data">Tidak ada data rekap kehadiran.</p>
    @endif

    <!-- I. Ringkasan Nilai -->
    <div class="section-title" style="font-size: 11pt;">I. Ringkasan Nilai (Nilai Akhir)</div>
    @if(isset($grades_summary) && count($grades_summary) > 0)
    @foreach($grades_summary as $period)
    <p style="font-weight: bold; margin: 8px 0 4px 0;">{{ $period['academic_year_name'] ?? '-' }} – {{ $period['semester_name'] ?? '-' }}</p>
    <table class="list">
        <thead>
            <tr>
                <th>Mata Pelajaran</th>
                <th>Nilai</th>
            </tr>
        </thead>
        <tbody>
            @foreach($period['subjects'] ?? [] as $s)
            <tr>
                <td>{{ $s['subject_name'] ?? '-' }}</td>
                <td>{{ isset($s['value']) ? $s['value'] : '-' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endforeach
    @else
    <p class="no-data">Tidak ada data ringkasan nilai.</p>
    @endif

    <!-- J. Ekstrakurikuler -->
    <div class="section-title" style="font-size: 11pt;">J. Ekstrakurikuler</div>
    @if(isset($extracurriculars) && $extracurriculars->isNotEmpty())
    <table class="list">
        <thead>
            <tr>
                <th>No</th>
                <th>Nama Ekskul</th>
                <th>Tahun Ajaran</th>
                <th>Semester</th>
                <th>Bergabung</th>
                <th>Keluar</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach($extracurriculars as $idx => $e)
            <tr>
                <td>{{ $idx + 1 }}</td>
                <td>{{ $e->extracurricular->name ?? '-' }}</td>
                <td>{{ $e->academicYear->name ?? '-' }}</td>
                <td>{{ $e->semester->name ?? '-' }}</td>
                <td>{{ $e->joined_at ? $e->joined_at->format('d/m/Y') : '-' }}</td>
                <td>{{ $e->left_at ? $e->left_at->format('d/m/Y') : '-' }}</td>
                <td>{{ $e->status ?? '-' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @else
    <p class="no-data">Tidak ada data ekstrakurikuler.</p>
    @endif

    <!-- K. Tujuan Setelah Lulus -->
    <div class="section-title" style="font-size: 11pt;">K. Tujuan Setelah Lulus</div>
    @if(isset($alumni_destinations) && $alumni_destinations->isNotEmpty())
    @php
        $destLabels = [
            'Sekolah' => 'Lanjut Sekolah (SMA/SMK/dll)',
            'Perguruan_Tinggi' => 'Perguruan Tinggi',
            'Kerja' => 'Bekerja',
            'Wirausaha' => 'Wirausaha',
            'Lainnya' => 'Lainnya',
        ];
    @endphp
    <table class="list">
        <thead>
            <tr>
                <th>No</th>
                <th>Jenis</th>
                <th>Nama / Tempat</th>
                <th>Program / Posisi</th>
                <th>Tahun Masuk</th>
                <th>Keterangan</th>
            </tr>
        </thead>
        <tbody>
            @foreach($alumni_destinations as $idx => $d)
            <tr>
                <td>{{ $idx + 1 }}</td>
                <td>{{ $destLabels[$d->destination_type] ?? $d->destination_type }}</td>
                <td>{{ $d->destination_name ?? '-' }}</td>
                <td>{{ $d->program_or_position ?? '-' }}</td>
                <td>{{ $d->year_entered ?? '-' }}</td>
                <td>{{ $d->notes ?? '-' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @else
    <p class="no-data">Tidak ada data tujuan setelah lulus.</p>
    @endif

    <!-- L. Ringkasan Perpustakaan -->
    <div class="section-title" style="font-size: 11pt;">L. Ringkasan Perpustakaan</div>
    <table class="data">
        <tr class="border"><td class="label">Total Peminjaman</td><td>{{ $library_loans_summary['total_loans'] ?? 0 }}</td></tr>
        <tr class="border"><td class="label">Keterlambatan (riwayat)</td><td>{{ $library_loans_summary['late_count'] ?? 0 }}</td></tr>
        <tr class="border"><td class="label">Sedang Terlambat</td><td>{{ $library_loans_summary['overdue_count'] ?? 0 }}</td></tr>
    </table>

    <!-- M. Riwayat Kesehatan (UKS) -->
    <div class="section-title" style="font-size: 11pt;">M. Riwayat Kesehatan (UKS)</div>
    @if(isset($health_records) && count($health_records) > 0)
    <table class="list">
        <thead>
            <tr>
                <th>No</th>
                <th>Tanggal</th>
                <th>Jenis</th>
                <th>Keterangan</th>
            </tr>
        </thead>
        <tbody>
            @foreach($health_records as $idx => $h)
            <tr>
                <td>{{ $idx + 1 }}</td>
                <td>{{ isset($h['date']) ? \Carbon\Carbon::parse($h['date'])->format('d/m/Y') : '-' }}</td>
                <td>{{ $h['type'] ?? '-' }}</td>
                <td>{{ $h['notes'] ?? '-' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @else
    <p class="no-data">Data akan diisi dari modul UKS ketika tersedia.</p>
    @endif

    <!-- N. Pengambilan Ijazah -->
    @if(isset($document_pickups) && $document_pickups->isNotEmpty())
    <div class="section-title" style="font-size: 11pt;">N. Pengambilan Ijazah</div>
    <table class="list">
        <thead>
            <tr>
                <th>No</th>
                <th>Tanggal</th>
                <th>Dokumen Diambil</th>
                <th>No. Ijazah / Kode Blangko</th>
                <th>Diterima oleh</th>
            </tr>
        </thead>
        <tbody>
            @foreach($document_pickups as $idx => $dp)
            <tr>
                <td>{{ $idx + 1 }}</td>
                <td>{{ $dp->pickup_date ? \Carbon\Carbon::parse($dp->pickup_date)->format('d/m/Y') : '-' }}</td>
                <td>
                    @php
                        $items = [];
                        if ($dp->taken_ijazah) $items[] = 'Ijazah';
                        if ($dp->taken_raport) $items[] = 'Raport';
                        if ($dp->taken_skhun) $items[] = 'SKHUN';
                        if ($dp->dokumen_lainnya) $items[] = $dp->dokumen_lainnya;
                    @endphp
                    {{ implode(', ', $items) ?: '-' }}
                </td>
                <td>{{ $dp->nomor_ijazah ?? $dp->kode_blangko ?? '-' }}</td>
                <td>{{ $dp->received_by ?? '-' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    @if($student->notes)
    <div class="section-title" style="font-size: 11pt;">O. Catatan</div>
    <p>{{ $student->notes }}</p>
    @endif

    <div class="footer">
        <p>Dicetak pada: {{ $printed_at }}</p>
    </div>
    <div class="standard-signature-wrap">
        <div class="standard-signature-left"></div>
        <div class="standard-signature-right">
            @include('partials.print-signature', [
                'institution' => $institution,
                'date' => \Carbon\Carbon::parse($printed_at)->locale('id')->translatedFormat('d F Y'),
            ])
        </div>
    </div>
</body>
</html>
