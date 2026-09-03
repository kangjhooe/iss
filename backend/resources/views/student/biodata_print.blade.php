<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Biodata {{ $student->name ?? '' }} — {{ $mode === 'singkat' ? 'Singkat' : 'Lengkap' }}</title>
    <style>
        @include('partials.student-print-styles')
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
    $fmtMoney = fn ($v) => ($v !== null && $v !== '') ? 'Rp ' . number_format((float) $v, 0, ',', '.') : '-';
    $val = fn ($v) => ($v !== null && $v !== '') ? $v : '-';
    $lookup = fn ($map, $key) => ($key !== null && $key !== '' && isset($map[$key])) ? $map[$key] : null;
    $classRel = $student->relationLoaded('class') ? $student->getRelation('class') : null;
    $className = (is_object($classRel) && isset($classRel->name))
        ? $classRel->name
        : ($student->getRawOriginal('class') ?? '-');
    $ayRel = $student->relationLoaded('academicYear') ? $student->getRelation('academicYear') : null;
    $academicYearName = $student->academic_year
        ?? ((is_object($ayRel) && isset($ayRel->name)) ? $ayRel->name : '-');
    $isSingkat = ($mode ?? 'lengkap') === 'singkat';
@endphp

@include('partials.print-letterhead', ['institution' => $institution])

<table class="title-row">
    <tr>
        <td>
            <div class="doc-title" style="text-align:left;">
                <h1>Biodata Siswa{{ $isSingkat ? ' (Singkat)' : '' }}</h1>
                <p>{{ $val($student->name) }} · Kelas {{ $className }} · {{ $academicYearName }}</p>
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

<div class="section-block">
    <div class="section-title">A. Identitas Siswa</div>
    <table class="data">
        @if($isSingkat)
            <tr><td class="label">Nama Lengkap</td><td class="sep">:</td><td>{{ $val($student->name) }}</td></tr>
            <tr><td class="label">NIS / NISN</td><td class="sep">:</td><td>{{ $val($student->nis) }} / {{ $val($student->nisn) }}</td></tr>
            <tr><td class="label">NIK</td><td class="sep">:</td><td>{{ $val($student->nik) }}</td></tr>
            <tr><td class="label">Jenis Kelamin</td><td class="sep">:</td><td>{{ $genderLabel($student->gender) }}</td></tr>
            <tr><td class="label">Tempat, Tanggal Lahir</td><td class="sep">:</td><td>{{ $val($student->birth_place) }}, {{ $fmtDate($student->birth_date) }}</td></tr>
            <tr><td class="label">Agama</td><td class="sep">:</td><td>{{ $val($student->religion) }}</td></tr>
            <tr><td class="label">Alamat</td><td class="sep">:</td><td>{{ \App\Support\RegionAddress::format($student) ?: $val($student->address) }}</td></tr>
            <tr><td class="label">Telepon / Email</td><td class="sep">:</td><td>{{ $val($student->phone) }} / {{ $val($student->email) }}</td></tr>
            <tr><td class="label">Kelas / Tahun Ajaran</td><td class="sep">:</td><td>{{ $className }} / {{ $academicYearName }}</td></tr>
            <tr><td class="label">Status</td><td class="sep">:</td><td>{{ $val($student->status) }}</td></tr>
        @else
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
            <tr><td class="label">NPSN / Alamat Sekolah Asal</td><td class="sep">:</td><td>{{ $val($student->previous_school_npsn) }} / {{ $val($student->previous_school_address) }}</td></tr>
            <tr><td class="label">Kelas / Tahun Ajaran</td><td class="sep">:</td><td>{{ $className }} / {{ $academicYearName }}</td></tr>
            <tr><td class="label">Status</td><td class="sep">:</td><td>{{ $val($student->status) }}</td></tr>
            @if($student->notes)
                <tr><td class="label">Catatan</td><td class="sep">:</td><td>{{ $val($student->notes) }}</td></tr>
            @endif
        @endif
    </table>
</div>

<div class="section-block">
    <div class="section-title">B. Data Orang Tua / Wali</div>
    @if($isSingkat)
        <table class="data">
            <tr><td class="label">Ayah</td><td class="sep">:</td><td>{{ $val($student->father_name) }}</td></tr>
            <tr><td class="label">Ibu</td><td class="sep">:</td><td>{{ $val($student->mother_name) }}</td></tr>
            <tr>
                <td class="label">Wali</td><td class="sep">:</td>
                <td>
                    {{ $lookup($guardianTypeLabels, $student->guardian_type) ?? $val($student->guardian_type) }}
                    · {{ $val($student->guardian_name) }}
                    · {{ $val($student->guardian_phone) }}
                </td>
            </tr>
        </table>
    @else
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
                        <tr><td class="label">Penghasilan</td><td class="sep">:</td><td>{{ $fmtMoney($student->father_income) }}</td></tr>
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
                        <tr><td class="label">Penghasilan</td><td class="sep">:</td><td>{{ $fmtMoney($student->mother_income) }}</td></tr>
                    </table>
                </td>
            </tr>
        </table>
        <table class="data" style="margin-top:4px;">
            <tr><td class="label">Jenis Wali</td><td class="sep">:</td><td>{{ $lookup($guardianTypeLabels, $student->guardian_type) ?? $val($student->guardian_type) }}</td></tr>
            <tr><td class="label">Nama Wali</td><td class="sep">:</td><td>{{ $val($student->guardian_name) }}</td></tr>
            <tr><td class="label">Status / NIK</td><td class="sep">:</td><td>{{ $lookup($parentStatus, $student->guardian_status) ?? $val($student->guardian_status) }} / {{ $val($student->guardian_nik) }}</td></tr>
            <tr><td class="label">Telepon</td><td class="sep">:</td><td>{{ $val($student->guardian_phone) }}</td></tr>
            <tr><td class="label">TTL</td><td class="sep">:</td><td>{{ $val($student->guardian_birth_place) }}, {{ $fmtDate($student->guardian_birth_date) }}</td></tr>
            <tr><td class="label">Pendidikan / Pekerjaan</td><td class="sep">:</td><td>{{ $val($student->guardian_education) }} / {{ $val($student->guardian_occupation) }}</td></tr>
            <tr><td class="label">Penghasilan</td><td class="sep">:</td><td>{{ $fmtMoney($student->guardian_income) }}</td></tr>
        </table>
    @endif
</div>

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
