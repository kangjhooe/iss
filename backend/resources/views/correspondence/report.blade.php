<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Surat Menyurat</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 15mm;
        }
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 11px;
            color: #333;
            margin: 0;
            padding: 10px;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #333;
            padding-bottom: 15px;
        }
        .header h1 {
            margin: 0;
            font-size: 20px;
            font-weight: bold;
        }
        .header p {
            margin: 5px 0;
            font-size: 11px;
        }
        .info-section {
            margin-bottom: 15px;
        }
        .info-section table {
            width: calc(100% - 2px);
            max-width: calc(100% - 2px);
            border-collapse: collapse;
        }
        .info-section td {
            padding: 8px;
            font-size: 11px;
            vertical-align: top;
        }
        .info-section td:first-child {
            font-weight: bold;
            width: 150px;
            background-color: #f2f2f2;
        }
        table {
            width: calc(100% - 2px);
            max-width: calc(100% - 2px);
            border-collapse: collapse;
            margin-top: 15px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 6px 8px;
            text-align: left;
            font-size: 9px;
        }
        th:nth-child(1), td:nth-child(1) {
            width: 3%;
        }
        th:nth-child(2), td:nth-child(2) {
            width: 12%;
        }
        th:nth-child(3), td:nth-child(3) {
            width: 15%;
        }
        th:nth-child(4), td:nth-child(4) {
            width: 25%;
        }
        th:nth-child(5), td:nth-child(5) {
            width: 15%;
        }
        th:nth-child(6), td:nth-child(6) {
            width: 8%;
        }
        th:nth-child(7), td:nth-child(7) {
            width: 10%;
        }
        th:nth-child(8), td:nth-child(8) {
            width: 12%;
        }
        .info-section td:last-child {
            border: 1px solid #ddd;
            background-color: #f9f9f9;
            min-height: 20px;
        }
        th {
            background-color: #f2f2f2;
            font-weight: bold;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .footer {
            margin-top: 20px;
            text-align: right;
            font-size: 9px;
            color: #666;
        }
        .no-data {
            text-align: center;
            padding: 40px;
            color: #999;
            font-style: italic;
        }
        @include('partials.print-letterhead-styles')
        @include('partials.print-signature-styles')
    </style>
</head>
<body>
    @include('partials.print-letterhead', ['institution' => $institution])
    <div class="header">
        <h1>
            @if(isset($filters['type']))
                @if($filters['type'] === 'masuk')
                    LAPORAN SURAT MASUK
                @elseif($filters['type'] === 'keluar')
                    LAPORAN SURAT KELUAR
                @elseif($filters['type'] === 'internal')
                    LAPORAN SURAT INTERNAL
                @else
                    LAPORAN SURAT MENYURAT
                @endif
            @else
                LAPORAN SURAT MENYURAT
            @endif
        </h1>
        <p>Dicetak pada: {{ $generated_at->format('d F Y H:i:s') }}</p>
    </div>

    @php
        $hasOtherFilters = false;
        $filterText = '';
        
        if (isset($filters['status']) && !empty($filters['status'])) {
            $hasOtherFilters = true;
            $filterText .= 'Status: ' . ucfirst($filters['status']) . '<br>';
        }
        
        if (isset($filters['priority']) && !empty($filters['priority'])) {
            $hasOtherFilters = true;
            $filterText .= 'Prioritas: ' . ucfirst(str_replace('_', ' ', $filters['priority'])) . '<br>';
        }
        
        if ((isset($filters['date_from']) && !empty($filters['date_from'])) || (isset($filters['date_to']) && !empty($filters['date_to']))) {
            $hasOtherFilters = true;
            $filterText .= 'Periode: ';
            $filterText .= isset($filters['date_from']) && !empty($filters['date_from']) ? date('d/m/Y', strtotime($filters['date_from'])) : 'Awal';
            $filterText .= ' - ';
            $filterText .= isset($filters['date_to']) && !empty($filters['date_to']) ? date('d/m/Y', strtotime($filters['date_to'])) : 'Akhir';
        }
    @endphp

    @if($hasOtherFilters)
    <div class="info-section">
        <table>
            <tr>
                <td>Filter yang Diterapkan:</td>
                <td>{!! $filterText !!}</td>
            </tr>
        </table>
    </div>
    @endif

    @if(count($correspondence) > 0)
    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Jenis</th>
                <th>Nomor Surat</th>
                <th>Perihal</th>
                <th>Dari/Kepada</th>
                <th>Tanggal</th>
                <th>Prioritas</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach($correspondence as $index => $item)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $item->letter_type_name ?? '-' }}</td>
                <td>{{ $item->letter_number ?? $item->reference_number ?? '-' }}</td>
                <td>{{ $item->subject }}</td>
                <td>{{ $item->type === 'masuk' ? ($item->from ?? '-') : ($item->to ?? '-') }}</td>
                <td>{{ $item->date ? $item->date->format('d/m/Y') : '-' }}</td>
                <td>{{ ucfirst(str_replace('_', ' ', $item->priority)) }}</td>
                <td>{{ ucfirst($item->status) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @else
    <div class="no-data">
        Tidak ada data surat yang ditemukan
    </div>
    @endif

    <div class="footer">
        Total: {{ count($correspondence) }} surat
    </div>
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
</body>
</html>
