<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Bukti Pendaftaran {{ $applicant->registration_number }}</title>
    <style>
        @page { margin: 1.5cm; size: A4 portrait; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 10pt; line-height: 1.4; color: #000; }
        .doc-title { text-align: center; margin: 10px 0 2px; font-size: 13pt; font-weight: bold; text-transform: uppercase; }
        .doc-sub { text-align: center; font-size: 9pt; color: #444; margin-bottom: 12px; }
        .reg-box {
            border: 2px solid #111;
            text-align: center;
            padding: 10px 8px;
            margin: 0 0 14px;
        }
        .reg-box .lbl { font-size: 8pt; color: #555; letter-spacing: 0.04em; text-transform: uppercase; }
        .reg-box .val { font-size: 16pt; font-weight: bold; font-family: DejaVu Sans, monospace; margin-top: 4px; }
        table.info { width: 100%; border-collapse: collapse; font-size: 10pt; }
        table.info td { padding: 5px 4px; vertical-align: top; border-bottom: 1px solid #ddd; }
        table.info td.label { width: 32%; color: #444; }
        h3.sec { font-size: 10pt; margin: 14px 0 6px; border-bottom: 1px solid #333; padding-bottom: 3px; }
        table.docs { width: 100%; border-collapse: collapse; font-size: 9pt; }
        table.docs th, table.docs td { border: 1px solid #333; padding: 4px 6px; }
        table.docs th { background: #eee; text-align: left; }
        .hint { margin-top: 12px; font-size: 8pt; color: #555; }
        .footer { margin-top: 10px; font-size: 8pt; text-align: center; color: #666; }
        @include('partials.print-letterhead-styles')
        @include('partials.print-signature-styles')
    </style>
</head>
<body>
    @include('partials.print-letterhead', ['institution' => $institution])

    <div class="doc-title">Bukti Pendaftaran {{ $admission_label }}</div>
    <div class="doc-sub">Simpan dokumen ini sebagai bukti pendaftaran calon peserta didik</div>

    <div class="reg-box">
        <div class="lbl">Nomor Pendaftaran</div>
        <div class="val">{{ $applicant->registration_number }}</div>
    </div>

    <table class="info">
        <tr><td class="label">Nama</td><td>{{ $applicant->name }}</td></tr>
        <tr><td class="label">Jenis kelamin</td><td>{{ $applicant->gender === 'P' ? 'Perempuan' : 'Laki-laki' }}</td></tr>
        <tr><td class="label">NISN</td><td>{{ $applicant->nisn ?: '—' }}</td></tr>
        <tr><td class="label">Tempat, tanggal lahir</td><td>{{ $applicant->birth_place ?: '—' }}, {{ $applicant->birth_date ? $applicant->birth_date->locale('id')->isoFormat('D MMMM YYYY') : '—' }}</td></tr>
        <tr><td class="label">Alamat</td><td>{{ \App\Support\RegionAddress::format($applicant) ?: '—' }}</td></tr>
        <tr><td class="label">Periode</td><td>{{ $applicant->period?->name ?: '—' }}@if($applicant->period?->academicYear) ({{ $applicant->period->academicYear->code ?: $applicant->period->academicYear->name }})@endif</td></tr>
        <tr><td class="label">Jalur</td><td>{{ $applicant->channel?->name ?: '—' }}</td></tr>
        <tr><td class="label">Tanggal daftar</td><td>{{ $applicant->submitted_at ? $applicant->submitted_at->locale('id')->isoFormat('D MMMM YYYY HH:mm') : ($applicant->created_at?->locale('id')->isoFormat('D MMMM YYYY HH:mm') ?? '—') }}</td></tr>
    </table>

    @if(!empty($checklist['items']))
        <h3 class="sec">Kelengkapan berkas</h3>
        <table class="docs">
            <thead>
                <tr>
                    <th>Jenis berkas</th>
                    <th style="width:18%;">Wajib</th>
                    <th style="width:22%;">Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach($checklist['items'] as $item)
                    <tr>
                        <td>{{ $item['label'] }}</td>
                        <td>{{ $item['required'] ? 'Ya' : 'Tidak' }}</td>
                        <td>{{ $item['uploaded'] ? 'Sudah unggah' : 'Belum' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <p class="hint">Gunakan nomor pendaftaran di atas untuk cek hasil seleksi dan unggah berkas. Jangan bagikan nomor ini kepada pihak yang tidak berkepentingan.</p>
    <div class="footer">Dicetak: {{ $printed_at }}</div>
    @include('partials.print-signature', ['institution' => $institution])
</body>
</html>
