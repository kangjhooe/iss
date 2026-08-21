<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Buku Mutasi - {{ $institution->name ?? 'Export' }}</title>
    <style>
        @page { margin: 1.2cm; size: A4 landscape; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 9pt; line-height: 1.3; color: #000; }
        .header { text-align: center; margin-bottom: 12px; }
        .header h1 { font-size: 14pt; font-weight: bold; margin: 0 0 4px 0; }
        .header p { font-size: 8pt; margin: 0; color: #444; }
        .period { font-size: 8pt; margin-bottom: 10px; color: #555; }
        table { width: calc(100% - 2px); max-width: calc(100% - 2px); border-collapse: collapse; font-size: 8pt; }
        table th, table td { border: 1px solid #333; padding: 4px 6px; text-align: left; }
        table th { background: #e8e8e8; font-weight: bold; }
        table td.num { text-align: center; width: 28px; }
        table td.date { white-space: nowrap; }
        .footer { margin-top: 10px; font-size: 7pt; text-align: center; color: #666; }
        @include('partials.print-letterhead-styles')
        @include('partials.print-signature-styles')
    </style>
</head>
<body>
    @include('partials.print-letterhead', ['institution' => $institution])
    <div class="header">
        <h1>BUKU MUTASI SISWA</h1>
    </div>

    @if(!empty($date_from) || !empty($date_to))
    <div class="period">
        Periode: {{ $date_from ? \Carbon\Carbon::parse($date_from)->locale('id')->isoFormat('D MMM YYYY') : '...' }}
        s/d {{ $date_to ? \Carbon\Carbon::parse($date_to)->locale('id')->isoFormat('D MMM YYYY') : '...' }}
    </div>
    @endif

    <table>
        <thead>
            <tr>
                <th class="num">No</th>
                <th class="date">Tanggal</th>
                <th>NIK</th>
                <th>NISN</th>
                <th>Nama Siswa</th>
                <th class="num">JK</th>
                <th>Kelas</th>
                <th>Jenis</th>
                <th>Sekolah Asal</th>
                <th>NPSN Asal</th>
                <th>Sekolah Tujuan</th>
                <th>NPSN Tujuan</th>
                <th>Alasan / Keterangan</th>
                <th>Disetujui oleh</th>
            </tr>
        </thead>
        <tbody>
            @forelse($mutations as $index => $m)
            @php
                $isOut = $m->origin_institution_id === ($institution->id ?? $institution_id ?? null);
                $jenis = $isOut ? 'Keluar' : 'Masuk';
            @endphp
            <tr>
                <td class="num">{{ $index + 1 }}</td>
                <td class="date">{{ $m->approved_at ? $m->approved_at->locale('id')->format('d/m/Y') : ($m->created_at ? $m->created_at->locale('id')->format('d/m/Y') : '-') }}</td>
                <td>{{ $m->student?->nik ?? '-' }}</td>
                <td>{{ $m->student?->nisn ?? '-' }}</td>
                <td>{{ $m->student?->name ?? '-' }}</td>
                <td class="num">{{ $m->student_gender ?? $m->student?->gender ?? '-' }}</td>
                <td>{{ $m->student_grade ?? '-' }}</td>
                <td>{{ $jenis }}</td>
                <td>{{ $m->originInstitution?->name ?? $m->origin_school_name ?? '-' }}</td>
                <td>{{ $m->originInstitution?->npsn ?? $m->origin_npsn ?? '-' }}</td>
                <td>{{ $m->targetInstitution?->name ?? $m->target_school_name ?? '-' }}</td>
                <td>{{ $m->targetInstitution?->npsn ?? $m->target_npsn ?? '-' }}</td>
                <td>{{ $m->notes ?? '-' }}</td>
                <td>{{ $m->approver?->name ?? '-' }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="14" style="text-align: center; padding: 12px;">Tidak ada data mutasi dalam periode ini.</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        Dicetak pada {{ $printed_at }} &mdash; {{ $mutations->count() }} catatan
    </div>
    <div class="standard-signature-wrap">
        <div class="standard-signature-left"></div>
        <div class="standard-signature-right">
            @include('partials.print-signature', [
                'institution' => $institution,
                'date' => now()->locale('id')->translatedFormat('d F Y'),
            ])
        </div>
    </div>
</body>
</html>
