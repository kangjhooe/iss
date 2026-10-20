<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>
        @if(($filters['type'] ?? null) === 'masuk')
            Laporan Surat Masuk
        @elseif(($filters['type'] ?? null) === 'keluar')
            Laporan Surat Keluar
        @elseif(($filters['type'] ?? null) === 'internal')
            Laporan Surat Internal
        @else
            Laporan Surat Menyurat
        @endif
    </title>
    <style>
        @page { margin: 1.1cm 1.1cm 1.5cm 1.1cm; size: A4 landscape; }
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 8.5pt;
            line-height: 1.35;
            color: #111;
            margin: 0;
            padding: 0;
        }
        .header { text-align: center; margin: 2px 0 10px; }
        .header h1 {
            font-size: 13pt;
            font-weight: bold;
            margin: 0 0 3px;
            letter-spacing: 0.04em;
        }
        .header p { font-size: 8pt; margin: 0; color: #444; }
        .meta {
            margin: 0 0 8px;
            padding: 5px 8px;
            background: #f4f4f4;
            border: 1px solid #ccc;
            font-size: 8pt;
            color: #333;
        }
        table.data {
            width: calc(100% - 2px);
            max-width: calc(100% - 2px);
            border-collapse: collapse;
            table-layout: fixed;
            font-size: 8pt;
        }
        table.data th,
        table.data td {
            border: 1px solid #333;
            padding: 4px 5px;
            text-align: left;
            vertical-align: top;
            word-wrap: break-word;
        }
        table.data th {
            background: #e8e8e8;
            font-weight: bold;
            text-align: center;
            vertical-align: middle;
        }
        table.data thead { display: table-header-group; }
        table.data tr { page-break-inside: avoid; }
        table.data td.num,
        table.data th.num { width: 4%; text-align: center; }
        table.data .col-jenis { width: 16%; }
        table.data .col-nomor { width: 30%; }
        table.data .col-perihal { width: 26%; }
        table.data .col-pihak { width: 16%; }
        table.data .col-tanggal { width: 8%; text-align: center; }
        .empty { text-align: center; padding: 16px; color: #666; }
        .standard-signature-wrap { margin-top: 16px; }
        .standard-signature-space { height: 46px; }
        @include('partials.print-letterhead-styles')
        @include('partials.print-signature-styles')
    </style>
</head>
<body>
    @php
        $typeLabels = [
            'masuk' => 'Surat Masuk',
            'keluar' => 'Surat Keluar',
            'internal' => 'Surat Internal',
        ];
        $statusLabels = [
            'draft' => 'Draft',
            'pending' => 'Menunggu',
            'approved' => 'Disetujui',
            'sent' => 'Terkirim',
            'archived' => 'Diarsipkan',
        ];
        $priorityLabels = [
            'biasa' => 'Biasa',
            'penting' => 'Penting',
            'sangat_penting' => 'Sangat Penting',
        ];
        $reportTitle = isset($filters['type']) && isset($typeLabels[$filters['type']])
            ? 'LAPORAN '.strtoupper($typeLabels[$filters['type']])
            : 'LAPORAN SURAT MENYURAT';
        $partyHeading = match ($filters['type'] ?? null) {
            'masuk' => 'Dari',
            'keluar', 'internal' => 'Kepada',
            default => 'Dari / Kepada',
        };

        $filterParts = [];
        if (!empty($filters['status'])) {
            $filterParts[] = 'Status: '.($statusLabels[$filters['status']] ?? ucfirst($filters['status']));
        }
        if (!empty($filters['priority'])) {
            $filterParts[] = 'Prioritas: '.($priorityLabels[$filters['priority']] ?? ucfirst(str_replace('_', ' ', $filters['priority'])));
        }
        if (!empty($filters['search'])) {
            $filterParts[] = 'Pencarian: '.$filters['search'];
        }
        if (!empty($filters['date_from']) || !empty($filters['date_to'])) {
            $from = !empty($filters['date_from']) ? \Carbon\Carbon::parse($filters['date_from'])->format('d/m/Y') : 'Awal';
            $to = !empty($filters['date_to']) ? \Carbon\Carbon::parse($filters['date_to'])->format('d/m/Y') : 'Akhir';
            $filterParts[] = 'Periode: '.$from.' – '.$to;
        }
    @endphp

    @include('partials.print-letterhead', ['institution' => $institution])

    <div class="header">
        <h1>{{ $reportTitle }}</h1>
        <p>Dicetak pada {{ $generated_at->locale('id')->translatedFormat('d F Y, H:i') }}</p>
    </div>

    @if(count($filterParts))
    <div class="meta">{{ implode('   ·   ', $filterParts) }}</div>
    @endif

    <table class="data">
        <thead>
            <tr>
                <th class="num">No</th>
                <th class="col-jenis">Jenis</th>
                <th class="col-nomor">Nomor Surat</th>
                <th class="col-perihal">Perihal</th>
                <th class="col-pihak">{{ $partyHeading }}</th>
                <th class="col-tanggal">Tanggal</th>
            </tr>
        </thead>
        <tbody>
            @forelse($correspondence as $index => $item)
            <tr>
                <td class="num">{{ $index + 1 }}</td>
                <td>
                    @if($item->letter_type_name)
                        {{ $item->letter_type_abbr }} — {{ $item->letter_type_name }}
                    @else
                        -
                    @endif
                </td>
                <td>{{ $item->letter_number ?? $item->reference_number ?? '-' }}</td>
                <td>{{ $item->subject }}</td>
                <td>{{ $item->type === 'masuk' ? ($item->from ?? '-') : ($item->to ?? '-') }}</td>
                <td class="col-tanggal">{{ $item->date ? $item->date->format('d/m/Y') : '-' }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="6" class="empty">Tidak ada data surat yang ditemukan.</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <div class="standard-signature-wrap">
        <div class="standard-signature-left"></div>
        <div class="standard-signature-right">
            @include('partials.print-signature', [
                'institution' => $institution,
                'date' => $generated_at->locale('id')->translatedFormat('d F Y'),
                'as_of_date' => $as_of_date ?? $generated_at,
            ])
        </div>
    </div>

    @include('partials.print-document-footer', ['footer_suffix' => '· '.count($correspondence).' surat'])
</body>
</html>
