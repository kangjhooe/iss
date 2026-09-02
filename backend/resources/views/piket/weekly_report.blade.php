<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>{{ $title ?? 'Laporan Guru Piket' }} - {{ $institution->name ?? 'Institusi' }}</title>
    <style>
        @page { margin: 1.2cm; size: A4 portrait; }
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 9pt;
            line-height: 1.3;
            color: #000;
        }
        .header { text-align: center; margin-bottom: 10px; }
        .header h1 {
            font-size: 13pt;
            font-weight: bold;
            margin: 0 0 4px 0;
            text-transform: uppercase;
        }
        .period { font-size: 8pt; margin-bottom: 10px; color: #333; }
        .stats {
            margin-bottom: 12px;
            padding: 6px 8px;
            background: #f5f5f5;
            border: 1px solid #ddd;
            font-size: 8pt;
        }
        .stats table { width: 100%; border: none; border-collapse: collapse; }
        .stats td {
            border: none;
            padding: 4px 6px;
            text-align: center;
            width: 16.66%;
        }
        .stats .num { font-size: 12pt; font-weight: bold; }
        .stats .lbl { font-size: 7.5pt; color: #444; }
        .section-title {
            font-size: 10pt;
            font-weight: bold;
            margin: 12px 0 6px;
            text-transform: uppercase;
        }
        table.data {
            width: calc(100% - 2px);
            max-width: calc(100% - 2px);
            border-collapse: collapse;
            font-size: 8pt;
            margin-bottom: 8px;
        }
        table.data th,
        table.data td {
            border: 1px solid #333;
            padding: 4px 6px;
            text-align: left;
            vertical-align: top;
        }
        table.data th {
            background: #e8e8e8;
            font-weight: bold;
            text-align: center;
        }
        .muted { color: #555; font-size: 7.5pt; }
        .cell-note { color: #555; font-size: 7.5pt; margin-top: 2px; }
        .footer {
            margin-top: 10px;
            font-size: 7pt;
            text-align: center;
            color: #666;
        }
        @include('partials.print-letterhead-styles')
        @include('partials.print-signature-styles')
    </style>
</head>
<body>
    @include('partials.print-letterhead', ['institution' => $institution])

    <div class="header">
        <h1>{{ $title ?? 'Laporan Guru Piket' }}</h1>
    </div>

    <div class="period">
        Periode: {{ $period_label ?? $week_label }}
    </div>

    <div class="stats">
        <table>
            <tr>
                <td>
                    <div class="num">{{ $summary['total_logs'] }}</div>
                    <div class="lbl">Log Piket</div>
                </td>
                <td>
                    <div class="num">{{ $summary['kelas_kosong'] }}</div>
                    <div class="lbl">Kelas Kosong</div>
                </td>
                <td>
                    <div class="num">{{ $summary['terlambat_guru'] }}</div>
                    <div class="lbl">Terlambat Guru</div>
                </td>
                <td>
                    <div class="num">{{ $summary['terlambat_siswa'] }}</div>
                    <div class="lbl">Terlambat Siswa</div>
                </td>
                <td>
                    <div class="num">{{ $summary['lainnya'] ?? 0 }}</div>
                    <div class="lbl">Lainnya</div>
                </td>
                <td>
                    <div class="num">{{ $summary['total_incidents'] }}</div>
                    <div class="lbl">Total Insiden</div>
                </td>
            </tr>
        </table>
    </div>

    <div class="section-title">1. Log Harian</div>
    <table class="data">
        <thead>
            <tr>
                <th style="width:70px">Tanggal</th>
                <th>Guru Piket</th>
                <th>Ringkasan</th>
                <th style="width:70px">Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($logs as $log)
                <tr>
                    <td>{{ $log->duty_date?->format('d/m/Y') }}</td>
                    <td>
                        <div>{{ $log->employee->name ?? '-' }}</div>
                        @if($log->employee)
                            <div class="cell-note">{{ $log->employee->nip ?: 'Tanpa NIP/NUPTK' }}</div>
                        @endif
                    </td>
                    <td>{{ $log->summary ?: '-' }}</td>
                    <td>{{ \App\Models\PiketLog::STATUSES[$log->status] ?? $log->status }}</td>
                </tr>
            @empty
                <tr><td colspan="4" style="text-align:center;padding:10px;">Belum ada log harian pada periode ini.</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="section-title">2. Monitoring Insiden</div>
    <table class="data">
        <thead>
            <tr>
                <th style="width:70px">Tanggal</th>
                <th style="width:90px">Jenis</th>
                <th>Detail</th>
                <th style="width:70px">Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($incidents as $inc)
                <tr>
                    <td>{{ $inc->incident_date?->format('d/m/Y') }}</td>
                    <td>{{ \App\Models\PiketIncident::TYPES[$inc->incident_type] ?? $inc->incident_type }}</td>
                    <td>
                        {{ $inc->description ?: '-' }}
                        @if($inc->schoolClass)
                            <div class="muted">Kelas: {{ $inc->schoolClass->name }}@if($inc->period) · Jam ke-{{ $inc->period }}@endif</div>
                        @endif
                        @if($inc->employee)
                            <div class="muted">
                                Guru: {{ $inc->employee->name }}
                                @if($inc->employee->nip)
                                    <span class="cell-note">({{ $inc->employee->nip }})</span>
                                @endif
                            </div>
                        @endif
                        @if($inc->student)
                            <div class="muted">Siswa: {{ $inc->student->name }} ({{ $inc->student->nis }})</div>
                        @endif
                        @if($inc->minutes_late)
                            <div class="muted">Terlambat {{ $inc->minutes_late }} menit</div>
                        @endif
                    </td>
                    <td>{{ \App\Models\PiketIncident::STATUSES[$inc->status] ?? $inc->status }}</td>
                </tr>
            @empty
                <tr><td colspan="4" style="text-align:center;padding:10px;">Tidak ada insiden pada periode ini.</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="standard-signature-wrap">
        <div class="standard-signature-left">
            @include('partials.print-signature', [
                'institution' => $institution,
                'show_place_date' => false,
                'as_of_date' => $period_end ?? null,
            ])
        </div>
        <div class="standard-signature-right">
            @include('partials.print-signature', [
                'institution' => $institution,
                'role' => 'Guru Piket / Koordinator',
                'name' => '',
                'nip' => '',
                'date' => now()->locale('id')->translatedFormat('d F Y'),
            ])
        </div>
    </div>

    <div class="footer print-document-footer">
        Dicetak pada {{ $generated_at }} &mdash; {{ $summary['total_logs'] }} log · {{ $summary['total_incidents'] }} insiden
    </div>
</body>
</html>
