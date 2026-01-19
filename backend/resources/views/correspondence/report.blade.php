<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Surat Menyurat</title>
    <style>
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 12px;
            color: #333;
            margin: 0;
            padding: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #333;
            padding-bottom: 20px;
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
            margin-bottom: 20px;
        }
        .info-section table {
            width: 100%;
            border-collapse: collapse;
        }
        .info-section td {
            padding: 5px;
            font-size: 11px;
        }
        .info-section td:first-child {
            font-weight: bold;
            width: 150px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
            font-size: 10px;
        }
        th {
            background-color: #f2f2f2;
            font-weight: bold;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .footer {
            margin-top: 30px;
            text-align: right;
            font-size: 10px;
            color: #666;
        }
        .no-data {
            text-align: center;
            padding: 40px;
            color: #999;
            font-style: italic;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>LAPORAN SURAT MENYURAT</h1>
        @if($institution)
        <p>{{ $institution->name }}</p>
        <p>NPSN: {{ $institution->npsn ?? '-' }}</p>
        @endif
        <p>Dicetak pada: {{ $generated_at->format('d F Y H:i:s') }}</p>
    </div>

    @if(count($filters) > 0)
    <div class="info-section">
        <table>
            <tr>
                <td>Filter yang Diterapkan:</td>
                <td>
                    @if(isset($filters['type']))
                        Tipe: {{ ucfirst($filters['type']) }}<br>
                    @endif
                    @if(isset($filters['status']))
                        Status: {{ ucfirst($filters['status']) }}<br>
                    @endif
                    @if(isset($filters['priority']))
                        Prioritas: {{ ucfirst(str_replace('_', ' ', $filters['priority'])) }}<br>
                    @endif
                    @if(isset($filters['date_from']) || isset($filters['date_to']))
                        Periode: 
                        {{ isset($filters['date_from']) ? date('d/m/Y', strtotime($filters['date_from'])) : 'Awal' }} - 
                        {{ isset($filters['date_to']) ? date('d/m/Y', strtotime($filters['date_to'])) : 'Akhir' }}
                    @endif
                </td>
            </tr>
        </table>
    </div>
    @endif

    @if(count($correspondence) > 0)
    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Tipe</th>
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
                <td>{{ ucfirst($item->type) }}</td>
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
</body>
</html>
