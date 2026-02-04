<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Data Kelas</title>
    <style>
        @page {
            size: A4 landscape;
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
            width: 100%;
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
        .info-section td:last-child {
            border: 1px solid #ddd;
            background-color: #f9f9f9;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 6px 8px;
            text-align: left;
            font-size: 9px;
        }
        th {
            background-color: #f2f2f2;
            font-weight: bold;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .text-center {
            text-align: center;
        }
        .text-right {
            text-align: right;
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
        .status-badge {
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 8px;
            font-weight: bold;
        }
        .status-aktif {
            background-color: #d4edda;
            color: #155724;
        }
        .status-nonaktif {
            background-color: #f8d7da;
            color: #721c24;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>LAPORAN DATA KELAS</h1>
        @if($institution)
        <p>{{ $institution->name }}</p>
        <p>NPSN: {{ $institution->npsn ?? '-' }}</p>
        @endif
        <p>Dicetak pada: {{ $generated_at->format('d F Y H:i:s') }}</p>
    </div>

    @php
        $hasFilters = false;
        $filterText = '';
        
        if (isset($filters['grade']) && !empty($filters['grade'])) {
            $hasFilters = true;
            $filterText .= 'Tingkat: ' . $filters['grade'] . '<br>';
        }
        
        if (isset($filters['status']) && !empty($filters['status'])) {
            $hasFilters = true;
            $filterText .= 'Status: ' . ucfirst($filters['status']) . '<br>';
        }
        
        if (isset($filters['academic_year']) && !empty($filters['academic_year'])) {
            $hasFilters = true;
            $filterText .= 'Tahun Ajaran: ' . $filters['academic_year'] . '<br>';
        }
        
        if (isset($filters['search']) && !empty($filters['search'])) {
            $hasFilters = true;
            $filterText .= 'Pencarian: ' . $filters['search'] . '<br>';
        }
    @endphp

    @if($hasFilters)
    <div class="info-section">
        <table>
            <tr>
                <td>Filter yang Diterapkan:</td>
                <td>{!! $filterText !!}</td>
            </tr>
        </table>
    </div>
    @endif

    @if(count($classes) > 0)
    <table>
        <thead>
            <tr>
                <th style="width: 3%;">No</th>
                <th style="width: 8%;">Kode</th>
                <th style="width: 12%;">Nama Kelas</th>
                <th style="width: 8%;">Tingkat</th>
                <th style="width: 12%;">Ruangan</th>
                <th style="width: 15%;">Wali Kelas</th>
                <th style="width: 6%;" class="text-center">Siswa</th>
                <th style="width: 8%;" class="text-center">Kapasitas</th>
                <th style="width: 8%;" class="text-center">Status</th>
                <th style="width: 10%;">Tahun Ajaran</th>
                <th style="width: 10%;">Semester</th>
            </tr>
        </thead>
        <tbody>
            @foreach($classes as $index => $class)
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td>{{ $class->code ?? '-' }}</td>
                <td>{{ $class->name }}</td>
                <td class="text-center">{{ $class->grade ? 'Tingkat ' . $class->grade : '-' }}</td>
                <td>{{ $class->room?->name ?? '-' }}</td>
                <td>{{ $class->teacher?->name ?? '-' }}</td>
                <td class="text-center">{{ $class->students_count ?? 0 }}</td>
                <td class="text-center">{{ $class->capacity ?? '-' }}</td>
                <td class="text-center">
                    <span class="status-badge status-{{ strtolower($class->status) }}">
                        {{ $class->status }}
                    </span>
                </td>
                <td>{{ $class->academic_year ?? ($class->academicYear?->code ?? '-') }}</td>
                <td>{{ $class->semester?->name ?? '-' }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr style="background-color: #e9ecef; font-weight: bold;">
                <td colspan="6" class="text-right">Total:</td>
                <td class="text-center">{{ $classes->sum('students_count') }}</td>
                <td colspan="4"></td>
            </tr>
        </tfoot>
    </table>
    @else
    <div class="no-data">
        Tidak ada data kelas yang ditemukan
    </div>
    @endif

    <div class="footer">
        Total: {{ count($classes) }} kelas
    </div>
</body>
</html>
