<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Buku Induk - {{ $student->name }}</title>
    <style>
        @include('partials.student-print-styles')
    </style>
</head>
<body>
@php
    $genderLabel = match ($student->gender ?? null) {
        'L' => 'Laki-laki',
        'P' => 'Perempuan',
        default => $student->gender ?? '-',
    };
    $parentStatus = [
        'masih_hidup' => 'Masih Hidup',
        'meninggal_dunia' => 'Meninggal Dunia',
        'tidak_diketahui' => 'Tidak Diketahui',
    ];
    $residenceLabels = [
        'asrama' => 'Asrama',
        'kost_kontrak' => 'Kost/Kontrak',
        'tinggal_dengan_orang_tua' => 'Tinggal dengan Orang Tua',
        'lainnya' => 'Lainnya',
    ];
    $guardianTypeLabels = [
        'sama_dengan_ayah' => 'Sama dengan Ayah Kandung',
        'sama_dengan_ibu' => 'Sama dengan Ibu Kandung',
        'lainnya' => 'Lainnya',
    ];
    $fmtDate = fn ($v) => $v ? \Carbon\Carbon::parse($v)->locale('id')->isoFormat('D MMMM YYYY') : '-';
    $fmtShort = fn ($v) => $v ? \Carbon\Carbon::parse($v)->format('d/m/Y') : '-';
    $fmtMoney = fn ($v) => $v !== null && $v !== '' ? 'Rp ' . number_format((float) $v, 0, ',', '.') : '-';
    $val = fn ($v) => ($v !== null && $v !== '') ? $v : '-';
    $lookup = fn ($map, $key) => ($key !== null && $key !== '' && isset($map[$key])) ? $map[$key] : null;
    $classRel = $student->relationLoaded('class') ? $student->getRelation('class') : null;
    $classDisplay = (is_object($classRel) && isset($classRel->name))
        ? $classRel->name
        : ($student->getRawOriginal('class') ?? '-');
    $ayRel = $student->relationLoaded('academicYear') ? $student->getRelation('academicYear') : null;
    $academicYearDisplay = $student->academic_year
        ?? ((is_object($ayRel) && isset($ayRel->name)) ? $ayRel->name : '-');
    $semRel = $student->relationLoaded('semester') ? $student->getRelation('semester') : null;
    $semesterDisplay = (is_object($semRel) && isset($semRel->name)) ? $semRel->name : '-';
@endphp

@include('partials.print-letterhead', ['institution' => $institution])

<table class="title-row">
    <tr>
        <td>
            <div class="doc-title" style="text-align:left;">
                <h1>Buku Induk Siswa</h1>
                <p>{{ $val($student->name) }} · Kelas {{ $classDisplay }} · {{ $academicYearDisplay }}</p>
            </div>
        </td>
        <td style="width:2.9cm; text-align:right;">
            <div class="photo-box">
                @if(!empty($photo_base64))
                    <img src="{!! $photo_base64 !!}" alt="Foto">
                @else
                    <div class="photo-empty">Pas foto<br>3 × 4</div>
                @endif
            </div>
        </td>
    </tr>
</table>

<table class="summary-box">
    <tr>
        <td class="lbl">NIS / NISN</td>
        <td>{{ $val($student->nis) }} / {{ $val($student->nisn) }}</td>
        <td class="lbl">Status</td>
        <td>{{ $val($student->status) }}</td>
    </tr>
    <tr>
        <td class="lbl">NIK</td>
        <td>{{ $val($student->nik) }}</td>
        <td class="lbl">Semester</td>
        <td>{{ $semesterDisplay }}</td>
    </tr>
</table>

<div class="section-block">
    <div class="section-title">A. Identitas Siswa</div>
    <table class="data">
        <tr><td class="label">Nama Lengkap</td><td class="sep">:</td><td>{{ $val($student->name) }}</td></tr>
        <tr><td class="label">Jenis Kelamin</td><td class="sep">:</td><td>{{ $genderLabel }}</td></tr>
        <tr><td class="label">Tempat, Tanggal Lahir</td><td class="sep">:</td><td>{{ $val($student->birth_place) }}, {{ $fmtDate($student->birth_date) }}</td></tr>
        <tr><td class="label">Agama</td><td class="sep">:</td><td>{{ $val($student->religion) }}</td></tr>
        <tr><td class="label">No. KK</td><td class="sep">:</td><td>{{ $val($student->no_kk) }}</td></tr>
        <tr><td class="label">Alamat</td><td class="sep">:</td><td>{{ \App\Support\RegionAddress::format($student) ?: '-' }}</td></tr>
        <tr><td class="label">Telepon / Email</td><td class="sep">:</td><td>{{ $val($student->phone) }} / {{ $val($student->email) }}</td></tr>
        <tr><td class="label">Tinggi / Berat</td><td class="sep">:</td><td>{{ $student->height ? $student->height.' cm' : '-' }} / {{ $student->weight ? $student->weight.' kg' : '-' }}</td></tr>
        <tr><td class="label">Sekolah Asal</td><td class="sep">:</td><td>{{ $val($student->previous_school) }}</td></tr>
        <tr><td class="label">NPSN / Alamat Sekolah Asal</td><td class="sep">:</td><td>{{ $val($student->previous_school_npsn) }} / {{ $val($student->previous_school_address) }}</td></tr>
        <tr><td class="label">Tingkat</td><td class="sep">:</td><td>{{ $val($student->tingkat) }}</td></tr>
        @if($student->graduation_year)
        <tr><td class="label">Tahun Lulus</td><td class="sep">:</td><td>{{ $student->graduation_year }}</td></tr>
        @endif
        <tr><td class="label">Kebutuhan Khusus</td><td class="sep">:</td><td>{{ $val($student->disability) }}</td></tr>
        <tr><td class="label">Cita-cita / Hobi</td><td class="sep">:</td><td>{{ $val($student->aspiration) }} / {{ $val($student->hobby) }}</td></tr>
        <tr><td class="label">Jenis Tempat Tinggal</td><td class="sep">:</td><td>{{ $lookup($residenceLabels, $student->residence_type) ?? $val($student->residence_type) }}</td></tr>
    </table>
</div>

<div class="section-block">
    <div class="section-title">B. Data Orang Tua / Wali</div>
    <table class="two-col">
        <tr>
            <td>
                <p class="sub-head">Ayah</p>
                <table class="data">
                    <tr><td class="label">Nama</td><td class="sep">:</td><td>{{ $val($student->father_name) }}</td></tr>
                    <tr><td class="label">Status</td><td class="sep">:</td><td>{{ $lookup($parentStatus, $student->father_status) ?? $val($student->father_status) }}</td></tr>
                    <tr><td class="label">NIK</td><td class="sep">:</td><td>{{ $val($student->father_nik) }}</td></tr>
                    <tr><td class="label">TTL</td><td class="sep">:</td><td>{{ $val($student->father_birth_place) }}, {{ $fmtDate($student->father_birth_date) }}</td></tr>
                    <tr><td class="label">Pendidikan</td><td class="sep">:</td><td>{{ $val($student->father_education) }}</td></tr>
                    <tr><td class="label">Pekerjaan</td><td class="sep">:</td><td>{{ $val($student->father_occupation) }}</td></tr>
                    <tr><td class="label">Penghasilan</td><td class="sep">:</td><td>{{ $fmtMoney($student->father_income) }}</td></tr>
                </table>
            </td>
            <td>
                <p class="sub-head">Ibu</p>
                <table class="data">
                    <tr><td class="label">Nama</td><td class="sep">:</td><td>{{ $val($student->mother_name) }}</td></tr>
                    <tr><td class="label">Status</td><td class="sep">:</td><td>{{ $lookup($parentStatus, $student->mother_status) ?? $val($student->mother_status) }}</td></tr>
                    <tr><td class="label">NIK</td><td class="sep">:</td><td>{{ $val($student->mother_nik) }}</td></tr>
                    <tr><td class="label">TTL</td><td class="sep">:</td><td>{{ $val($student->mother_birth_place) }}, {{ $fmtDate($student->mother_birth_date) }}</td></tr>
                    <tr><td class="label">Pendidikan</td><td class="sep">:</td><td>{{ $val($student->mother_education) }}</td></tr>
                    <tr><td class="label">Pekerjaan</td><td class="sep">:</td><td>{{ $val($student->mother_occupation) }}</td></tr>
                    <tr><td class="label">Penghasilan</td><td class="sep">:</td><td>{{ $fmtMoney($student->mother_income) }}</td></tr>
                </table>
            </td>
        </tr>
    </table>
    <p class="sub-head">Wali</p>
    <table class="data">
        <tr><td class="label">Jenis Wali</td><td class="sep">:</td><td>{{ $lookup($guardianTypeLabels, $student->guardian_type) ?? $val($student->guardian_type) }}</td></tr>
        <tr><td class="label">Nama / Status</td><td class="sep">:</td><td>{{ $val($student->guardian_name) }} / {{ $lookup($parentStatus, $student->guardian_status) ?? $val($student->guardian_status) }}</td></tr>
        <tr><td class="label">NIK / Telepon</td><td class="sep">:</td><td>{{ $val($student->guardian_nik) }} / {{ $val($student->guardian_phone) }}</td></tr>
        <tr><td class="label">TTL</td><td class="sep">:</td><td>{{ $val($student->guardian_birth_place) }}, {{ $fmtDate($student->guardian_birth_date) }}</td></tr>
        <tr><td class="label">Pendidikan / Pekerjaan</td><td class="sep">:</td><td>{{ $val($student->guardian_education) }} / {{ $val($student->guardian_occupation) }}</td></tr>
        <tr><td class="label">Penghasilan</td><td class="sep">:</td><td>{{ $fmtMoney($student->guardian_income) }}</td></tr>
    </table>
</div>

<div class="section-block page-break">
    <div class="section-title">C. Riwayat Kelas</div>
    @if($class_history && $class_history->isNotEmpty())
    <table class="list">
        <thead>
            <tr>
                <th class="num">No</th>
                <th>Tahun Ajaran</th>
                <th>Kelas</th>
                <th>Semester</th>
                <th>Mulai</th>
                <th>Selesai</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach($class_history as $idx => $h)
            <tr>
                <td class="num">{{ $idx + 1 }}</td>
                <td>{{ $h->academic_year ?? ($h->academicYear->name ?? '-') }}</td>
                <td>{{ $h->class->name ?? '-' }}</td>
                <td>{{ $h->semester->name ?? '-' }}</td>
                <td class="center">{{ $fmtShort($h->start_date) }}</td>
                <td class="center">{{ $fmtShort($h->end_date) }}</td>
                <td>{{ $h->status ?? '-' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @else
    <p class="no-data">Tidak ada riwayat kelas.</p>
    @endif
</div>

<div class="section-block">
    <div class="section-title">D. Riwayat Mutasi</div>
    @if($mutations && count($mutations) > 0)
    <table class="list">
        <thead>
            <tr>
                <th class="num">No</th>
                <th>Tanggal</th>
                <th>Asal (NPSN)</th>
                <th>Tujuan (NPSN)</th>
                <th>Status</th>
                <th>Keterangan</th>
            </tr>
        </thead>
        <tbody>
            @foreach($mutations as $idx => $m)
            @php
                $m = is_array($m) ? $m : (array) $m;
                $origin = $m['origin_school_name'] ?? ($m['origin_institution']['name'] ?? '-');
                $target = $m['target_school_name'] ?? ($m['target_institution']['name'] ?? '-');
                $originNpsn = $m['origin_npsn'] ?? ($m['origin_institution']['npsn'] ?? null);
                $targetNpsn = $m['target_npsn'] ?? ($m['target_institution']['npsn'] ?? null);
                $dateRaw = $m['approved_at'] ?? $m['created_at'] ?? null;
                $notesParts = array_filter([
                    $m['notes'] ?? null,
                    !empty($m['rejection_reason']) ? 'Tolak: '.$m['rejection_reason'] : null,
                    !empty($m['cancel_reason']) ? 'Batal: '.$m['cancel_reason'] : null,
                    !empty($m['cancel_rejection_reason']) ? 'Tolak batal: '.$m['cancel_rejection_reason'] : null,
                ]);
            @endphp
            <tr>
                <td class="num">{{ $idx + 1 }}</td>
                <td class="center">{{ $fmtShort($dateRaw) }}</td>
                <td>{{ $origin }}{{ $originNpsn ? ' ('.$originNpsn.')' : '' }}</td>
                <td>{{ $target }}{{ $targetNpsn ? ' ('.$targetNpsn.')' : '' }}</td>
                <td>{{ $m['status_label'] ?? ($m['status'] ?? '-') }}</td>
                <td>{{ $notesParts ? implode(' | ', $notesParts) : '-' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @else
    <p class="no-data">Tidak ada riwayat mutasi.</p>
    @endif
</div>

<div class="section-block">
    <div class="section-title">E. Prestasi</div>
    @if($achievements && $achievements->isNotEmpty())
    <table class="list">
        <thead>
            <tr><th class="num">No</th><th>Jenis</th><th>Tanggal</th><th>Keterangan</th></tr>
        </thead>
        <tbody>
            @foreach($achievements as $idx => $a)
            <tr>
                <td class="num">{{ $idx + 1 }}</td>
                <td>{{ $a->achievementType->name ?? '-' }}</td>
                <td class="center">{{ $fmtShort($a->achievement_date) }}</td>
                <td>{{ $a->notes ?? '-' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @else
    <p class="no-data">Tidak ada data prestasi.</p>
    @endif
</div>

<div class="section-block">
    <div class="section-title">F. Pelanggaran</div>
    @if($violations && $violations->isNotEmpty())
    <table class="list">
        <thead>
            <tr><th class="num">No</th><th>Jenis</th><th>Tanggal</th><th>Sanksi</th><th>Keterangan</th></tr>
        </thead>
        <tbody>
            @foreach($violations as $idx => $v)
            <tr>
                <td class="num">{{ $idx + 1 }}</td>
                <td>{{ $v->violationType->name ?? '-' }}</td>
                <td class="center">{{ $fmtShort($v->violation_date) }}</td>
                <td>{{ $v->sanction ?? '-' }}</td>
                <td>{{ $v->description ?? '-' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @else
    <p class="no-data">Tidak ada data pelanggaran.</p>
    @endif
</div>

<div class="section-block">
    <div class="section-title">G. Bimbingan Konseling</div>
    @if($counseling_sessions && $counseling_sessions->isNotEmpty())
    <table class="list">
        <thead>
            <tr><th class="num">No</th><th>Jenis</th><th>Tanggal</th><th>Hasil / Tindak Lanjut</th></tr>
        </thead>
        <tbody>
            @foreach($counseling_sessions as $idx => $c)
            <tr>
                <td class="num">{{ $idx + 1 }}</td>
                <td>{{ $c->counselingType->name ?? '-' }}</td>
                <td class="center">{{ $fmtShort($c->session_date) }}</td>
                <td>{{ $c->follow_up_notes ?? $c->summary ?? '-' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @else
    <p class="no-data">Tidak ada data bimbingan konseling.</p>
    @endif
</div>

<div class="section-block page-break">
    <div class="section-title">H. Rekap Kehadiran</div>
    @if(isset($attendance_summary) && count($attendance_summary) > 0)
    <table class="list">
        <thead>
            <tr>
                <th>Tahun Ajaran</th><th>Semester</th><th>Hadir</th><th>Sakit</th><th>Izin</th><th>Alpha</th><th>Dinas Luar</th>
            </tr>
        </thead>
        <tbody>
            @foreach($attendance_summary as $a)
            <tr>
                <td>{{ $a['academic_year_name'] ?? '-' }}</td>
                <td>{{ $a['semester_name'] ?? '-' }}</td>
                <td class="center">{{ $a['hadir'] ?? 0 }}</td>
                <td class="center">{{ $a['sakit'] ?? 0 }}</td>
                <td class="center">{{ $a['izin'] ?? 0 }}</td>
                <td class="center">{{ $a['alpha'] ?? 0 }}</td>
                <td class="center">{{ $a['dinas_luar'] ?? 0 }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @else
    <p class="no-data">Tidak ada data rekap kehadiran.</p>
    @endif
</div>

<div class="section-block">
    <div class="section-title">I. Ringkasan Nilai (Nilai Akhir)</div>
    @if(isset($grades_summary) && count($grades_summary) > 0)
    @foreach($grades_summary as $period)
    <p class="sub-head">{{ $period['academic_year_name'] ?? '-' }} — {{ $period['semester_name'] ?? '-' }}</p>
    <table class="list">
        <thead>
            <tr><th>Mata Pelajaran</th><th>Nilai</th><th>KKM</th><th>Predikat</th><th>Ketuntasan</th></tr>
        </thead>
        <tbody>
            @foreach($period['subjects'] ?? [] as $s)
            <tr>
                <td>{{ $s['subject_name'] ?? '-' }}</td>
                <td class="center">{{ isset($s['value']) && $s['value'] !== null ? $s['value'] : '-' }}</td>
                <td class="center">{{ isset($s['kkm']) && $s['kkm'] !== null ? $s['kkm'] : '-' }}</td>
                <td class="center">{{ $s['predicate'] ?? '-' }}</td>
                <td class="center">{{ $s['tuntas_label'] ?? '-' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endforeach
    @else
    <p class="no-data">Tidak ada data ringkasan nilai.</p>
    @endif
</div>

<div class="section-block page-break">
    <div class="section-title">J. Ekstrakurikuler</div>
    @if(isset($extracurriculars) && count($extracurriculars) > 0)
    <table class="list">
        <thead>
            <tr>
                <th class="num">No</th><th>Nama</th><th>Tahun Ajaran</th><th>Semester</th>
                <th>Bergabung</th><th>Keluar</th><th>Status</th><th>Nilai</th><th>KKM</th><th>Predikat</th>
            </tr>
        </thead>
        <tbody>
            @foreach($extracurriculars as $idx => $e)
            @php $e = is_array($e) ? $e : (array) $e; @endphp
            <tr>
                <td class="num">{{ $idx + 1 }}</td>
                <td>{{ $e['extracurricular']['name'] ?? '-' }}</td>
                <td>{{ $e['academic_year']['name'] ?? '-' }}</td>
                <td>{{ $e['semester']['name'] ?? '-' }}</td>
                <td class="center">{{ $fmtShort($e['joined_at'] ?? null) }}</td>
                <td class="center">{{ $fmtShort($e['left_at'] ?? null) }}</td>
                <td>{{ $e['status'] ?? '-' }}</td>
                <td class="center">{{ isset($e['score']) && $e['score'] !== null ? $e['score'] : '-' }}</td>
                <td class="center">{{ isset($e['kkm']) && $e['kkm'] !== null ? $e['kkm'] : '-' }}</td>
                <td class="center">{{ $e['predicate'] ?? '-' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @else
    <p class="no-data">Tidak ada data ekstrakurikuler.</p>
    @endif
</div>

<div class="section-block">
    <div class="section-title">K. Tujuan Setelah Lulus</div>
    @if(isset($alumni_destinations) && $alumni_destinations->isNotEmpty())
    @php
        $destLabels = [
            'Sekolah' => 'Lanjut Sekolah',
            'Perguruan_Tinggi' => 'Perguruan Tinggi',
            'Kerja' => 'Bekerja',
            'Wirausaha' => 'Wirausaha',
            'Lainnya' => 'Lainnya',
        ];
    @endphp
    <table class="list">
        <thead>
            <tr><th class="num">No</th><th>Jenis</th><th>Nama / Tempat</th><th>Program / Posisi</th><th>Tahun</th><th>Keterangan</th></tr>
        </thead>
        <tbody>
            @foreach($alumni_destinations as $idx => $d)
            <tr>
                <td class="num">{{ $idx + 1 }}</td>
                <td>{{ $destLabels[$d->destination_type] ?? $d->destination_type }}</td>
                <td>{{ $d->destination_name ?? '-' }}</td>
                <td>{{ $d->program_or_position ?? '-' }}</td>
                <td class="center">{{ $d->year_entered ?? '-' }}</td>
                <td>{{ $d->notesForDisplay() ?? '-' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @else
    <p class="no-data">Tidak ada data tujuan setelah lulus.</p>
    @endif
</div>

<div class="section-block">
    <div class="section-title">L. Ringkasan Perpustakaan</div>
    <table class="summary-box">
        <tr>
            <td class="lbl">Total Peminjaman</td>
            <td>{{ $library_loans_summary['total_loans'] ?? 0 }}</td>
            <td class="lbl">Keterlambatan</td>
            <td>{{ $library_loans_summary['late_count'] ?? 0 }}</td>
        </tr>
        <tr>
            <td class="lbl">Sedang Terlambat</td>
            <td colspan="3">{{ $library_loans_summary['overdue_count'] ?? 0 }}</td>
        </tr>
    </table>
</div>

<div class="section-block">
    <div class="section-title">M. Riwayat Kesehatan (UKS)</div>
    @if(isset($health_records) && count($health_records) > 0)
    <table class="list">
        <thead>
            <tr><th class="num">No</th><th>Tanggal</th><th>Jenis</th><th>Keterangan</th></tr>
        </thead>
        <tbody>
            @foreach($health_records as $idx => $h)
            <tr>
                <td class="num">{{ $idx + 1 }}</td>
                <td class="center">{{ $fmtShort($h['date'] ?? null) }}</td>
                <td>{{ $h['type'] ?? '-' }}</td>
                <td>{{ $h['notes'] ?? '-' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @else
    <p class="no-data">Belum ada data dari modul UKS.</p>
    @endif
</div>

@if(isset($document_pickups) && $document_pickups->isNotEmpty())
<div class="section-block">
    <div class="section-title">N. Pengambilan Ijazah</div>
    <table class="list">
        <thead>
            <tr><th class="num">No</th><th>Tanggal</th><th>Dokumen</th><th>No. Ijazah / Blangko</th><th>Diterima oleh</th></tr>
        </thead>
        <tbody>
            @foreach($document_pickups as $idx => $dp)
            <tr>
                <td class="num">{{ $idx + 1 }}</td>
                <td class="center">{{ $fmtShort($dp->pickup_date) }}</td>
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
</div>
@endif

@if($student->notes)
<div class="section-block">
    <div class="section-title">O. Catatan</div>
    <p style="margin:4px 0; font-size:9pt;">{{ $student->notes }}</p>
</div>
@endif

<div class="standard-signature-wrap">
    <div class="standard-signature-left"></div>
    <div class="standard-signature-right">
        @include('partials.print-signature', [
            'institution' => $institution,
            'date' => $signature_date ?? now()->locale('id')->translatedFormat('d F Y'),
            'as_of_date' => $as_of_date ?? now(),
        ])
    </div>
</div>

@include('partials.print-document-footer', ['footer_tag' => 'p', 'footer_class' => 'printed-at'])
</body>
</html>
