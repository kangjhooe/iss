<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Rekap Nilai Kelas — {{ $class_name }} — {{ $semester_name }}</title>
    <style>
        @page { margin: 1cm 0.8cm; size: A4 landscape; }
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 8pt;
            color: #111;
            line-height: 1.3;
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
        .meta .lbl { width: 16%; color: #475569; font-weight: bold; background: #f8fafc; }
        table.data {
            width: calc(100% - 2px);
            max-width: calc(100% - 2px);
            border-collapse: collapse;
            font-size: {{ count($subjects) > 12 ? '6.5pt' : (count($subjects) > 8 ? '7pt' : '7.5pt') }};
        }
        table.data th, table.data td {
            border: 1px solid #64748b;
            padding: 2px 3px;
            vertical-align: middle;
        }
        table.data th {
            background: #e2e8f0;
            font-weight: bold;
            text-align: center;
        }
        table.data th.subject {
            writing-mode: horizontal-tb;
            max-width: 52px;
            word-wrap: break-word;
            font-size: 0.92em;
        }
        table.data td.center { text-align: center; }
        table.data td.num { text-align: center; width: 20px; }
        table.data td.rank {
            text-align: center;
            font-weight: bold;
            width: 28px;
        }
        table.data td.name { text-align: left; white-space: nowrap; }
        table.data td.avg { text-align: center; font-weight: bold; }
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
        <h1>Rekap Nilai Kelas</h1>
        <p class="sub">{{ $class_name }} — {{ $semester_name }}</p>
    </div>

    <table class="meta">
        <tr>
            <td class="lbl">Kelas</td>
            <td>{{ $class_name }}@if(!empty($grade)) (Tingkat {{ $grade }})@endif</td>
            <td class="lbl">Semester</td>
            <td>{{ $semester_name }}</td>
        </tr>
        <tr>
            <td class="lbl">Jumlah Mapel</td>
            <td>{{ count($subjects) }}</td>
            <td class="lbl">Jumlah Siswa</td>
            <td>{{ count($rows) }}</td>
        </tr>
        <tr>
            <td class="lbl">Wali Kelas</td>
            <td colspan="3">{{ $wali_kelas?->name ?: '—' }}@if(!empty($wali_kelas?->nip)) (NIP. {{ $wali_kelas->nip }})@endif</td>
        </tr>
    </table>

    <table class="data">
        <thead>
            <tr>
                <th>No</th>
                <th>NIS</th>
                <th>Nama</th>
                @foreach($subjects as $subject)
                    <th class="subject">
                        {{ $subject['code'] ?: ($subject['name'] ?? '-') }}
                        @if(isset($subject['kkm']) && $subject['kkm'] !== null)
                            <div style="font-weight:normal;font-size:0.85em;color:#475569;">KKM {{ $subject['kkm'] }}</div>
                        @endif
                    </th>
                @endforeach
                <th>Rata-rata</th>
                <th>Ranking</th>
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $i => $row)
                @php
                    $gradesBySubject = [];
                    foreach ($row['subjects'] ?? [] as $entry) {
                        $gradesBySubject[(int) ($entry['subject_id'] ?? 0)] = $entry;
                    }
                @endphp
                <tr>
                    <td class="num">{{ $i + 1 }}</td>
                    <td class="center">{{ $row['student']['nis'] ?? '' }}</td>
                    <td class="name">{{ $row['student']['name'] ?? '' }}</td>
                    @foreach($subjects as $subject)
                        @php $entry = $gradesBySubject[(int) ($subject['id'] ?? 0)] ?? null; @endphp
                        <td class="center">
                            {{ $entry['nilai_akhir'] ?? '' }}
                            @if(!empty($entry['predicate']))
                                <div style="font-size:0.85em;color:#475569;">{{ $entry['predicate'] }}@if(!empty($entry['tuntas_label'])) · {{ $entry['tuntas_label'] }}@endif</div>
                            @endif
                        </td>
                    @endforeach
                    <td class="avg">{{ $row['average'] ?? '' }}</td>
                    <td class="rank">{{ $row['rank'] ?? '—' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="{{ 5 + count($subjects) }}" class="center">Belum ada data siswa</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    @if(count($subjects))
        <p class="legend">
            Kode mapel:
            @foreach($subjects as $idx => $subject)
                {{ $subject['code'] ?: ($subject['name'] ?? '-') }} = {{ $subject['name'] ?? '-' }}@if(isset($subject['kkm']) && $subject['kkm'] !== null) (KKM {{ $subject['kkm'] }})@endif{{ $idx < count($subjects) - 1 ? '; ' : '' }}
            @endforeach
        </p>
    @endif

    <p class="legend">
        Ranking dihitung dari rata-rata nilai akhir semua mapel (nilai tertinggi = ranking 1).
        Siswa dengan rata-rata sama mendapat ranking sama.
        Predikat: &lt;KKM = D; di atas KKM dibagi C, B, A.
    </p>
    <div class="footer">
        Dicetak pada {{ $printed_at }}
        @if(!empty($printed_by))
            · oleh {{ $printed_by }}
        @endif
        · {{ count($rows) }} siswa · {{ count($subjects) }} mapel
    </div>

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
                'role' => 'Wali Kelas',
                'name' => $wali_kelas?->name ?? '',
                'nip' => $wali_kelas?->nip ?? '',
                'date' => $signature_date ?? now()->locale('id')->translatedFormat('d F Y'),
                'show_place_date' => true,
            ])
        </div>
    </div>
</body>
</html>
