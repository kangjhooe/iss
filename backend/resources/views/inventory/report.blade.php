<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>{{ $report_title ?? 'Laporan Inventaris' }} - {{ $institution->name ?? 'Institusi' }}</title>
    <style>
        @page { margin: 1.2cm 1.2cm 1.6cm 1.2cm; size: A4 landscape; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 8.5pt; line-height: 1.3; color: #000; }
        .header { text-align: center; margin-bottom: 10px; }
        .header h1 { font-size: 13pt; font-weight: bold; margin: 0 0 3px 0; text-transform: uppercase; }
        .header p { font-size: 8pt; margin: 0; color: #444; }
        .meta {
            margin-bottom: 10px;
            padding: 6px 8px;
            background: #f5f5f5;
            border: 1px solid #ddd;
            font-size: 7.5pt;
        }
        .meta table { width: 100%; border: none; border-collapse: collapse; }
        .meta td { border: none; padding: 1px 10px 1px 0; vertical-align: top; }
        .meta td.lbl { font-weight: bold; white-space: nowrap; width: 1%; }
        .stats { margin-bottom: 10px; padding: 6px 8px; background: #f5f5f5; border: 1px solid #ddd; font-size: 8pt; }
        .stats table { width: auto; border: none; border-collapse: collapse; }
        .stats td { border: none; padding: 2px 14px 2px 0; }
        .stats td.lbl { font-weight: bold; }
        .section-title {
            font-size: 9.5pt;
            font-weight: bold;
            margin: 12px 0 5px;
            padding: 2px 0;
            border-bottom: 1px solid #333;
            text-transform: uppercase;
        }
        table.data { width: 100%; border-collapse: collapse; font-size: 7.5pt; margin-bottom: 4px; }
        table.data th, table.data td { border: 1px solid #333; padding: 3px 4px; text-align: left; }
        table.data th { background: #e8e8e8; font-weight: bold; }
        table.data td.num, table.data th.num { text-align: center; width: 24px; }
        table.data td.center, table.data th.center { text-align: center; }
        table.data td.right, table.data th.right { text-align: right; }
        table.data tfoot td { font-weight: bold; background: #f3f3f3; }
        .footer { margin-top: 10px; font-size: 7pt; text-align: center; color: #666; }
        .empty { text-align: center; padding: 10px; color: #666; }
        .two-col { width: 100%; border-collapse: collapse; margin-bottom: 6px; }
        .two-col > tbody > tr > td { width: 49%; vertical-align: top; border: none; padding: 0; }
        .two-col > tbody > tr > td + td { padding-left: 2%; }
        @include('partials.print-letterhead-styles')
        @include('partials.print-signature-styles')
        .standard-signature-wrap { margin-top: 16px; }
        .standard-signature-space { height: 48px; }
    </style>
</head>
<body>
    @include('partials.print-letterhead', ['institution' => $institution])

    <div class="header">
        <h1>{{ $report_title ?? 'Laporan Inventaris' }}</h1>
        <p>{{ $institution->name ?? '' }}</p>
    </div>

    <div class="meta">
        <table>
            <tr>
                <td class="lbl">Jenis laporan:</td>
                <td>{{ $report_title ?? '-' }}</td>
                <td class="lbl">Dicetak:</td>
                <td>{{ $printed_at ?? '-' }}@if(!empty($printed_by)) — {{ $printed_by }}@endif</td>
            </tr>
            @if(!empty($filter_legend))
            <tr>
                <td class="lbl">Filter:</td>
                <td colspan="3">{{ implode(' · ', $filter_legend) }}</td>
            </tr>
            @endif
        </table>
    </div>

    @php
        $type = $report_type ?? 'summary';
        $showSummaryBlocks = $type === 'summary';
        $showStock = in_array($type, ['summary', 'stock'], true);
        $showAsset = in_array($type, ['summary', 'asset'], true);
        $showDamaged = in_array($type, ['summary', 'damaged'], true);
        $showLoaned = in_array($type, ['summary', 'loaned'], true);
        $showTransactions = $type === 'transactions';
        $showAssetMovements = $type === 'asset_movements';
        $showMaintenance = $type === 'maintenance';
        $showLocation = $type === 'location';
        $showCategory = $type === 'category';
        $showDisposal = $type === 'disposal';
        $section = 0;
    @endphp

    @if($showSummaryBlocks && !empty($statistics))
    <div class="stats">
        <table>
            <tr>
                <td class="lbl">Total Barang:</td>
                <td>{{ $statistics['total_items'] ?? 0 }} ({{ number_format($statistics['total_quantity'] ?? 0, 0, ',', '.') }} unit)</td>
                <td class="lbl">Estimasi Nilai Perolehan:</td>
                <td>Rp {{ number_format($statistics['total_value'] ?? 0, 0, ',', '.') }}</td>
                <td class="lbl">Garansi &lt; 3 bln:</td>
                <td>{{ $statistics['warranty_expiring_soon'] ?? 0 }}</td>
            </tr>
        </table>
    </div>

    <table class="two-col">
        <tr>
            <td>
                @php $section++; @endphp
                <div class="section-title">{{ $section }}. Ringkasan per Status</div>
                <table class="data">
                    <thead>
                        <tr>
                            <th>Status</th>
                            <th class="center">Jumlah Item</th>
                            <th class="center">Total Unit</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse(($statistics['by_status'] ?? []) as $status => $row)
                        <tr>
                            <td>{{ $status }}</td>
                            <td class="center">{{ $row['count'] ?? 0 }}</td>
                            <td class="center">{{ $row['quantity'] ?? 0 }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="3" class="empty">Tidak ada data.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </td>
            <td>
                @php $section++; @endphp
                <div class="section-title">{{ $section }}. Ringkasan per Kondisi</div>
                <table class="data">
                    <thead>
                        <tr>
                            <th>Kondisi</th>
                            <th class="center">Jumlah Item</th>
                            <th class="center">Total Unit</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse(($statistics['by_condition'] ?? []) as $condition => $row)
                        <tr>
                            <td>{{ $condition }}</td>
                            <td class="center">{{ $row['count'] ?? 0 }}</td>
                            <td class="center">{{ $row['quantity'] ?? 0 }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="3" class="empty">Tidak ada data.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </td>
        </tr>
    </table>
    @endif

    @if($showAsset && !empty($asset_value))
    @php $section++; @endphp
    <div class="section-title">{{ $section }}. Estimasi Nilai Perolehan per Kategori</div>
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
                <td class="center">{{ number_format($row['total_quantity'] ?? 0, 0, ',', '.') }}</td>
                <td class="right">{{ number_format($row['total_value'] ?? 0, 0, ',', '.') }}</td>
            </tr>
            @empty
            <tr><td colspan="5" class="empty">Tidak ada data nilai aset.</td></tr>
            @endforelse
        </tbody>
        @if(!empty($asset_value['by_category']))
        <tfoot>
            <tr>
                <td colspan="4" class="right">Total</td>
                <td class="right">{{ number_format($asset_value['grand_total_value'] ?? 0, 0, ',', '.') }}</td>
            </tr>
        </tfoot>
        @endif
    </table>

    @if($type === 'asset')
    @php $section++; @endphp
    <div class="section-title">{{ $section }}. Rincian Nilai per Barang</div>
    <table class="data">
        <thead>
            <tr>
                <th class="num">No</th>
                <th>Kode</th>
                <th>Nama</th>
                <th>Kategori</th>
                <th class="center">Qty</th>
                <th class="right">Harga Satuan</th>
                <th class="right">Nilai</th>
                <th class="center">Tgl Beli</th>
            </tr>
        </thead>
        <tbody>
            @php
                $assetItems = collect($asset_value['by_category'] ?? [])->flatMap(function ($cat) {
                    return collect($cat['items'] ?? [])->map(fn ($item) => array_merge($item, [
                        'category' => $cat['category'] ?? '-',
                    ]));
                })->values();
            @endphp
            @forelse($assetItems as $i => $row)
            <tr>
                <td class="num">{{ $i + 1 }}</td>
                <td>{{ $row['code'] ?? '-' }}</td>
                <td>{{ $row['name'] ?? '-' }}</td>
                <td>{{ $row['category'] ?? '-' }}</td>
                <td class="center">{{ $row['quantity'] ?? '-' }}{{ !empty($row['unit']) ? ' '.$row['unit'] : '' }}</td>
                <td class="right">{{ number_format($row['unit_price'] ?? 0, 0, ',', '.') }}</td>
                <td class="right">{{ number_format($row['total_value'] ?? 0, 0, ',', '.') }}</td>
                <td class="center">{{ !empty($row['purchase_date']) ? \Carbon\Carbon::parse($row['purchase_date'])->format('d/m/Y') : '-' }}</td>
            </tr>
            @empty
            <tr><td colspan="8" class="empty">Tidak ada rincian aset.</td></tr>
            @endforelse
        </tbody>
    </table>
    @endif
    @endif

    @if($showDamaged && !empty($damaged_missing))
    @php $section++; @endphp
    <div class="section-title">{{ $section }}. Barang Rusak / Hilang</div>
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
                $problemRows = collect($damaged_missing['rows'] ?? []);
                if ($problemRows->isEmpty()) {
                    $damagedRows = collect($damaged_missing['damaged'] ?? [])->map(fn ($r) => array_merge($r, ['label' => $r['condition'] ?? 'Rusak']));
                    $missingRows = collect($damaged_missing['missing'] ?? [])->map(fn ($r) => array_merge($r, ['label' => 'Hilang']));
                    $problemRows = $damagedRows->concat($missingRows)->values();
                }
            @endphp
            @forelse($problemRows as $i => $row)
            <tr>
                <td class="num">{{ $i + 1 }}</td>
                <td>{{ $row['code'] ?? '-' }}</td>
                <td>{{ $row['name'] ?? '-' }}</td>
                <td>{{ $row['category'] ?? '-' }}</td>
                <td>{{ $row['label'] ?? ($row['condition'] ?? $row['status'] ?? '-') }}</td>
                <td class="center">{{ $row['quantity'] ?? '-' }}{{ !empty($row['unit']) ? ' '.$row['unit'] : '' }}</td>
                <td>{{ $row['location'] ?? '-' }}</td>
            </tr>
            @empty
            <tr><td colspan="7" class="empty">Tidak ada barang rusak/hilang.</td></tr>
            @endforelse
        </tbody>
    </table>
    @endif

    @if($showLoaned && !empty($loaned))
    @php $section++; @endphp
    <div class="section-title">{{ $section }}. Sedang Dipinjam</div>
    <table class="data">
        <thead>
            <tr>
                <th class="num">No</th>
                <th>Kode</th>
                <th>Barang</th>
                <th>Kategori</th>
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
                <td>{{ $loan['category'] ?? '-' }}</td>
                <td>{{ $loan['borrower_name'] ?? '-' }}</td>
                <td class="center">{{ $loan['quantity'] ?? '-' }}</td>
                <td class="center">{{ !empty($loan['loan_date']) ? \Carbon\Carbon::parse($loan['loan_date'])->format('d/m/Y') : '-' }}</td>
                <td class="center">{{ !empty($loan['expected_return_date']) ? \Carbon\Carbon::parse($loan['expected_return_date'])->format('d/m/Y') : '-' }}</td>
                <td>{{ ($loan['is_overdue'] ?? false) ? 'Terlambat' : ($loan['status'] ?? '-') }}</td>
            </tr>
            @empty
            <tr><td colspan="9" class="empty">Tidak ada peminjaman aktif.</td></tr>
            @endforelse
        </tbody>
    </table>
    @endif

    @if($showStock && !empty($stock))
    @php $section++; @endphp
    <div class="section-title">{{ $section }}. Daftar Stok Barang</div>
    <table class="data">
        <thead>
            <tr>
                <th class="num">No</th>
                <th>Kode</th>
                <th>Nama</th>
                <th>Merk/Model</th>
                <th>SN</th>
                <th>Kategori</th>
                <th class="center">Qty</th>
                <th>Kondisi</th>
                <th>Status</th>
                <th>Lokasi</th>
                <th class="center">Tgl Beli</th>
                <th class="right">Harga</th>
                <th class="right">Nilai</th>
            </tr>
        </thead>
        <tbody>
            @forelse($stock['items'] ?? [] as $i => $item)
            <tr>
                <td class="num">{{ $i + 1 }}</td>
                <td>{{ $item['code'] ?? '-' }}</td>
                <td>{{ $item['name'] ?? '-' }}</td>
                <td>{{ $item['brand_model'] ?? '-' }}</td>
                <td>{{ $item['serial_number'] ?? '-' }}</td>
                <td>{{ $item['category'] ?? '-' }}</td>
                <td class="center">{{ $item['quantity'] ?? '-' }}{{ !empty($item['unit']) ? ' '.$item['unit'] : '' }}</td>
                <td>{{ $item['condition'] ?? '-' }}</td>
                <td>{{ $item['status'] ?? '-' }}</td>
                <td>{{ $item['location'] ?? '-' }}</td>
                <td class="center">{{ !empty($item['purchase_date']) ? \Carbon\Carbon::parse($item['purchase_date'])->format('d/m/Y') : '-' }}</td>
                <td class="right">{{ $item['purchase_price'] !== null ? number_format($item['purchase_price'], 0, ',', '.') : '-' }}</td>
                <td class="right">{{ $item['total_value'] !== null ? number_format($item['total_value'], 0, ',', '.') : '-' }}</td>
            </tr>
            @empty
            <tr><td colspan="13" class="empty">Tidak ada data barang.</td></tr>
            @endforelse
        </tbody>
        @if(!empty($stock['items']))
        <tfoot>
            <tr>
                <td colspan="6" class="right">Total</td>
                <td class="center">{{ number_format($stock['total_quantity'] ?? 0, 0, ',', '.') }}</td>
                <td colspan="4"></td>
                <td colspan="2" class="right">{{ number_format($stock['total_value'] ?? 0, 0, ',', '.') }}</td>
            </tr>
        </tfoot>
        @endif
    </table>
    @if(!empty($stock['truncated']))
    <div class="footer">Daftar dibatasi {{ count($stock['items'] ?? []) }} dari {{ $stock['total'] ?? 0 }} barang.</div>
    @endif
    @endif

    @if($showTransactions && !empty($transactions))
    @php $section++; @endphp
    <div class="section-title">{{ $section }}. Mutasi / Transaksi</div>
    <table class="data">
        <thead>
            <tr>
                <th class="num">No</th>
                <th class="center">Tanggal</th>
                <th>No Ref</th>
                <th>Kode</th>
                <th>Nama</th>
                <th>Jenis</th>
                <th class="center">Qty</th>
                <th>Dari</th>
                <th>Ke</th>
                <th>Oleh</th>
            </tr>
        </thead>
        <tbody>
            @forelse($transactions['transactions'] ?? [] as $i => $row)
            <tr>
                <td class="num">{{ $i + 1 }}</td>
                <td class="center">{{ !empty($row['date']) ? \Carbon\Carbon::parse($row['date'])->format('d/m/Y') : '-' }}</td>
                <td>{{ $row['reference_number'] ?? '-' }}</td>
                <td>{{ $row['item_code'] ?? '-' }}</td>
                <td>{{ $row['item_name'] ?? '-' }}</td>
                <td>{{ $row['type'] ?? '-' }}</td>
                <td class="center">{{ $row['quantity'] ?? '-' }}</td>
                <td>{{ $row['from_location'] ?? '-' }}</td>
                <td>{{ $row['to_location'] ?? '-' }}</td>
                <td>{{ $row['created_by'] ?? '-' }}</td>
            </tr>
            @empty
            <tr><td colspan="10" class="empty">Tidak ada transaksi pada periode ini.</td></tr>
            @endforelse
        </tbody>
    </table>
    @endif

    @if($showAssetMovements && !empty($asset_movements))
    @php $section++; @endphp
    <div class="section-title">{{ $section }}. Mutasi Aset Individual</div>
    <table class="data">
        <thead>
            <tr>
                <th class="num">No</th>
                <th class="center">Tanggal</th>
                <th>No Aset</th>
                <th>Kode Barang</th>
                <th>Nama Barang</th>
                <th>Dari</th>
                <th>Ke</th>
                <th>No Ref</th>
                <th>Oleh</th>
            </tr>
        </thead>
        <tbody>
            @forelse($asset_movements['movements'] ?? [] as $i => $row)
            <tr>
                <td class="num">{{ $i + 1 }}</td>
                <td class="center">{{ !empty($row['movement_date']) ? \Carbon\Carbon::parse($row['movement_date'])->format('d/m/Y') : '-' }}</td>
                <td>{{ $row['asset_number'] ?? '-' }}</td>
                <td>{{ $row['item_code'] ?? '-' }}</td>
                <td>{{ $row['item_name'] ?? '-' }}</td>
                <td>{{ $row['from_location'] ?? '-' }}</td>
                <td>{{ $row['to_location'] ?? '-' }}</td>
                <td>{{ $row['reference_number'] ?? '-' }}</td>
                <td>{{ $row['created_by'] ?? '-' }}</td>
            </tr>
            @empty
            <tr><td colspan="9" class="empty">Tidak ada mutasi aset pada periode ini.</td></tr>
            @endforelse
        </tbody>
    </table>
    @endif

    @if($showMaintenance && !empty($maintenance))
    @php $section++; @endphp
    <div class="section-title">{{ $section }}. Pemeliharaan</div>
    <table class="data">
        <thead>
            <tr>
                <th class="num">No</th>
                <th class="center">Tgl Jadwal</th>
                <th>Kode</th>
                <th>Barang</th>
                <th>Jenis</th>
                <th class="right">Biaya</th>
                <th>Status</th>
                <th>Teknisi</th>
                <th class="center">Selesai</th>
            </tr>
        </thead>
        <tbody>
            @forelse($maintenance['maintenances'] ?? [] as $i => $row)
            <tr>
                <td class="num">{{ $i + 1 }}</td>
                <td class="center">{{ !empty($row['scheduled_date']) ? \Carbon\Carbon::parse($row['scheduled_date'])->format('d/m/Y') : '-' }}</td>
                <td>{{ $row['item_code'] ?? '-' }}</td>
                <td>{{ $row['item_name'] ?? '-' }}</td>
                <td>{{ $row['type'] ?? '-' }}</td>
                <td class="right">{{ $row['cost'] !== null ? number_format($row['cost'], 0, ',', '.') : '-' }}</td>
                <td>{{ $row['status'] ?? '-' }}</td>
                <td>{{ $row['technician_name'] ?? '-' }}</td>
                <td class="center">{{ !empty($row['completed_date']) ? \Carbon\Carbon::parse($row['completed_date'])->format('d/m/Y') : '-' }}</td>
            </tr>
            @empty
            <tr><td colspan="9" class="empty">Tidak ada data pemeliharaan.</td></tr>
            @endforelse
        </tbody>
        @if(!empty($maintenance['maintenances']))
        <tfoot>
            <tr>
                <td colspan="5" class="right">Total biaya</td>
                <td class="right">{{ number_format($maintenance['total_cost'] ?? 0, 0, ',', '.') }}</td>
                <td colspan="3"></td>
            </tr>
        </tfoot>
        @endif
    </table>
    @endif

    @if($showLocation && !empty($by_location))
    @php $section++; @endphp
    <div class="section-title">{{ $section }}. Inventaris per Ruangan</div>
    @forelse(collect($by_location['by_room'] ?? []) as $loc)
    <div style="margin-bottom: 10px;">
        <strong>{{ $loc['location_name'] ?? '-' }}</strong>
        ({{ $loc['count'] ?? 0 }} barang, {{ $loc['total_quantity'] ?? 0 }} unit)
        <table class="data" style="margin-top: 4px;">
            <thead>
                <tr>
                    <th>Kode</th>
                    <th>Nama</th>
                    <th>Kategori</th>
                    <th class="center">Qty</th>
                </tr>
            </thead>
            <tbody>
                @foreach($loc['items'] ?? [] as $item)
                <tr>
                    <td>{{ $item['code'] ?? '-' }}</td>
                    <td>{{ $item['name'] ?? '-' }}</td>
                    <td>{{ $item['category'] ?? '-' }}</td>
                    <td class="center">{{ $item['quantity'] ?? 0 }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @empty
    <p class="empty">Tidak ada data inventaris per ruangan.</p>
    @endforelse
    @endif

    @if($showCategory && !empty($by_category))
    @php $section++; @endphp
    <div class="section-title">{{ $section }}. Inventaris per Kategori</div>
    @forelse($by_category as $cat)
    <div style="margin-bottom: 10px;">
        <strong>{{ $cat['category'] ?? '-' }}</strong>
        ({{ $cat['count'] ?? 0 }} barang, {{ $cat['total_quantity'] ?? 0 }} unit)
        <table class="data" style="margin-top: 4px;">
            <thead>
                <tr>
                    <th>Kode</th>
                    <th>Nama</th>
                    <th class="center">Qty</th>
                    <th>Kondisi</th>
                    <th>Status</th>
                    <th>Lokasi</th>
                </tr>
            </thead>
            <tbody>
                @foreach($cat['items'] ?? [] as $item)
                <tr>
                    <td>{{ $item['code'] ?? '-' }}</td>
                    <td>{{ $item['name'] ?? '-' }}</td>
                    <td class="center">{{ $item['quantity'] ?? 0 }}{{ !empty($item['unit']) ? ' '.$item['unit'] : '' }}</td>
                    <td>{{ $item['condition'] ?? '-' }}</td>
                    <td>{{ $item['status'] ?? '-' }}</td>
                    <td>{{ $item['location'] ?? '-' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @empty
    <p class="empty">Tidak ada data inventaris per kategori.</p>
    @endforelse
    @endif

    @if($showDisposal && !empty($disposed))
    @php $section++; @endphp
    <div class="section-title">{{ $section }}. Riwayat Penghapusan Barang</div>
    <table class="data">
        <thead>
            <tr>
                <th class="num">No</th>
                <th class="center">Tgl Penghapusan</th>
                <th>Kode</th>
                <th>Nama</th>
                <th>Kategori</th>
                <th>Status Akhir</th>
                <th class="center">Qty</th>
                <th>No. SK / BA</th>
                <th>Alasan</th>
            </tr>
        </thead>
        <tbody>
            @forelse($disposed['items'] ?? [] as $i => $row)
            <tr>
                <td class="num">{{ $i + 1 }}</td>
                <td class="center">{{ !empty($row['disposed_at']) ? \Carbon\Carbon::parse($row['disposed_at'])->format('d/m/Y') : '-' }}</td>
                <td>{{ $row['code'] ?? '-' }}</td>
                <td>{{ $row['name'] ?? '-' }}</td>
                <td>{{ $row['category'] ?? '-' }}</td>
                <td>{{ $row['status'] ?? '-' }}</td>
                <td class="center">{{ $row['quantity'] ?? '-' }}{{ !empty($row['unit']) ? ' '.$row['unit'] : '' }}</td>
                <td>{{ $row['disposal_document_number'] ?? '-' }}</td>
                <td>{{ $row['disposal_reason'] ?? '-' }}</td>
            </tr>
            @empty
            <tr><td colspan="9" class="empty">Tidak ada data penghapusan.</td></tr>
            @endforelse
        </tbody>
    </table>
    @endif

    <div class="standard-signature-wrap">
        <div class="standard-signature-left"></div>
        <div class="standard-signature-right">
            @include('partials.print-signature', [
                'institution' => $institution,
                'date' => ($as_of_date ?? now())->locale('id')->translatedFormat('d F Y'),
                'as_of_date' => $as_of_date ?? null,
            ])
        </div>
    </div>

    <div class="footer print-document-footer">
        Dicetak pada {{ $printed_at }}
        @if(!empty($printed_by)) &mdash; oleh {{ $printed_by }}@endif
    </div>
</body>
</html>
