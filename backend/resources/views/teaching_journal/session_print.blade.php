<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Lembar Jurnal Mengajar — {{ $institution->name ?? 'Institusi' }}</title>
    <style>
        @page { margin: 1.2cm; size: A4 portrait; }
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 9pt;
            color: #111;
            line-height: 1.35;
        }
        .header { text-align: center; margin: 2px 0 8px; }
        .header h1 {
            font-size: 13pt;
            font-weight: bold;
            margin: 0;
            text-transform: uppercase;
        }
        .header .sub {
            font-size: 9pt;
            margin: 3px 0 0;
            color: #333;
        }
        .meta {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
            font-size: 8.5pt;
        }
        .meta td {
            border: 1px solid #cbd5e1;
            padding: 4px 6px;
            vertical-align: top;
        }
        .meta .lbl { width: 18%; color: #475569; font-weight: bold; background: #f8fafc; }
        .section-title {
            font-size: 10pt;
            font-weight: bold;
            margin: 10px 0 5px;
            text-transform: uppercase;
        }
        .journal-box {
            border: 1px solid #cbd5e1;
            padding: 7px 8px;
            margin-bottom: 8px;
            min-height: 36px;
            white-space: pre-wrap;
        }
        .muted { color: #64748b; font-style: italic; }
        .stats {
            margin-bottom: 8px;
            padding: 6px 8px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            font-size: 8pt;
        }
        .stats table { width: auto; border: none; }
        .stats td { border: none; padding: 2px 10px 2px 0; }
        .stats td.label { font-weight: bold; }
        table.data {
            width: calc(100% - 2px);
            max-width: calc(100% - 2px);
            border-collapse: collapse;
            font-size: 8pt;
        }
        table.data th, table.data td {
            border: 1px solid #64748b;
            padding: 3px 4px;
            vertical-align: middle;
        }
        table.data th {
            background: #e2e8f0;
            font-weight: bold;
            text-align: center;
        }
        table.data td.center, table.data td.num { text-align: center; }
        table.data td.name { text-align: left; }
        .footer {
            margin-top: 8px;
            font-size: 7pt;
            text-align: center;
            color: #64748b;
        }
        .standard-signature-wrap { margin-top: 18px; }
        .standard-signature-space { height: 48px; }
        .standard-signature-place,
        .standard-signature-role { font-size: 9px; }
        .standard-signature-name { font-size: 10px; }
        @include('partials.print-letterhead-styles')
        @include('partials.print-signature-styles')
    </style>
</head>
<body>
    @php
        $formatScore = function ($value) {
            if ($value === null || $value === '') {
                return '—';
            }
            $n = (float) $value;
            return abs($n - round($n)) < 0.05 ? (string) (int) round($n) : number_format($n, 1, ',', '');
        };
        $formatDate = function ($iso) {
            try {
                return \Carbon\Carbon::parse($iso)->locale('id')->isoFormat('dddd, D MMMM YYYY');
            } catch (\Throwable $e) {
                return $iso;
            }
        };
        $sessionCount = count($sessions);
    @endphp

    @foreach ($sessions as $i => $session)
        <div class="session-block" @if($i < $sessionCount - 1) style="page-break-after: always;" @endif>
            @include('partials.print-letterhead', ['institution' => $institution])

            <div class="header">
                <h1>Lembar Jurnal Mengajar</h1>
                <p class="sub">{{ $formatDate($date) }}@if(!empty($semester_name)) · {{ $semester_name }}@endif</p>
            </div>

            <table class="meta">
                <tr>
                    <td class="lbl">Mata Pelajaran</td>
                    <td>{{ $session['subject_name'] ?: '—' }}</td>
                    <td class="lbl">Kelas</td>
                    <td>{{ $session['class_name'] ?: '—' }}</td>
                </tr>
                <tr>
                    <td class="lbl">Jam ke</td>
                    <td>
                        {{ $session['period_label'] ?: '—' }}
                        @if(!empty($session['start_time']))
                            ({{ $session['start_time'] }}@if(!empty($session['end_time']))–{{ $session['end_time'] }}@endif)
                        @endif
                    </td>
                    <td class="lbl">Ruang</td>
                    <td>{{ $session['room_name'] ?: '—' }}</td>
                </tr>
                <tr>
                    <td class="lbl">Guru Mapel</td>
                    <td colspan="3">
                        {{ $teacher?->name ?: '—' }}
                        @if(!empty($teacher?->nip)) (NIP. {{ $teacher->nip }})@endif
                    </td>
                </tr>
            </table>

            <div class="section-title">Materi yang diajarkan</div>
            <div class="journal-box">
                @if(!empty($session['journal']['material_taught']))
                    {{ $session['journal']['material_taught'] }}
                @else
                    <span class="muted">Belum diisi</span>
                @endif
            </div>

            @if(!empty($session['journal']['attendance_notes']) || !empty($session['journal']['notes']))
                <table class="meta">
                    @if(!empty($session['journal']['attendance_notes']))
                        <tr>
                            <td class="lbl">Catatan absensi</td>
                            <td colspan="3">{{ $session['journal']['attendance_notes'] }}</td>
                        </tr>
                    @endif
                    @if(!empty($session['journal']['notes']))
                        <tr>
                            <td class="lbl">Catatan lain</td>
                            <td colspan="3">{{ $session['journal']['notes'] }}</td>
                        </tr>
                    @endif
                </table>
            @endif

            <div class="section-title">Absensi siswa</div>
            <div class="stats">
                <table>
                    <tr>
                        <td class="label">Siswa:</td><td>{{ $session['attendance']['student_count'] ?? 0 }}</td>
                        <td class="label">Tercatat:</td><td>{{ $session['attendance']['recorded'] ?? 0 }}</td>
                        <td class="label">Hadir:</td><td>{{ $session['attendance']['counts']['hadir'] ?? 0 }}</td>
                        <td class="label">Alpha:</td><td>{{ $session['attendance']['counts']['alpha'] ?? 0 }}</td>
                        <td class="label">Izin:</td><td>{{ $session['attendance']['counts']['izin'] ?? 0 }}</td>
                        <td class="label">Sakit:</td><td>{{ $session['attendance']['counts']['sakit'] ?? 0 }}</td>
                        <td class="label">Dinas Luar:</td><td>{{ $session['attendance']['counts']['dinas_luar'] ?? 0 }}</td>
                    </tr>
                </table>
            </div>

            <table class="data">
                <thead>
                    <tr>
                        <th style="width:28px;">No</th>
                        <th style="width:70px;">NIS</th>
                        <th>Nama Siswa</th>
                        <th style="width:70px;">Status</th>
                        <th style="width:90px;">Ket.</th>
                        @foreach ($session['grade_columns'] as $col)
                            <th style="width:42px;">P{{ $col }}</th>
                        @endforeach
                        @if(!empty($session['grade_columns']))
                            <th style="width:52px;">Nilai Akhir</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @forelse ($session['students'] as $idx => $row)
                        <tr>
                            <td class="num">{{ $idx + 1 }}</td>
                            <td class="center">{{ $row['nis'] ?: '—' }}</td>
                            <td class="name">{{ $row['name'] }}</td>
                            <td class="center">{{ $row['status_label'] }}</td>
                            <td>{{ $row['attendance_notes'] ?: '' }}</td>
                            @foreach ($session['grade_columns'] as $col)
                                <td class="center">{{ $formatScore($row['grades'][$col] ?? null) }}</td>
                            @endforeach
                            @if(!empty($session['grade_columns']))
                                <td class="center">{{ $formatScore($row['nilai_akhir'] ?? null) }}</td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td class="center" colspan="{{ 5 + count($session['grade_columns']) + (!empty($session['grade_columns']) ? 1 : 0) }}">
                                Tidak ada siswa aktif di kelas ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            @if(empty($session['grade_columns']))
                <p class="muted" style="margin:6px 0 0;">Nilai harian pertemuan ini belum diisi.</p>
            @endif

            <div class="footer">
                Dicetak pada {{ $printed_at }}
                @if(!empty($printed_by))
                    · oleh {{ $printed_by }}
                @endif
                · {{ $session['attendance']['student_count'] ?? 0 }} siswa
            </div>

            <div class="standard-signature-wrap">
                <div class="standard-signature-left">
                    @include('partials.print-signature', [
                        'institution' => $institution,
                        'show_place_date' => false,
                    ])
                </div>
                <div class="standard-signature-right">
                    @include('partials.print-signature', [
                        'institution' => $institution,
                        'role' => 'Guru Mata Pelajaran',
                        'name' => $teacher?->name ?? '',
                        'nip' => $teacher?->nip ?? '',
                        'date' => $signature_date ?? now()->locale('id')->translatedFormat('d F Y'),
                        'show_place_date' => true,
                    ])
                </div>
            </div>
        </div>
    @endforeach
</body>
</html>
