<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Laporan Mingguan Guru Piket</title>
    <style>
        @page { margin: 1.4cm 1.3cm; size: A4 portrait; }
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 10px;
            color: #1a1a1a;
            line-height: 1.4;
        }
        .kop {
            width: 100%;
            border-bottom: 2.5px solid #0f172a;
            padding-bottom: 10px;
            margin-bottom: 4px;
        }
        .kop-inner { width: 100%; }
        .kop-inner td { vertical-align: middle; border: none; padding: 0; }
        .logo-cell { width: 64px; }
        .logo-cell img { width: 58px; height: 58px; object-fit: contain; }
        .kop-text { text-align: center; }
        .kop-text .inst-name {
            font-size: 14px;
            font-weight: bold;
            text-transform: uppercase;
            margin: 0 0 2px;
        }
        .kop-text .inst-foundation {
            font-size: 10px;
            font-weight: 600;
            text-transform: uppercase;
            margin: 0 0 2px;
        }
        .kop-text .inst-meta { font-size: 8.5px; color: #444; margin: 0; }
        .kop-line {
            border-bottom: 0.8px solid #0f172a;
            margin-bottom: 12px;
            height: 3px;
        }
        .doc-title { text-align: center; margin: 6px 0 10px; }
        .doc-title h1 {
            font-size: 13px;
            margin: 0;
            text-transform: uppercase;
            letter-spacing: 0.4px;
        }
        .doc-title .subtitle { font-size: 10px; color: #333; margin: 3px 0 0; }
        .summary {
            width: calc(100% - 2px);
            max-width: calc(100% - 2px);
            border-collapse: collapse;
            margin-bottom: 14px;
        }
        .summary td {
            border: 1px solid #cbd5e1;
            padding: 6px 8px;
            text-align: center;
            width: 20%;
        }
        .summary .num { font-size: 14px; font-weight: bold; }
        .summary .lbl { font-size: 8.5px; color: #475569; }
        .section-title {
            font-size: 11px;
            font-weight: bold;
            margin: 14px 0 6px;
            padding: 4px 8px;
            background: #0f172a;
            color: #fff;
        }
        table.data {
            width: calc(100% - 2px);
            max-width: calc(100% - 2px);
            border-collapse: collapse;
            margin-bottom: 8px;
        }
        table.data th, table.data td {
            border: 1px solid #cbd5e1;
            padding: 4px 6px;
            vertical-align: top;
        }
        table.data th {
            background: #f1f5f9;
            font-size: 9px;
            text-align: left;
        }
        .muted { color: #64748b; font-size: 8.5px; }
        .footer {
            margin-top: 18px;
            font-size: 8.5px;
            color: #64748b;
            border-top: 1px solid #e2e8f0;
            padding-top: 6px;
        }
        .sign {
            width: 100%;
            margin-top: 24px;
        }
        .sign td {
            width: 50%;
            text-align: center;
            vertical-align: top;
            border: none;
            padding: 0;
        }
        .sign .space { height: 48px; }
        @include('partials.print-letterhead-styles')
        .cell-note { color: #555; font-size: 8px; margin-top: 2px; }
    </style>
</head>
<body>
    @include('partials.print-letterhead', ['institution' => $institution])

    <div class="doc-title">
        <h1>Laporan Mingguan Guru Piket</h1>
        <p class="subtitle">Periode {{ $week_label }}</p>
    </div>

    <table class="summary">
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
                <div class="num">{{ $summary['total_incidents'] }}</div>
                <div class="lbl">Total Insiden</div>
            </td>
        </tr>
    </table>

    <div class="section-title">1. Roster Jadwal Piket</div>
    <table class="data">
        <thead>
            <tr>
                <th>Hari</th>
                <th>Shift</th>
                <th>Guru Piket</th>
                <th>Jam</th>
            </tr>
        </thead>
        <tbody>
            @forelse($schedules as $s)
                <tr>
                    <td>{{ \App\Models\PiketSchedule::DAYS[$s->day_of_week] ?? $s->day_of_week }}</td>
                    <td>{{ \App\Models\PiketSchedule::SHIFTS[$s->shift] ?? $s->shift }}</td>
                    <td>
                        <div>{{ $s->employee->name ?? '-' }}</div>
                        @if($s->employee)
                            <div class="cell-note">{{ $s->employee->nip ?: 'Tanpa NIP/NUPTK' }}</div>
                        @endif
                    </td>
                    <td>
                        @if($s->start_time)
                            {{ $s->start_time->format('H:i') }}–{{ $s->end_time?->format('H:i') ?? '' }}
                        @else
                            -
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="4">Belum ada jadwal piket.</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="section-title">2. Log Harian</div>
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
                <tr><td colspan="4">Belum ada log harian minggu ini.</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="section-title">3. Monitoring Insiden</div>
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
                <tr><td colspan="4">Tidak ada insiden minggu ini.</td></tr>
            @endforelse
        </tbody>
    </table>

    <table class="sign">
        <tr>
            <td>
                <div>Mengetahui,</div>
                <div>{{ $inst->principal_title ?? \App\Models\Institution::principalTitleForLevel($inst->level ?? null) }}</div>
                <div class="space"></div>
                <div><strong>{{ $inst->principal_name ?? '........................' }}</strong></div>
                @if(!empty($inst->principal_nip))
                    <div class="muted">NIP. {{ $inst->principal_nip }}</div>
                @endif
            </td>
            <td>
                <div>{{ $inst->district ?? '' }}, {{ now()->translatedFormat('d F Y') }}</div>
                <div>Guru Piket / Koordinator</div>
                <div class="space"></div>
                <div><strong>........................</strong></div>
            </td>
        </tr>
    </table>

    <div class="footer">
        Dicetak otomatis dari sistem ISS · {{ $generated_at }}
    </div>
</body>
</html>
