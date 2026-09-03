<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>{{ $title }} - {{ $subtitle ?? '' }}</title>
    <style>
        @page { margin: 1cm 1.1cm; size: A4 landscape; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 9pt; line-height: 1.3; color: #000; }
        .header { text-align: center; margin: 4px 0 10px; }
        .header h1 { font-size: 13pt; font-weight: bold; margin: 0 0 2px; text-transform: uppercase; }
        .header h2 { font-size: 11pt; font-weight: bold; margin: 0 0 4px; }
        .meta { font-size: 8.5pt; margin-bottom: 8px; color: #222; text-align: center; }
        .meta span { margin: 0 8px; }
        table.matrix {
            width: calc(100% - 2px);
            max-width: calc(100% - 2px);
            border-collapse: collapse;
            table-layout: fixed;
            font-size: 7.5pt;
        }
        table.matrix th, table.matrix td {
            border: 1px solid #222;
            padding: 4px 3px;
            vertical-align: top;
        }
        table.matrix th {
            background: #e8e8e8;
            font-weight: bold;
            text-align: center;
            font-size: 8pt;
        }
        table.matrix td.period {
            width: 42px;
            text-align: center;
            font-weight: bold;
            background: #f5f5f5;
            vertical-align: middle;
        }
        table.matrix td.cell { text-align: center; min-height: 28px; }
        table.matrix td.cell-out { background: #f0f0f0; color: #999; text-align: center; vertical-align: middle; }
        .line-subject { font-weight: bold; font-size: 7.5pt; line-height: 1.25; }
        .line-meta { font-size: 6.8pt; color: #333; line-height: 1.2; }
        .empty { color: #bbb; }
        .footer { margin-top: 8px; font-size: 7pt; text-align: center; color: #666; }
        .sign-note { font-size: 8pt; margin-top: 6px; color: #444; }
        @include('partials.print-letterhead-styles')
        @include('partials.print-signature-styles')
        .standard-signature-wrap { margin-top: 18px; }
        .standard-signature-space { height: 48px; }
    </style>
</head>
<body>
    @include('partials.print-letterhead', ['institution' => $institution])

    <div class="header">
        <h1>{{ $title }}</h1>
        @if(!empty($subtitle))
            <h2>{{ $subtitle }}</h2>
        @endif
    </div>

    <div class="meta">
        <span>Semester: {{ $semester->name }}</span>
        @if($semester->academicYear)
            <span>Tahun Ajaran: {{ $semester->academicYear->name }}</span>
        @endif
        @foreach($meta_lines ?? [] as $line)
            <span>{{ $line }}</span>
        @endforeach
    </div>

    @if(empty($active_days) || $max_periods < 1)
        <p style="text-align:center;padding:24px;color:#666;">Belum ada hari aktif / jadwal untuk dicetak. Atur template jadwal terlebih dahulu.</p>
    @else
        <table class="matrix">
            <thead>
                <tr>
                    <th style="width:42px;">Jam</th>
                    @foreach($active_days as $day)
                        <th>{{ $day['day_name'] ?? '' }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @for($period = 1; $period <= $max_periods; $period++)
                    <tr>
                        <td class="period">Ke-{{ $period }}</td>
                        @foreach($active_days as $day)
                            @php
                                $dayOfWeek = (int) $day['day_of_week'];
                                $cell = $grid[$period][$dayOfWeek] ?? ['out' => true, 'lines' => []];
                            @endphp
                            @if(!empty($cell['out']))
                                <td class="cell-out">—</td>
                            @else
                                <td class="cell">
                                    @if(empty($cell['lines']))
                                        <span class="empty">&nbsp;</span>
                                    @else
                                        @foreach($cell['lines'] as $i => $line)
                                            <div class="{{ $i === 0 ? 'line-subject' : 'line-meta' }}">{{ $line }}</div>
                                        @endforeach
                                    @endif
                                </td>
                            @endif
                        @endforeach
                    </tr>
                @endfor
            </tbody>
        </table>
    @endif

    <div class="standard-signature-wrap">
        <div class="standard-signature-left">
            @if(!empty($left_signer))
                @include('partials.print-signature', [
                    'institution' => $institution,
                    'role' => $left_signer['role'] ?? null,
                    'name' => $left_signer['name'] ?? null,
                    'nip' => $left_signer['nip'] ?? null,
                    'date' => ($as_of_date ?? now())->locale('id')->translatedFormat('d F Y'),
                    'as_of_date' => $as_of_date ?? null,
                    'show_place_date' => $left_signer['show_place_date'] ?? false,
                ])
            @else
                @include('partials.print-signature', [
                    'institution' => $institution,
                    'role' => 'Wakil Kepala Sekolah Bidang Kurikulum',
                    'name' => $waka_kurikulum?->name ?? '',
                    'nip' => $waka_kurikulum?->nip ?? '',
                    'date' => ($as_of_date ?? now())->locale('id')->translatedFormat('d F Y'),
                    'as_of_date' => $as_of_date ?? null,
                ])
            @endif
        </div>
        <div class="standard-signature-right">
            @if(!empty($right_signer))
                @include('partials.print-signature', [
                    'institution' => $institution,
                    'role' => $right_signer['role'] ?? null,
                    'name' => $right_signer['name'] ?? '',
                    'nip' => $right_signer['nip'] ?? '',
                    'date' => ($as_of_date ?? now())->locale('id')->translatedFormat('d F Y'),
                    'as_of_date' => $as_of_date ?? null,
                    'show_place_date' => $right_signer['show_place_date'] ?? true,
                ])
            @else
                @include('partials.print-signature', [
                    'institution' => $institution,
                    'date' => ($as_of_date ?? now())->locale('id')->translatedFormat('d F Y'),
                    'as_of_date' => $as_of_date ?? null,
                ])
            @endif
        </div>
    </div>

    @include('partials.print-document-footer')
</body>
</html>
