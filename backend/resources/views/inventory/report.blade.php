<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Inventaris - {{ $institution->name ?? 'Institusi' }}</title>
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
        <h1>Laporan Inventaris</h1>
        <p>Statistik, kondisi, peminjaman, dan nilai aset</p>
    </div>

    <div class="stats">
        <table>
            <tr>
                <td class="lbl">Total Barang:</td>
                <td>{{ $statistics['total_items'] ?? 0 }} ({{ number_format($statistics['total_quantity'] ?? 0, 0, ',', '.') }} unit)</td>
                <td class="lbl">Nilai Aset:</td>
                <td>Rp {{ number_format($statistics['total_value'] ?? 0, 0, ',', '.') }}</td>
                <td class="lbl">Garansi &lt; 3 bln:</td>
                <td>{{ $statistics['warranty_expiring_soon'] ?? 0 }}</td>
            </tr>
        </table>
    </div>

    @if(!empty($statistics['by_status']))
    <div class="section-title">1. Ringkasan per Status</div>
    <table class="data">
        <thead>
            <tr>
                <th>Status</th>
                <th class="center">Jumlah Item</th>
                <th class="center">Total Unit</th>
            </tr>
        </thead>
        <tbody>
            @foreach($statistics['by_status'] as $status => $row)
            <tr>
                <td>{{ $status }}</td>
                <td class="center">{{ $row['count'] ?? 0 }}</td>
                <td class="center">{{ $row['quantity'] ?? 0 }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    @if(!empty($statistics['by_condition']))
    <div class="section-title">2. Ringkasan per Kondisi</div>
    <table class="data">
        <thead>
            <tr>
                <th>Kondisi</th>
                <th class="center">Jumlah Item</th>
                <th class="center">Total Unit</th>
            </tr>
        </thead>
        <tbody>
            @foreach($statistics['by_condition'] as $condition => $row)
            <tr>
                <td>{{ $condition }}</td>
                <td class="center">{{ $row['count'] ?? 0 }}</td>
                <td class="center">{{ $row['quantity'] ?? 0 }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    <div class="section-title">3. Nilai Aset per Kategori</div>
    <table class="data">
        <thead>
            <tr>
                <th class="num">No</th>
                <th>Kategori</th>
                <th class="center">Jumlah Item</th>
                <th class="center">Total Unit</th>
                <th class="right">Nilai (Rp)</th>
            </tr>
        </thead>
        <tbody>
            @forelse($asset_value['by_category'] ?? [] as $i => $row)
            <tr>
                <td class="num">{{ $i + 1 }}</td>
                <td>{{ $row['category'] ?? '-' }}</td>
                <td class="center">{{ $row['item_count'] ?? 0 }}</td>
                <td class="center">{{ $row['total_quantity'] ?? 0 }}</td>
                <td class="right">{{ number_format($row['total_value'] ?? 0, 0, ',', '.') }}</td>
            </tr>
            @empty
            <tr><td colspan="5" class="empty">Tidak ada data nilai aset.</td></tr>
            @endforelse
            @if(!empty($asset_value['by_category']))
            <tr>
                <td colspan="4" class="right"><strong>Total</strong></td>
                <td class="right"><strong>{{ number_format($asset_value['grand_total_value'] ?? 0, 0, ',', '.') }}</strong></td>
            </tr>
            @endif
        </tbody>
    </table>

    <div class="section-title">4. Barang Rusak / Hilang</div>
    <table class="data">
        <thead>
            <tr>
                <th class="num">No</th>
                <th>Kode</th>
                <th>Nama</th>
                <th>Kategori</th>
                <th>Kondisi / Status</th>
                <th class="center">Qty</th>
                <th>Lokasi</th>
            </tr>
        </thead>
        <tbody>
            @php
                $damagedRows = collect($damaged_missing['damaged'] ?? [])->map(fn ($r) => array_merge($r, ['label' => $r['condition'] ?? 'Rusak']));
                $missingRows = collect($damaged_missing['missing'] ?? [])->map(fn ($r) => array_merge($r, ['label' => 'Hilang']));
                $problemRows = $damagedRows->concat($missingRows)->values();
            @endphp
            @forelse($problemRows as $i => $row)
            <tr>
                <td class="num">{{ $i + 1 }}</td>
                <td>{{ $row['code'] ?? '-' }}</td>
                <td>{{ $row['name'] ?? '-' }}</td>
                <td>{{ $row['category'] ?? '-' }}</td>
                <td>{{ $row['label'] ?? '-' }}</td>
                <td class="center">{{ $row['quantity'] ?? '-' }}</td>
                <td>{{ $row['location'] ?? '-' }}</td>
            </tr>
            @empty
            <tr><td colspan="7" class="empty">Tidak ada barang rusak/hilang.</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="section-title">5. Sedang Dipinjam</div>
    <table class="data">
        <thead>
            <tr>
                <th class="num">No</th>
                <th>Kode</th>
                <th>Barang</th>
                <th>Peminjam</th>
                <th class="center">Qty</th>
                <th class="center">Tgl Pinjam</th>
                <th class="center">Jatuh Tempo</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($loaned['loans'] ?? [] as $i => $loan)
            <tr>
                <td class="num">{{ $i + 1 }}</td>
                <td>{{ $loan['item_code'] ?? '-' }}</td>
                <td>{{ $loan['item_name'] ?? '-' }}</td>
                <td>{{ $loan['borrower_name'] ?? '-' }}</td>
                <td class="center">{{ $loan['quantity'] ?? '-' }}</td>
                <td class="center">{{ !empty($loan['loan_date']) ? \Carbon\Carbon::parse($loan['loan_date'])->format('d/m/Y') : '-' }}</td>
                <td class="center">{{ !empty($loan['expected_return_date']) ? \Carbon\Carbon::parse($loan['expected_return_date'])->format('d/m/Y') : '-' }}</td>
                <td>{{ ($loan['is_overdue'] ?? false) ? 'Terlambat' : ($loan['status'] ?? '-') }}</td>
            </tr>
            @empty
            <tr><td colspan="8" class="empty">Tidak ada peminjaman aktif.</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="section-title">6. Daftar Barang Inventaris</div>
    <table class="data">
        <thead>
            <tr>
                <th class="num">No</th>
                <th>Kode</th>
                <th>Nama</th>
                <th>Kategori</th>
                <th class="center">Qty</th>
                <th>Kondisi</th>
                <th>Status</th>
                <th>Lokasi</th>
            </tr>
        </thead>
        <tbody>
            @forelse($items as $i => $item)
            <tr>
                <td class="num">{{ $i + 1 }}</td>
                <td>{{ $item->code ?? '-' }}</td>
                <td>{{ $item->name ?? '-' }}</td>
                <td>{{ $item->category->name ?? '-' }}</td>
                <td class="center">{{ $item->quantity ?? '-' }}{{ $item->unit ? ' ' . $item->unit : '' }}</td>
                <td>{{ $item->condition ?? '-' }}</td>
                <td>{{ $item->status ?? '-' }}</td>
                <td>
                    @if($item->room)
                        {{ $item->room->name }}
                    @elseif($item->building)
                        {{ $item->building->name }}
                    @else
                        {{ $item->location_note ?: '-' }}
                    @endif
                </td>
            </tr>
            @empty
            <tr><td colspan="8" class="empty">Tidak ada data barang.</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        Dicetak pada {{ $printed_at }}
        @if(!empty($printed_by)) &mdash; oleh {{ $printed_by }}@endif
        &mdash; {{ $items->count() }} barang ditampilkan
        @if(($items_truncated ?? false)) (dibatasi 2000 baris)@endif
    </div>

    <div class="standard-signature-wrap">
        <div class="standard-signature-left"></div>
        <div class="standard-signature-right">
            @include('partials.print-signature', [
                'institution' => $institution,
                'date' => now()->locale('id')->translatedFormat('d F Y'),
            ])
        </div>
    </div>
</body>
</html>
