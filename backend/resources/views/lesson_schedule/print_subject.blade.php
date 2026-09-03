<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>{{ $title }} - {{ $subtitle ?? '' }}</title>
    <style>
        @page { margin: 1.1cm 1.2cm; size: A4 landscape; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 9pt; line-height: 1.3; color: #000; }
        .header { text-align: center; margin: 4px 0 10px; }
        .header h1 { font-size: 13pt; font-weight: bold; margin: 0 0 2px; text-transform: uppercase; }
        .header h2 { font-size: 11pt; font-weight: bold; margin: 0 0 4px; }
        .meta { font-size: 8.5pt; margin-bottom: 8px; color: #222; text-align: center; }
        .meta span { margin: 0 8px; }
        table.data {
            width: calc(100% - 2px);
            max-width: calc(100% - 2px);
            border-collapse: collapse;
            font-size: 8.5pt;
        }
        table.data th, table.data td {
            border: 1px solid #222;
            padding: 5px 6px;
            text-align: left;
        }
        table.data th {
            background: #e8e8e8;
            font-weight: bold;
            text-align: center;
        }
        table.data td.num, table.data td.center { text-align: center; }
        .empty-msg { text-align: center; padding: 24px; color: #666; }
        .footer { margin-top: 8px; font-size: 7pt; text-align: center; color: #666; }
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

    <table class="data">
        <thead>
            <tr>
                <th style="width:36px;">No</th>
                <th>Kelas</th>
                <th style="width:90px;">Hari</th>
                <th style="width:60px;">Jam ke</th>
                <th>Guru</th>
                <th>Ruangan</th>
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $index => $row)
                <tr>
                    <td class="num">{{ $index + 1 }}</td>
                    <td>{{ $row['class_name'] }}</td>
                    <td class="center">{{ $row['day_name'] }}</td>
                    <td class="center">{{ $row['period'] }}</td>
                    <td>{{ $row['teacher_name'] }}</td>
                    <td>{{ $row['room_name'] }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="empty-msg">Belum ada jadwal untuk mata pelajaran ini.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="standard-signature-wrap">
        <div class="standard-signature-left">
            @include('partials.print-signature', [
                'institution' => $institution,
                'role' => 'Wakil Kepala Sekolah Bidang Kurikulum',
                'name' => $waka_kurikulum?->name ?? '',
                'nip' => $waka_kurikulum?->nip ?? '',
                    'date' => ($as_of_date ?? now())->locale('id')->translatedFormat('d F Y'),
                    'as_of_date' => $as_of_date ?? null,
            ])
        </div>
        <div class="standard-signature-right">
            @include('partials.print-signature', [
                'institution' => $institution,
                    'date' => ($as_of_date ?? now())->locale('id')->translatedFormat('d F Y'),
                    'as_of_date' => $as_of_date ?? null,
            ])
        </div>
    </div>

    @include('partials.print-document-footer', ['footer_suffix' => $rows->count() ? '· '.$rows->count().' slot' : null])
</body>
</html>
