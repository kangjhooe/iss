<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Sarana Prasarana - {{ $institution->name ?? 'Institusi' }}</title>
    <style>
        @page { margin: 1.2cm; size: A4 landscape; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 9pt; line-height: 1.3; color: #000; }
        .header { text-align: center; margin-bottom: 12px; }
        .header h1 { font-size: 14pt; font-weight: bold; margin: 0 0 4px 0; text-transform: uppercase; }
        .header p { font-size: 8pt; margin: 0; color: #444; }
        .stats { margin-bottom: 12px; padding: 8px 10px; background: #f5f5f5; border: 1px solid #ddd; font-size: 8pt; }
        .stats table { width: auto; border: none; border-collapse: collapse; }
        .stats td { border: none; padding: 2px 14px 2px 0; }
        .stats td.lbl { font-weight: bold; }
        .section-title {
            font-size: 10pt;
            font-weight: bold;
            margin: 14px 0 6px;
            padding: 3px 0;
            border-bottom: 1px solid #333;
            text-transform: uppercase;
        }
        table.data { width: calc(100% - 2px); max-width: calc(100% - 2px); border-collapse: collapse; font-size: 8pt; margin-bottom: 4px; }
        table.data th, table.data td { border: 1px solid #333; padding: 4px 5px; text-align: left; }
        table.data th { background: #e8e8e8; font-weight: bold; }
        table.data td.num { text-align: center; width: 28px; }
        table.data td.center { text-align: center; }
        table.data td.right { text-align: right; }
        .footer { margin-top: 10px; font-size: 7pt; text-align: center; color: #666; }
        .empty { text-align: center; padding: 10px; color: #666; }
        @include('partials.print-letterhead-styles')
        @include('partials.print-signature-styles')
        .standard-signature-wrap { margin-top: 18px; }
        .standard-signature-space { height: 48px; }
    </style>
</head>
<body>
    @include('partials.print-letterhead', ['institution' => $institution])

    <div class="header">
        <h1>Laporan Sarana dan Prasarana</h1>
        <p>Ringkasan data tanah, gedung, dan ruangan</p>
    </div>

    <div class="stats">
        <table>
            <tr>
                <td class="lbl">Tanah:</td>
                <td>{{ $summary['land_count'] }} bidang ({{ number_format($summary['land_area'], 0, ',', '.') }} m²)</td>
                <td class="lbl">Gedung:</td>
                <td>{{ $summary['building_count'] }} unit ({{ number_format($summary['building_area'], 0, ',', '.') }} m²)</td>
                <td class="lbl">Ruangan:</td>
                <td>{{ $summary['room_count'] }} ruang</td>
            </tr>
        </table>
    </div>

    <div class="section-title">1. Data Tanah</div>
    <table class="data">
        <thead>
            <tr>
                <th class="num">No</th>
                <th>Nama</th>
                <th>No. Sertifikat</th>
                <th>Jenis</th>
                <th class="right">Luas (m²)</th>
                <th>Lokasi</th>
                <th>Status</th>
                <th class="center">Tgl Perolehan</th>
            </tr>
        </thead>
        <tbody>
            @forelse($lands as $i => $land)
            <tr>
                <td class="num">{{ $i + 1 }}</td>
                <td>{{ $land->name ?? '-' }}</td>
                <td>{{ $land->certificate_number ?? '-' }}</td>
                <td>{{ $land->certificate_type ?? '-' }}</td>
                <td class="right">{{ $land->area !== null ? number_format((float) $land->area, 0, ',', '.') : '-' }}</td>
                <td>{{ $land->location ?? '-' }}</td>
                <td>{{ $land->status ?? '-' }}</td>
                <td class="center">{{ $land->acquisition_date?->format('d/m/Y') ?? '-' }}</td>
            </tr>
            @empty
            <tr><td colspan="8" class="empty">Tidak ada data tanah.</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="section-title">2. Data Gedung</div>
    <table class="data">
        <thead>
            <tr>
                <th class="num">No</th>
                <th>Nama</th>
                <th>Kode</th>
                <th>Tanah</th>
                <th class="center">Lantai</th>
                <th class="right">Luas (m²)</th>
                <th>Kondisi</th>
                <th class="center">Thn Bangun</th>
            </tr>
        </thead>
        <tbody>
            @forelse($buildings as $i => $building)
            <tr>
                <td class="num">{{ $i + 1 }}</td>
                <td>{{ $building->name ?? '-' }}</td>
                <td>{{ $building->code ?? '-' }}</td>
                <td>{{ $building->land->name ?? '-' }}</td>
                <td class="center">{{ $building->floor_count ?? '-' }}</td>
                <td class="right">{{ $building->building_area !== null ? number_format((float) $building->building_area, 0, ',', '.') : '-' }}</td>
                <td>{{ $building->condition ?? '-' }}</td>
                <td class="center">{{ $building->construction_year ?? '-' }}</td>
            </tr>
            @empty
            <tr><td colspan="8" class="empty">Tidak ada data gedung.</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="section-title">3. Data Ruangan</div>
    @if(!empty($rooms_by_type) && count($rooms_by_type))
    <div class="stats" style="margin-bottom: 8px;">
        <table>
            <tr>
                @foreach($rooms_by_type as $type => $count)
                <td class="lbl">{{ $type }}:</td>
                <td>{{ $count }}</td>
                @endforeach
            </tr>
        </table>
    </div>
    @endif
    <table class="data">
        <thead>
            <tr>
                <th class="num">No</th>
                <th>Nama</th>
                <th>Kode</th>
                <th>Jenis</th>
                <th>Gedung</th>
                <th class="center">Lt</th>
                <th class="right">Luas</th>
                <th class="center">Kapasitas</th>
                <th>Kondisi</th>
                <th>PJ</th>
            </tr>
        </thead>
        <tbody>
            @forelse($rooms as $i => $room)
            <tr>
                <td class="num">{{ $i + 1 }}</td>
                <td>{{ $room->name ?? '-' }}</td>
                <td>{{ $room->code ?? '-' }}</td>
                <td>{{ $room->type ?? '-' }}@if($room->lab_type) ({{ $room->lab_type }})@endif</td>
                <td>{{ $room->building->name ?? '-' }}</td>
                <td class="center">{{ $room->floor ?? '-' }}</td>
                <td class="right">{{ $room->area !== null ? number_format((float) $room->area, 0, ',', '.') : '-' }}</td>
                <td class="center">{{ $room->capacity ?? '-' }}</td>
                <td>{{ $room->condition ?? '-' }}</td>
                <td>{{ $room->responsibleEmployee->name ?? '-' }}</td>
            </tr>
            @empty
            <tr><td colspan="10" class="empty">Tidak ada data ruangan.</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="standard-signature-wrap">
        <div class="standard-signature-left"></div>
        <div class="standard-signature-right">
            @include('partials.print-signature', [
                'institution' => $institution,
                'date' => now()->locale('id')->translatedFormat('d F Y'),
            ])
        </div>
    </div>

    @include('partials.print-document-footer', ['footer_suffix' => '· '.($summary['land_count'] + $summary['building_count'] + $summary['room_count']).' catatan'])
</body>
</html>
