<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Rekap Absensi Siswa - {{ $institution->name ?? 'Institusi' }}</title>
    <style>
        @page { margin: 1.2cm; size: A4 landscape; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 9pt; line-height: 1.3; color: #000; }
        .header { text-align: center; margin-bottom: 10px; }
        .header h1 { font-size: 13pt; font-weight: bold; margin: 0 0 4px 0; text-transform: uppercase; }
        .period { font-size: 8pt; margin-bottom: 8px; color: #333; }
        .stats { margin-bottom: 10px; padding: 6px 8px; background: #f5f5f5; border: 1px solid #ddd; font-size: 8pt; }
        .stats table { width: auto; border: none; }
        .stats td { border: none; padding: 2px 10px 2px 0; }
        .stats td.label { font-weight: bold; }
        table.data { width: calc(100% - 2px); max-width: calc(100% - 2px); border-collapse: collapse; font-size: 8pt; }
        table.data th, table.data td { border: 1px solid #333; padding: 3px 5px; text-align: left; }
        table.data th { background: #e8e8e8; font-weight: bold; text-align: center; }
        table.data td.num, table.data td.center { text-align: center; }
        table.data tfoot td { font-weight: bold; background: #f0f0f0; }
        .footer { margin-top: 10px; font-size: 7pt; text-align: center; color: #666; }
        .legend { margin-top: 6px; font-size: 7pt; color: #555; }
        @include('partials.print-letterhead-styles')
        @include('partials.print-signature-styles')
    </style>
</head>
<body>
    @include('partials.print-letterhead', ['institution' => $institution])

    <div class="header">
        <h1>Rekap Absensi Siswa</h1>
    </div>

    <div class="period">
        @if(!empty($meta['semester_name'])) Semester: {{ $meta['semester_name'] }} &nbsp;|&nbsp; @endif
        @if(!empty($meta['class_name'])) Kelas: {{ $meta['class_name'] }} &nbsp;|&nbsp; @endif
        @if(!empty($meta['subject_name'])) Mapel: {{ $meta['subject_name'] }} &nbsp;|&nbsp; @endif
        Periode:
        {{ !empty($meta['date_from']) ? \Carbon\Carbon::parse($meta['date_from'])->locale('id')->isoFormat('D MMM YYYY') : '...' }}
        s/d
        {{ !empty($meta['date_to']) ? \Carbon\Carbon::parse($meta['date_to'])->locale('id')->isoFormat('D MMM YYYY') : '...' }}
        &nbsp;|&nbsp; Pertemuan: {{ $meta['meeting_days'] ?? 0 }} hari
        &nbsp;|&nbsp; JP: {{ $meta['jp_count'] ?? ($meta['session_count'] ?? 0) }}
    </div>

    <div class="stats">
        <table>
            <tr>
                <td class="label">Jumlah siswa:</td><td>{{ $meta['student_count'] ?? count($rows) }}</td>
                <td class="label" style="padding-left:16px;">Tercatat:</td><td>{{ $totals['tercatat'] ?? 0 }}</td>
                <td class="label" style="padding-left:16px;">Hadir:</td><td>{{ $totals['hadir'] ?? 0 }}</td>
                <td class="label" style="padding-left:16px;">Alpha:</td><td>{{ $totals['alpha'] ?? 0 }}</td>
                <td class="label" style="padding-left:16px;">Izin:</td><td>{{ $totals['izin'] ?? 0 }}</td>
                <td class="label" style="padding-left:16px;">Sakit:</td><td>{{ $totals['sakit'] ?? 0 }}</td>
                <td class="label" style="padding-left:16px;">Dinas Luar:</td><td>{{ $totals['dinas_luar'] ?? 0 }}</td>
                <td class="label" style="padding-left:16px;">% Hadir (JP):</td><td>{{ $totals['persentase_hadir_jp'] ?? 0 }}%</td>
            </tr>
        </table>
    </div>

    <table class="data">
        <thead>
            <tr>
                <th style="width:28px;">No</th>
                <th>NIS</th>
                <th>Nama Siswa</th>
                <th>Kelas</th>
                <th>H</th>
                <th>A</th>
                <th>I</th>
                <th>S</th>
                <th>DL</th>
                <th>Tercatat</th>
                <th>JP</th>
                <th>Pertemuan</th>
                <th>% (tercatat)</th>
                <th>% (JP)</th>
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $index => $row)
            <tr>
                <td class="num">{{ $index + 1 }}</td>
                <td>{{ $row['nis'] ?? '-' }}</td>
                <td>{{ $row['name'] ?? '-' }}</td>
                <td>{{ $row['class_name'] ?? '-' }}</td>
                <td class="center">{{ $row['counts']['hadir'] ?? 0 }}</td>
                <td class="center">{{ $row['counts']['alpha'] ?? 0 }}</td>
                <td class="center">{{ $row['counts']['izin'] ?? 0 }}</td>
                <td class="center">{{ $row['counts']['sakit'] ?? 0 }}</td>
                <td class="center">{{ $row['counts']['dinas_luar'] ?? 0 }}</td>
                <td class="center">{{ $row['tercatat'] ?? 0 }}</td>
                <td class="center">{{ $row['jp_diharapkan'] ?? ($row['sesi_diharapkan'] ?? 0) }}</td>
                <td class="center">{{ $row['pertemuan_diharapkan'] ?? 0 }}</td>
                <td class="center">{{ $row['persentase_hadir'] ?? 0 }}%</td>
                <td class="center">{{ $row['persentase_hadir_jp'] ?? 0 }}%</td>
            </tr>
            @empty
            <tr>
                <td colspan="14" class="center" style="padding:12px;">Tidak ada data absensi untuk filter yang dipilih.</td>
            </tr>
            @endforelse
        </tbody>
        @if(count($rows) > 0)
        <tfoot>
            <tr>
                <td colspan="4" class="center">TOTAL</td>
                <td class="center">{{ $totals['hadir'] ?? 0 }}</td>
                <td class="center">{{ $totals['alpha'] ?? 0 }}</td>
                <td class="center">{{ $totals['izin'] ?? 0 }}</td>
                <td class="center">{{ $totals['sakit'] ?? 0 }}</td>
                <td class="center">{{ $totals['dinas_luar'] ?? 0 }}</td>
                <td class="center">{{ $totals['tercatat'] ?? 0 }}</td>
                <td class="center">{{ $meta['jp_count'] ?? ($totals['jp_diharapkan'] ?? 0) }}</td>
                <td class="center">{{ $meta['meeting_days'] ?? ($totals['pertemuan_diharapkan'] ?? 0) }}</td>
                <td class="center">{{ $totals['persentase_hadir'] ?? 0 }}%</td>
                <td class="center">{{ $totals['persentase_hadir_jp'] ?? 0 }}%</td>
            </tr>
        </tfoot>
        @endif
    </table>

    <p class="legend">
        H = Hadir, A = Alpha, I = Izin, S = Sakit, DL = Dinas Luar.
        JP = jumlah jam pelajaran (jurnal). Pertemuan = jumlah hari unik.
        % (tercatat) = Hadir ÷ absensi yang tercatat. % (JP) = Hadir ÷ jumlah JP.
    </p>

    <div class="footer">
        Dicetak pada {{ $printed_at }} &mdash; {{ count($rows) }} siswa
    </div>
    <div class="standard-signature-wrap">
        <div class="standard-signature-left"></div>
        <div class="standard-signature-right">
            @include('partials.print-signature', array_filter([
                'institution' => $institution,
                'date' => now()->locale('id')->translatedFormat('d F Y'),
                'role' => $signer['role'] ?? null,
                'name' => $signer['name'] ?? null,
                'nip' => $signer['nip'] ?? null,
            ], fn ($v) => $v !== null))
        </div>
    </div>
</body>
</html>
