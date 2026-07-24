<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Data Guru - {{ $institution->name ?? 'Export' }}</title>
    <style>
        @page { margin: 1.2cm; size: A4 landscape; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 9pt; line-height: 1.3; color: #000; }
        .header { text-align: center; margin-bottom: 12px; }
        .header h1 { font-size: 14pt; font-weight: bold; margin: 0 0 4px 0; }
        .period { font-size: 8pt; margin-bottom: 8px; color: #555; }
        .stats { margin-bottom: 10px; padding: 8px 10px; background: #f5f5f5; border: 1px solid #ddd; font-size: 8pt; }
        table { width: calc(100% - 2px); max-width: calc(100% - 2px); border-collapse: collapse; font-size: 8pt; }
        table th, table td { border: 1px solid #333; padding: 4px 6px; text-align: left; }
        table th { background: #e8e8e8; font-weight: bold; }
        table td.num { text-align: center; width: 28px; }
        table td.center { text-align: center; }
        .footer { margin-top: 10px; font-size: 7pt; text-align: center; color: #666; }
        @include('partials.print-letterhead-styles')
        @include('partials.print-signature-styles')
        .standard-signature-wrap { margin-top: 18px; }
        .standard-signature-space { height: 48px; }
    </style>
</head>
<body>
    @include('partials.print-letterhead', ['institution' => $institution])
    <div class="header">
        <h1>DATA GURU / PEGAWAI</h1>
    </div>

    @if(!empty($filter_label))
    <div class="period">Filter: {{ $filter_label }}</div>
    @endif

    <div class="stats">
        Total data: <strong>{{ $employees->count() }}</strong>
        &nbsp;|&nbsp; Dicetak: {{ $printed_at }}
    </div>

    <table>
        <thead>
            <tr>
                <th class="num">No</th>
                <th>Nama</th>
                <th>NIP</th>
                <th>NUPTK</th>
                <th class="center">JK</th>
                <th>Tipe</th>
                <th>Mata Pelajaran</th>
                <th>Kepegawaian</th>
                <th>Status</th>
                <th>Afiliasi</th>
            </tr>
        </thead>
        <tbody>
            @forelse($employees as $index => $employee)
            @php
                $affiliation = ($institutionId && (int) $employee->institution_id === (int) $institutionId)
                    ? 'Induk'
                    : 'Non-Induk';
                $gender = match ($employee->gender ?? null) {
                    'L' => 'L',
                    'P' => 'P',
                    default => $employee->gender ?: '-',
                };
            @endphp
            <tr>
                <td class="num">{{ $index + 1 }}</td>
                <td>{{ $employee->name ?? '-' }}</td>
                <td>{{ $employee->nip ?? '-' }}</td>
                <td>{{ $employee->nuptk ?? '-' }}</td>
                <td class="center">{{ $gender }}</td>
                <td>{{ $employee->type ?? '-' }}</td>
                <td>{{ $employee->subject ?? '-' }}</td>
                <td>{{ $employee->employment_status ?? '-' }}</td>
                <td>{{ $employee->status ?? '-' }}</td>
                <td>{{ $affiliation }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="10" class="center" style="padding: 12px;">Tidak ada data guru/pegawai.</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        Dicetak pada {{ $printed_at }} &mdash; {{ $employees->count() }} catatan
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
