<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kartu Inventaris Barang - {{ $item->code ?? '' }}</title>
    <style>
        @include('inventory.partials.kib_print_styles')
    </style>
</head>
<body>
    <div class="kib-document">
        @include('partials.print-letterhead', ['institution' => $institution])

        <div class="doc-title">
            <h1>Kartu Inventaris Barang (KIB)</h1>
            <p>Master Barang / Stok</p>
        </div>

        <table class="kib">
            <tr>
                <th>Kode Inventaris</th>
                <td>{{ $item->code ?? '-' }}</td>
                <th>Kategori</th>
                <td>{{ $item->category->name ?? '-' }}@if(!empty($item->category->code)) ({{ $item->category->code }})@endif</td>
            </tr>
            <tr>
                <th>Nama Barang</th>
                <td colspan="3">{{ $item->name ?? '-' }}</td>
            </tr>
            <tr>
                <th>Merk / Model</th>
                <td>{{ trim(($item->brand ?? '') . ' ' . ($item->model ?? '')) ?: '-' }}</td>
                <th>Nomor Seri</th>
                <td>{{ $item->serial_number ?? '-' }}</td>
            </tr>
            <tr>
                <th>Jumlah</th>
                <td>{{ $item->quantity ?? 0 }} {{ $item->unit ?? 'Unit' }}</td>
                <th>Kondisi</th>
                <td>{{ $item->condition ?? '-' }}</td>
            </tr>
            <tr>
                <th>Tgl Perolehan</th>
                <td>{{ $item->purchase_date?->format('d/m/Y') ?? '-' }}</td>
                <th>Status</th>
                <td>{{ $item->status ?? '-' }}</td>
            </tr>
            <tr>
                <th>Sumber Dana</th>
                <td>{{ $item->funding_source ?? '-' }}</td>
                <th>Garansi Sampai</th>
                <td>{{ $item->warranty_expiry?->format('d/m/Y') ?? '-' }}</td>
            </tr>
            <tr>
                <th>Harga / Nilai</th>
                <td>Rp {{ $item->purchase_price !== null ? number_format((float) $item->purchase_price, 0, ',', '.') : '-' }}</td>
                <th>Supplier</th>
                <td>{{ $item->supplier ?? '-' }}</td>
            </tr>
            <tr>
                <th>Lokasi / Ruangan</th>
                <td>{{ $item->room->name ?? ($item->building->name ?? ($item->location_note ?? '-')) }}</td>
                <th>Penanggung Jawab</th>
                <td>{{ $item->responsibleEmployee->name ?? '-' }}</td>
            </tr>
            @if($item->disposed_at)
            <tr>
                <th>Tgl Penghapusan</th>
                <td>{{ $item->disposed_at->format('d/m/Y') }}</td>
                <th>No. SK / BA</th>
                <td>{{ $item->disposal_document_number ?? '-' }}</td>
            </tr>
            <tr>
                <th>Alasan Penghapusan</th>
                <td colspan="3">{{ $item->disposal_reason ?? '-' }}</td>
            </tr>
            @endif
            <tr>
                <th>Keterangan</th>
                <td colspan="3">{{ $item->description ?? '-' }}</td>
            </tr>
        </table>

        <div class="footer">
            Dicetak: {{ $printed_at }}@if(!empty($printed_by)) — {{ $printed_by }}@endif
        </div>

        @include('inventory.partials.kib_signature', [
            'institution' => $institution,
            'signature_date' => $signature_date ?? null,
            'as_of_date' => $as_of_date ?? null,
        ])
    </div>
</body>
</html>
