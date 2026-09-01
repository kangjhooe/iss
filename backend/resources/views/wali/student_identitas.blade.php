<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Identitas Peserta Didik - {{ $class->name ?? '' }}</title>
    <style>
        @page { margin: 1.2cm 1.4cm 1.3cm 1.4cm; size: A4 portrait; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 9pt; color: #000; }
        .sheet { page-break-after: always; }
        .sheet:last-child { page-break-after: auto; }
        .header { text-align: center; margin: 6px 0 8px 0; }
        .header h1 { font-size: 13pt; margin: 0; text-transform: uppercase; letter-spacing: .04em; }
        .header p { font-size: 9pt; margin: 3px 0 0 0; }
        .title-row { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
        .title-row td { vertical-align: top; }
        .photo-box {
            width: 2.7cm; height: 3.6cm; border: 1px solid #333;
            text-align: center; vertical-align: middle;
        }
        .photo-box img { width: 2.7cm; height: 3.6cm; object-fit: cover; }
        .photo-empty { font-size: 8pt; color: #666; padding: 8px 4px; line-height: 1.3; }
        .section-title {
            font-weight: bold; font-size: 9.5pt; margin: 8px 0 4px 0;
            background: #e8e8e8; padding: 3px 6px; text-transform: uppercase;
        }
        table.data { width: 100%; border-collapse: collapse; }
        table.data td { padding: 2px 5px; vertical-align: top; border-bottom: 1px solid #ddd; }
        table.data td.label { width: 28%; font-weight: bold; color: #222; }
        table.data td.sep { width: 2%; color: #666; }
        table.two-col { width: 100%; border-collapse: collapse; }
        table.two-col > tbody > tr > td { width: 50%; vertical-align: top; padding: 0 4px 0 0; }
        table.two-col > tbody > tr > td + td { padding: 0 0 0 4px; }
        .muted { color: #555; }
        @include('partials.print-letterhead-styles')
        @include('partials.print-signature-styles')
    </style>
</head>
<body>
@php
    $genderLabel = fn ($g) => match ($g) {
        'L' => 'Laki-laki',
        'P' => 'Perempuan',
        default => $g ?: '-',
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
    $fmtDate = function ($v) {
        if ($v === null || $v === '') {
            return '-';
        }
        try {
            return \Carbon\Carbon::parse($v)->locale('id')->isoFormat('D MMMM YYYY');
        } catch (\Throwable $e) {
            return (string) $v;
        }
    };
    $val = fn ($v) => ($v !== null && $v !== '') ? $v : '-';
    $lookup = fn ($map, $key) => ($key !== null && $key !== '' && isset($map[$key])) ? $map[$key] : null;
@endphp

@foreach($sheets as $sheet)
@php $student = $sheet['student']; @endphp
<div class="sheet">
    @include('partials.print-letterhead', ['institution' => $institution])

    <table class="title-row">
        <tr>
            <td>
                <div class="header" style="text-align:left;">
                    <h1>Identitas Peserta Didik</h1>
                    <p>
                        Kelas {{ $class->name ?? '-' }}
                        @if(!empty($class->grade)) · Tingkat {{ $class->grade }} @endif
                        @if($student->academicYear?->name || $student->academic_year)
                            · {{ $student->academicYear->name ?? $student->academic_year }}
                        @endif
                    </p>
                </div>
            </td>
            <td style="width:2.9cm; text-align:right;">
                <div class="photo-box">
                    @if(!empty($sheet['photo_base64']))
                        <img src="{!! $sheet['photo_base64'] !!}" alt="Foto">
                    @else
                        <div class="photo-empty">Pas foto<br>3 × 4</div>
                    @endif
                </div>
            </td>
        </tr>
    </table>

    <div class="section-title">A. Identitas Siswa</div>
    <table class="data">
        <tr><td class="label">Nama Lengkap</td><td class="sep">:</td><td>{{ $val($student->name) }}</td></tr>
        <tr><td class="label">NIS / NISN</td><td class="sep">:</td><td>{{ $val($student->nis) }} / {{ $val($student->nisn) }}</td></tr>
        <tr><td class="label">NIK / No. KK</td><td class="sep">:</td><td>{{ $val($student->nik) }} / {{ $val($student->no_kk) }}</td></tr>
        <tr><td class="label">Jenis Kelamin</td><td class="sep">:</td><td>{{ $genderLabel($student->gender) }}</td></tr>
        <tr><td class="label">Tempat, Tanggal Lahir</td><td class="sep">:</td><td>{{ $val($student->birth_place) }}, {{ $fmtDate($student->birth_date) }}</td></tr>
        <tr><td class="label">Agama</td><td class="sep">:</td><td>{{ $val($student->religion) }}</td></tr>
        <tr><td class="label">Alamat</td><td class="sep">:</td><td>{{ \App\Support\RegionAddress::format($student) ?: $val($student->address) }}</td></tr>
        <tr><td class="label">Telepon / Email</td><td class="sep">:</td><td>{{ $val($student->phone) }} / {{ $val($student->email) }}</td></tr>
        <tr><td class="label">Tinggi / Berat</td><td class="sep">:</td><td>{{ $student->height ? $student->height.' cm' : '-' }} / {{ $student->weight ? $student->weight.' kg' : '-' }}</td></tr>
        <tr><td class="label">Tempat Tinggal</td><td class="sep">:</td><td>{{ $lookup($residenceLabels, $student->residence_type) ?? $val($student->residence_type) }}</td></tr>
        <tr><td class="label">Kebutuhan Khusus</td><td class="sep">:</td><td>{{ $val($student->disability) }}</td></tr>
        <tr><td class="label">Cita-cita / Hobi</td><td class="sep">:</td><td>{{ $val($student->aspiration) }} / {{ $val($student->hobby) }}</td></tr>
        <tr><td class="label">Sekolah Asal</td><td class="sep">:</td><td>{{ $val($student->previous_school) }}</td></tr>
    </table>

    <div class="section-title">B. Data Orang Tua / Wali</div>
    <table class="two-col">
        <tr>
            <td>
                <table class="data">
                    <tr><td class="label">Ayah</td><td class="sep">:</td><td>{{ $val($student->father_name) }}</td></tr>
                    <tr><td class="label">Status</td><td class="sep">:</td><td>{{ $lookup($parentStatus, $student->father_status) ?? $val($student->father_status) }}</td></tr>
                    <tr><td class="label">NIK</td><td class="sep">:</td><td>{{ $val($student->father_nik) }}</td></tr>
                    <tr><td class="label">TTL</td><td class="sep">:</td><td>{{ $val($student->father_birth_place) }}, {{ $fmtDate($student->father_birth_date) }}</td></tr>
                    <tr><td class="label">Pendidikan</td><td class="sep">:</td><td>{{ $val($student->father_education) }}</td></tr>
                    <tr><td class="label">Pekerjaan</td><td class="sep">:</td><td>{{ $val($student->father_occupation) }}</td></tr>
                </table>
            </td>
            <td>
                <table class="data">
                    <tr><td class="label">Ibu</td><td class="sep">:</td><td>{{ $val($student->mother_name) }}</td></tr>
                    <tr><td class="label">Status</td><td class="sep">:</td><td>{{ $lookup($parentStatus, $student->mother_status) ?? $val($student->mother_status) }}</td></tr>
                    <tr><td class="label">NIK</td><td class="sep">:</td><td>{{ $val($student->mother_nik) }}</td></tr>
                    <tr><td class="label">TTL</td><td class="sep">:</td><td>{{ $val($student->mother_birth_place) }}, {{ $fmtDate($student->mother_birth_date) }}</td></tr>
                    <tr><td class="label">Pendidikan</td><td class="sep">:</td><td>{{ $val($student->mother_education) }}</td></tr>
                    <tr><td class="label">Pekerjaan</td><td class="sep">:</td><td>{{ $val($student->mother_occupation) }}</td></tr>
                </table>
            </td>
        </tr>
    </table>
    <table class="data" style="margin-top:4px;">
        <tr>
            <td class="label">Wali</td><td class="sep">:</td>
            <td>
                {{ $lookup($guardianTypeLabels, $student->guardian_type) ?? $val($student->guardian_type) }}
                · {{ $val($student->guardian_name) }}
                · {{ $val($student->guardian_phone) }}
            </td>
        </tr>
    </table>

    @include('partials.print-wali-signatures', [
        'institution' => $institution,
        'wali_kelas' => $wali_kelas ?? null,
        'signature_date' => $signature_date ?? null,
    ])
</div>
@endforeach
</body>
</html>
