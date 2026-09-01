<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Riwayat Absensi - {{ $student->name ?? 'Siswa' }}</title>
    <style>
        @page { margin: 1.5cm; size: A4 portrait; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 10pt; line-height: 1.35; color: #000; }
        .header { text-align: center; margin-bottom: 12px; }
        .header h1 { font-size: 13pt; font-weight: bold; margin: 0 0 4px 0; text-transform: uppercase; }
        .student-info { margin-bottom: 12px; font-size: 9pt; }
        .student-info table { width: auto; border: none; }
        .student-info td { border: none; padding: 2px 8px 2px 0; }
        .student-info td.label { font-weight: bold; width: 90px; }
        .period { font-size: 8pt; margin-bottom: 8px; color: #333; }
        table.data { width: calc(100% - 2px); max-width: calc(100% - 2px); border-collapse: collapse; font-size: 8pt; }
        table.data th, table.data td { border: 1px solid #333; padding: 4px 6px; text-align: left; }
        table.data th { background: #e8e8e8; font-weight: bold; text-align: center; }
        table.data td.center { text-align: center; }
        .footer { margin-top: 10px; font-size: 7pt; text-align: center; color: #666; }
        @include('partials.print-letterhead-styles')
        @include('partials.print-signature-styles')
    </style>
</head>
<body>
    @include('partials.print-letterhead', ['institution' => $institution])

    <div class="header">
        <h1>Riwayat Absensi Siswa</h1>
    </div>

    <div class="student-info">
        <table>
            <tr>
                <td class="label">Nama</td>
                <td>: {{ $student->name ?? '-' }}</td>
            </tr>
            <tr>
                <td class="label">NIS / NISN</td>
                <td>: {{ $student->nis ?? '-' }} / {{ $student->nisn ?? '-' }}</td>
            </tr>
            <tr>
                <td class="label">Kelas</td>
                <td>: {{ $student->class->name ?? '-' }}</td>
            </tr>
        </table>
    </div>

    <div class="period">
        @if(!empty($meta['semester_name'])) Semester: {{ $meta['semester_name'] }} &nbsp;|&nbsp; @endif
        Periode:
        {{ !empty($meta['date_from']) ? \Carbon\Carbon::parse($meta['date_from'])->locale('id')->isoFormat('D MMM YYYY') : '...' }}
        s/d
        {{ !empty($meta['date_to']) ? \Carbon\Carbon::parse($meta['date_to'])->locale('id')->isoFormat('D MMM YYYY') : '...' }}
    </div>

    <table class="data">
        <thead>
            <tr>
                <th style="width:28px;">No</th>
                <th>Tanggal</th>
                <th>Semester</th>
                <th>Kelas</th>
                <th>Mapel</th>
                <th>Guru</th>
                <th>Jam ke</th>
                <th>Status</th>
                <th>Catatan</th>
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $index => $row)
            <tr>
                <td class="center">{{ $index + 1 }}</td>
                <td class="center">{{ !empty($row['date']) ? \Carbon\Carbon::parse($row['date'])->format('d/m/Y') : '-' }}</td>
                <td>{{ $row['semester_name'] ?? '-' }}</td>
                <td>{{ $row['class_name'] ?? '-' }}</td>
                <td>{{ $row['subject_name'] ?? '-' }}</td>
                <td>{{ $row['teacher_name'] ?? '-' }}</td>
                <td class="center">{{ $row['period'] ?? '-' }}</td>
                <td class="center">{{ $row['status_label'] ?? ($row['status'] ?? '-') }}</td>
                <td>{{ $row['notes'] ?? '-' }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="9" class="center" style="padding:12px;">Belum ada data absensi untuk filter yang dipilih.</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        Dicetak pada {{ $printed_at }} &mdash; {{ count($rows) }} catatan
    </div>
    <div class="standard-signature-wrap">
        <div class="standard-signature-left"></div>
        <div class="standard-signature-right">
            @include('partials.print-signature', [
                'institution' => $institution,
                'date' => ($as_of_date ?? now())->locale('id')->translatedFormat('d F Y'),
                'as_of_date' => $as_of_date ?? null,
            ])
        </div>
    </div>
</body>
</html>
