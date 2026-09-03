<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Buku Nilai — {{ $subject_name }} — {{ $class_name }}</title>
    <style>
        @page { margin: 1.1cm 1cm; size: A4 landscape; }
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 8.5pt;
            color: #111;
            line-height: 1.35;
        }
        .header { text-align: center; margin: 4px 0 8px; }
        .header h1 {
            font-size: 12pt;
            font-weight: bold;
            margin: 0;
            text-transform: uppercase;
            letter-spacing: 0.3px;
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
            font-size: 8pt;
        }
        .meta td {
            border: 1px solid #cbd5e1;
            padding: 4px 6px;
            vertical-align: top;
        }
        .meta .lbl { width: 18%; color: #475569; font-weight: bold; background: #f8fafc; }
        table.data {
            width: calc(100% - 2px);
            max-width: calc(100% - 2px);
            border-collapse: collapse;
            font-size: 7.5pt;
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
        table.data td.center { text-align: center; }
        table.data td.num { text-align: center; width: 22px; }
        table.data td.rank {
            text-align: center;
            font-weight: bold;
            width: 28px;
        }
        table.data td.name { text-align: left; }
        .footer {
            margin-top: 8px;
            font-size: 7pt;
            text-align: center;
            color: #64748b;
        }
        .legend {
            margin-top: 6px;
            font-size: 7pt;
            color: #475569;
        }
        @include('partials.print-letterhead-styles')
        @include('partials.print-signature-styles')
        .standard-signature-wrap { margin-top: 18px; }
        .standard-signature-space { height: 48px; }
        .standard-signature-place,
        .standard-signature-role { font-size: 9px; }
        .standard-signature-name { font-size: 10px; }
        .standard-signature-nip { font-size: 8px; }
    </style>
</head>
<body>
    @include('partials.print-letterhead', ['institution' => $institution])

    <div class="header">
        <h1>Buku Nilai Mata Pelajaran</h1>
        <p class="sub">{{ $subject_name }} — {{ $class_name }} — {{ $semester_name }}</p>
    </div>

    <table class="meta">
        <tr>
            <td class="lbl">Mata Pelajaran</td>
            <td>{{ $subject_name }}</td>
            <td class="lbl">Kelas</td>
            <td>{{ $class_name }}@if($grade) (Tingkat {{ $grade }})@endif</td>
        </tr>
        <tr>
            <td class="lbl">Semester</td>
            <td>{{ $semester_name }}</td>
            <td class="lbl">KKM</td>
            <td>{{ $kkm !== null ? $kkm : 'Belum diisi' }}</td>
        </tr>
        <tr>
            <td class="lbl">Bobot</td>
            <td colspan="3">
                Penilaian {{ $weights['penilaian'] ?? 40 }}%
                · UTS {{ $weights['uts'] ?? 30 }}%
                · UAS {{ $weights['uas'] ?? 30 }}%
            </td>
        </tr>
        <tr>
            <td class="lbl">Guru Mapel</td>
            <td colspan="3">{{ $teacher?->name ?: '—' }}@if(!empty($teacher?->nip)) (NIP. {{ $teacher->nip }})@endif</td>
        </tr>
    </table>

    <table class="data">
        <thead>
            <tr>
                <th>No</th>
                <th>Peringkat</th>
                <th>Nama</th>
                <th>NIS</th>
                @for($i = 1; $i <= $assessment_count; $i++)
                    <th>P{{ $i }}</th>
                @endfor
                <th>Rata P</th>
                <th>UTS</th>
                <th>UAS</th>
                <th>Nilai Akhir</th>
                <th>Predikat</th>
                <th>Ketuntasan</th>
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $i => $row)
                @php
                    $penilaian = (array) ($row['penilaian'] ?? []);
                @endphp
                <tr>
                    <td class="num">{{ $i + 1 }}</td>
                    <td class="rank">{{ $row['rank'] ?? '—' }}</td>
                    <td class="name">{{ $row['student']['name'] ?? '' }}</td>
                    <td class="center">{{ $row['student']['nis'] ?? ($row['student']['nisn'] ?? '') }}</td>
                    @for($n = 1; $n <= $assessment_count; $n++)
                        <td class="center">{{ $penilaian[(string) $n] ?? $penilaian[$n] ?? '' }}</td>
                    @endfor
                    <td class="center">{{ $row['rata_penilaian'] ?? '' }}</td>
                    <td class="center">{{ $row['uts'] ?? '' }}</td>
                    <td class="center">{{ $row['uas'] ?? '' }}</td>
                    <td class="center"><strong>{{ $row['nilai_akhir'] ?? '' }}</strong></td>
                    <td class="center">{{ $row['predicate'] ?? '' }}</td>
                    <td class="center">{{ $row['tuntas_label'] ?? '' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="{{ 9 + $assessment_count }}" class="center">Belum ada data siswa</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <p class="legend">
        Peringkat dihitung dari nilai akhir (nilai tertinggi = peringkat 1). Siswa dengan nilai sama mendapat peringkat sama.
        Nilai akhir = rata penilaian × bobot + UTS × bobot + UAS × bobot.
    </p>
    <div class="standard-signature-wrap">
        <div class="standard-signature-left">
            @include('partials.print-signature', [
                'institution' => $institution,
                'show_place_date' => false,
                'as_of_date' => $as_of_date ?? null,
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

    @include('partials.print-document-footer', ['footer_suffix' => '· '.count($rows).' siswa'])
</body>
</html>
