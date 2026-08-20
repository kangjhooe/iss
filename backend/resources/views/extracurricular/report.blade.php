<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Laporan Ekstrakurikuler — {{ $data['extracurricular']['name'] ?? '' }}</title>
    <style>
        @page { margin: 1.4cm 1.2cm; size: A4 portrait; }
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 10px;
            color: #1a1a1a;
            line-height: 1.4;
        }
        .kop {
            width: 100%;
            border-bottom: 2.5px solid #0f172a;
            padding-bottom: 8px;
            margin-bottom: 3px;
        }
        .kop-inner { width: 100%; }
        .kop-inner td { vertical-align: middle; border: none; padding: 0; }
        .kop-text { text-align: center; }
        .kop-text .inst-name {
            font-size: 13px;
            font-weight: bold;
            text-transform: uppercase;
            margin: 0 0 2px;
        }
        .kop-text .inst-foundation {
            font-size: 9.5px;
            font-weight: 600;
            text-transform: uppercase;
            margin: 0 0 2px;
        }
        .kop-text .inst-meta { font-size: 8px; color: #444; margin: 0; }
        .kop-line {
            border-bottom: 0.8px solid #0f172a;
            margin-bottom: 12px;
            height: 3px;
        }
        .doc-title { text-align: center; margin: 6px 0 10px; }
        .doc-title h1 {
            font-size: 12px;
            margin: 0;
            text-transform: uppercase;
            letter-spacing: 0.4px;
        }
        .doc-title .subtitle { font-size: 10px; color: #333; margin: 3px 0 0; }
        .info-box {
            width: calc(100% - 2px);
            max-width: calc(100% - 2px);
            margin: 0 0 12px;
            border: 1px solid #cbd5e1;
            background: #f8fafc;
        }
        .info-box td {
            border: none;
            padding: 4px 8px;
            font-size: 9px;
            vertical-align: top;
        }
        .info-box .lbl { width: 26%; color: #475569; font-weight: bold; }
        .stats {
            width: calc(100% - 2px);
            max-width: calc(100% - 2px);
            margin-bottom: 12px;
            border-collapse: collapse;
        }
        .stats td {
            width: 20%;
            text-align: center;
            border: 1px solid #cbd5e1;
            padding: 8px 4px;
            background: #f8fafc;
        }
        .stats .val { font-size: 14px; font-weight: bold; color: #0f172a; }
        .stats .lbl { font-size: 8px; color: #64748b; text-transform: uppercase; margin-top: 2px; }
        .section-title {
            font-size: 10px;
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
            border: 1px solid #94a3b8;
            padding: 4px 5px;
            font-size: 8.5px;
            vertical-align: top;
        }
        table.data th {
            background: #e2e8f0;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 7.5px;
        }
        table.data tr.group-row td {
            background: #e2e8f0;
            font-weight: bold;
            font-size: 8px;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }
        .right { text-align: right; }
        .muted { color: #64748b; }
        table.matrix th, table.matrix td { font-size: 7.5px; padding: 3px 2px; }
        table.matrix .st-hadir { color: #047857; font-weight: bold; }
        table.matrix .st-izin { color: #c2410c; }
        table.matrix .st-sakit { color: #2563eb; }
        table.matrix .st-alpha { color: #dc2626; font-weight: bold; }
        table.matrix .st-empty { color: #94a3b8; }
        .footer {
            margin-top: 18px;
            width: calc(100% - 2px);
            max-width: calc(100% - 2px);
            border-collapse: collapse;
        }
        .footer td { border: none; vertical-align: top; font-size: 9px; }
        .sig { text-align: center; width: 42%; }
        .sig-space { height: 48px; }
        @include('partials.print-letterhead-styles')
        .cell-note { color: #555; font-size: 8px; margin-top: 2px; }
    </style>
</head>
<body>
    @php
        $ekskul = $data['extracurricular'] ?? [];
        $period = $data['period'] ?? [];
        $att = $data['attendance'] ?? [];
        $grades = $data['grades'] ?? [];
        $perStudent = $data['per_student'] ?? [];
        $sessions = $data['sessions'] ?? [];
        $matrixSessions = $data['attendance_matrix']['sessions'] ?? [];
        $matrixRows = $data['attendance_matrix']['rows'] ?? [];
        $attendanceGroups = \App\Support\StudentRosterSort::groupRows($matrixRows);
        $perStudentGroups = \App\Support\StudentRosterSort::groupRows($perStudent);
        $instName = $institution->name ?? 'Institusi';
        $instAddr = collect([
            $institution->address ?? null,
            $institution->district ?? null,
            $institution->city ?? null,
        ])->filter()->implode(', ');
        $instContact = collect([
            !empty($institution->phone) ? 'Telp. '.$institution->phone : null,
            !empty($institution->email) ? $institution->email : null,
        ])->filter()->implode(' · ');
        $supervisorNip = $ekskul['supervisor']['nip'] ?? $ekskul['supervisor']['nuptk'] ?? null;
    @endphp

    @include('partials.print-letterhead', ['institution' => $institution])

    <div class="doc-title">
        <h1>Laporan Kegiatan Ekstrakurikuler</h1>
        <p class="subtitle">{{ $ekskul['name'] ?? '—' }}</p>
    </div>

    <table class="info-box">
        <tr>
            <td class="lbl">Nama Ekskul</td>
            <td>{{ $ekskul['name'] ?? '—' }}</td>
            <td class="lbl">Periode</td>
            <td>{{ $period['label'] ?? '—' }}</td>
        </tr>
        <tr>
            <td class="lbl">Pembina</td>
            <td>
                <div>{{ $ekskul['supervisor']['name'] ?? '—' }}</div>
                @if(!empty($ekskul['supervisor']))
                    <div class="cell-note">{{ $supervisorNip ?: 'Tanpa NIP/NUPTK' }}</div>
                @endif
            </td>
            <td class="lbl">KKM</td>
            <td>{{ $data['kkm'] ?? $ekskul['kkm'] ?? '—' }}</td>
        </tr>
        <tr>
            <td class="lbl">Status</td>
            <td>{{ $ekskul['status'] ?? '—' }}</td>
            <td class="lbl">Dicetak</td>
            <td>{{ $printedAt }}@if(!empty($printedBy)) · {{ $printedBy }}@endif</td>
        </tr>
    </table>

    <table class="stats">
        <tr>
            <td>
                <div class="val">{{ $data['participants_count'] ?? 0 }}</div>
                <div class="lbl">Peserta</div>
            </td>
            <td>
                <div class="val">{{ $data['sessions_count'] ?? 0 }}</div>
                <div class="lbl">Pertemuan</div>
            </td>
            <td>
                <div class="val">{{ $att['hadir'] ?? 0 }}</div>
                <div class="lbl">Hadir</div>
            </td>
            <td>
                <div class="val">{{ isset($att['hadir_pct']) ? $att['hadir_pct'].'%' : '—' }}</div>
                <div class="lbl">% Kehadiran</div>
            </td>
            <td>
                <div class="val">{{ $grades['average_score'] ?? '—' }}</div>
                <div class="lbl">Rata-rata Nilai</div>
            </td>
        </tr>
    </table>

    <div class="section-title">1. Daftar Pertemuan ({{ count($sessions) }})</div>
    @if(count($sessions) === 0)
        <p class="muted">Tidak ada pertemuan pada periode ini.</p>
    @else
        <table class="data">
            <thead>
                <tr>
                    <th class="num" style="width:28px">No</th>
                    <th style="width:72px">Tanggal</th>
                    <th style="width:70px">Jam</th>
                    <th>Materi / Topik</th>
                    <th class="num" style="width:48px">Kehadiran</th>
                </tr>
            </thead>
            <tbody>
                @foreach($sessions as $i => $s)
                    <tr>
                        <td class="num">{{ $i + 1 }}</td>
                        <td>{{ !empty($s['session_date']) ? \Carbon\Carbon::parse($s['session_date'])->locale('id')->isoFormat('D MMM YYYY') : '—' }}</td>
                        <td>
                            @if(!empty($s['start_time']) || !empty($s['end_time']))
                                {{ $s['start_time'] ?? '?' }}–{{ $s['end_time'] ?? '?' }}
                            @else
                                —
                            @endif
                        </td>
                        <td>{{ $s['topic'] ?: '—' }}</td>
                        <td class="num">{{ $s['attendances_count'] ?? 0 }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <div class="section-title">2. Rekap Kehadiran per Pertemuan ({{ count($matrixRows) }} peserta × {{ count($matrixSessions) }} pertemuan)</div>
    @if(count($matrixSessions) === 0 || count($matrixRows) === 0)
        <p class="muted">Belum ada data untuk matriks kehadiran pada periode ini.</p>
    @else
        @php
            $statusCode = ['hadir' => 'H', 'izin' => 'I', 'sakit' => 'S', 'alpha' => 'A'];
        @endphp
        <table class="data matrix">
            <thead>
                <tr>
                    <th class="num" style="width:22px">No</th>
                    <th>Nama</th>
                    <th style="width:48px">Kelas</th>
                    @foreach($matrixSessions as $s)
                        <th class="num" style="width:28px" title="{{ $s['session_date'] ?? '' }}{{ !empty($s['topic']) ? ' · '.$s['topic'] : '' }}">
                            {{ $s['label'] ?? '' }}
                        </th>
                    @endforeach
                    <th class="num">H</th>
                    <th class="num">I</th>
                    <th class="num">S</th>
                    <th class="num">A</th>
                    <th class="num">%</th>
                </tr>
            </thead>
            <tbody>
                @php $no = 1; @endphp
                @foreach($attendanceGroups as $group)
                    <tr class="group-row">
                        <td colspan="{{ 8 + count($matrixSessions) }}">Kelas {{ $group['name'] }} ({{ count($group['rows']) }} siswa)</td>
                    </tr>
                    @foreach($group['rows'] as $r)
                        <tr>
                            <td class="num">{{ $no++ }}</td>
                            <td>{{ $r['name'] ?? '—' }}</td>
                            <td>{{ $r['class']['name'] ?? '—' }}</td>
                            @foreach($matrixSessions as $s)
                                @php $st = $r['statuses'][(string) $s['id']] ?? null; @endphp
                                <td class="num st-{{ $st ?: 'empty' }}">{{ $st ? ($statusCode[$st] ?? substr($st, 0, 1)) : '-' }}</td>
                            @endforeach
                            <td class="num">{{ $r['hadir'] ?? 0 }}</td>
                            <td class="num">{{ $r['izin'] ?? 0 }}</td>
                            <td class="num">{{ $r['sakit'] ?? 0 }}</td>
                            <td class="num">{{ $r['alpha'] ?? 0 }}</td>
                            <td class="num">{{ isset($r['hadir_pct']) ? $r['hadir_pct'] : '—' }}</td>
                        </tr>
                    @endforeach
                @endforeach
            </tbody>
        </table>
        <p class="muted">Kolom tanggal = tanggal pertemuan. H=Hadir, I=Izin, S=Sakit, A=Alpha, -=belum dicatat.</p>
    @endif

    @php
        $gradeMatrix = $data['grade_matrix'] ?? ['sessions' => [], 'rows' => []];
        $gradeSessions = $gradeMatrix['sessions'] ?? [];
        $gradeRows = $gradeMatrix['rows'] ?? [];
        $gradeGroups = \App\Support\StudentRosterSort::groupRows($gradeRows);
    @endphp
    <div class="section-title">3. Rekap Nilai per Pertemuan ({{ count($gradeRows) }} peserta × {{ count($gradeSessions) }} pertemuan)</div>
    @if(count($gradeSessions) === 0 || count($gradeRows) === 0)
        <p class="muted">Belum ada data nilai pertemuan pada periode ini.</p>
    @else
        <table class="data matrix">
            <thead>
                <tr>
                    <th class="num" style="width:22px">No</th>
                    <th>Nama</th>
                    <th style="width:48px">Kelas</th>
                    @foreach($gradeSessions as $s)
                        <th class="num" style="width:28px" title="{{ $s['session_date'] ?? '' }}{{ !empty($s['topic']) ? ' · '.$s['topic'] : '' }}">
                            {{ $s['label'] ?? '' }}
                        </th>
                    @endforeach
                    <th class="num">Jml</th>
                    <th class="num">Rata</th>
                    <th class="num">Akhir</th>
                    <th class="num">Pred.</th>
                </tr>
            </thead>
            <tbody>
                @php $no = 1; @endphp
                @foreach($gradeGroups as $group)
                    <tr class="group-row">
                        <td colspan="{{ 7 + count($gradeSessions) }}">Kelas {{ $group['name'] }} ({{ count($group['rows']) }} siswa)</td>
                    </tr>
                    @foreach($group['rows'] as $r)
                        <tr>
                            <td class="num">{{ $no++ }}</td>
                            <td>{{ $r['name'] ?? '—' }}</td>
                            <td>{{ $r['class']['name'] ?? '—' }}</td>
                            @foreach($gradeSessions as $s)
                                @php $sc = $r['scores'][(string) $s['id']] ?? null; @endphp
                                <td class="num">{{ $sc !== null ? $sc : '-' }}</td>
                            @endforeach
                            <td class="num">{{ $r['graded_sessions'] ?? 0 }}</td>
                            <td class="num">{{ $r['average'] ?? '—' }}</td>
                            <td class="num">{{ $r['score'] ?? '—' }}</td>
                            <td class="num">{{ $r['predicate'] ?? '—' }}</td>
                        </tr>
                    @endforeach
                @endforeach
            </tbody>
        </table>
        <p class="muted">Nilai akhir = rata-rata skor pertemuan yang terisi. Predikat berdasarkan KKM {{ $data['kkm'] ?? '—' }}. Alpha / kosong tidak dihitung.</p>
    @endif

    @if(($period['type'] ?? '') !== 'month')
    <div class="section-title">4. Rekap Ringkas per Siswa ({{ count($perStudent) }})</div>
    @if(count($perStudent) === 0)
        <p class="muted">Belum ada data kehadiran untuk direkap.</p>
    @else
        <table class="data">
            <thead>
                <tr>
                    <th class="num" style="width:24px">No</th>
                    <th>Nama</th>
                    <th style="width:56px">NIS</th>
                    <th style="width:56px">Kelas</th>
                    <th class="num">H</th>
                    <th class="num">I</th>
                    <th class="num">S</th>
                    <th class="num">A</th>
                    <th class="num">% Hadir</th>
                    <th class="num">Nilai</th>
                    <th class="num">Pred.</th>
                </tr>
            </thead>
            <tbody>
                @php $no = 1; @endphp
                @foreach($perStudentGroups as $group)
                    <tr class="group-row">
                        <td colspan="11">Kelas {{ $group['name'] }} ({{ count($group['rows']) }} siswa)</td>
                    </tr>
                    @foreach($group['rows'] as $r)
                        <tr>
                            <td class="num">{{ $no++ }}</td>
                            <td>{{ $r['name'] ?? '—' }}</td>
                            <td>{{ $r['nis'] ?? '—' }}</td>
                            <td>{{ $r['class']['name'] ?? '—' }}</td>
                            <td class="num">{{ $r['hadir'] ?? 0 }}</td>
                            <td class="num">{{ $r['izin'] ?? 0 }}</td>
                            <td class="num">{{ $r['sakit'] ?? 0 }}</td>
                            <td class="num">{{ $r['alpha'] ?? 0 }}</td>
                            <td class="num">{{ isset($r['hadir_pct']) ? $r['hadir_pct'].'%' : '—' }}</td>
                            <td class="num">{{ $r['score'] ?? '—' }}</td>
                            <td class="num">{{ $r['predicate'] ?? '—' }}</td>
                        </tr>
                    @endforeach
                @endforeach
            </tbody>
        </table>
        <p class="muted">Keterangan: H = Hadir, I = Izin, S = Sakit, A = Alpha</p>
    @endif
    @endif

    <table class="footer">
        <tr>
            <td>
                <strong>Ringkasan nilai:</strong><br>
                Dinilai: {{ $grades['graded_count'] ?? 0 }} siswa<br>
                Rata-rata: {{ $grades['average_score'] ?? '—' }}
            </td>
            <td class="sig">
                {{ $institution->city ?? $institution->district ?? '........................' }},
                {{ now()->locale('id')->isoFormat('D MMMM YYYY') }}<br>
                Pembina Ekstrakurikuler
                <div class="sig-space"></div>
                <strong>{{ $ekskul['supervisor']['name'] ?? '___________________' }}</strong>
                <div class="cell-note">NIP. {{ $supervisorNip ?: '___________________' }}</div>
            </td>
        </tr>
    </table>
</body>
</html>
