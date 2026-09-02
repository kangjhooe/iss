<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>KIB Aset - {{ $asset->asset_number ?? '' }}</title>
    <style>
        @include('inventory.partials.kib_print_styles')
    </style>
</head>
<body>
    <div class="kib-document">
        @include('partials.print-letterhead', ['institution' => $institution])

        <div class="doc-title">
            <h1>Kartu Inventaris Barang (KIB)</h1>
            <p>Unit Aset Individual — {{ $asset->asset_number ?? '-' }}</p>
        </div>

        <table class="kib">
            <tr class="section-row">
                <th colspan="4">Identitas Unit Aset</th>
            </tr>
            <tr>
                <th>Nomor Aset</th>
                <td>{{ $asset->asset_number ?? '-' }}</td>
                <th>Nomor Inventaris</th>
                <td>{{ $asset->inventory_number ?? '-' }}</td>
            </tr>
            <tr>
                <th>Nomor Seri Unit</th>
                <td>{{ $asset->serial_number ?? '-' }}</td>
                <th>Nomor Registrasi</th>
                <td>{{ $asset->registration_number ?? '-' }}</td>
            </tr>
            <tr>
                <th>Kondisi Unit</th>
                <td>{{ $asset->condition ?? '-' }}</td>
                <th>Status Unit</th>
                <td>{{ $asset->status ?? '-' }}</td>
            </tr>
            <tr>
                <th>Lokasi / Ruangan</th>
                <td>{{ $asset->room->name ?? ($asset->building->name ?? ($asset->location_note ?? '-')) }}</td>
                <th>Penanggung Jawab</th>
                <td>{{ $asset->responsibleEmployee->name ?? ($item->responsibleEmployee->name ?? '-') }}</td>
            </tr>

            <tr class="section-row">
                <th colspan="4">Master Barang</th>
            </tr>
            <tr>
                <th>Kode Master</th>
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
                <th>Sumber Dana</th>
                <td>{{ $item->funding_source ?? '-' }}</td>
            </tr>
            <tr>
                <th>Tgl Perolehan</th>
                <td>{{ ($asset->purchase_date ?? $item->purchase_date)?->format('d/m/Y') ?? '-' }}</td>
                <th>Kepemilikan</th>
                <td>{{ $item->ownership_type ?? '-' }}@if(!empty($item->owner_name)) — {{ $item->owner_name }}@endif</td>
            </tr>
            <tr>
                <th>Harga / Nilai Unit</th>
                <td>Rp {{ $asset->purchase_price !== null ? number_format((float) $asset->purchase_price, 0, ',', '.') : ($item->purchase_price !== null ? number_format((float) $item->purchase_price, 0, ',', '.') : '-') }}</td>
                <th>Keterangan</th>
                <td>{{ $asset->description ?? $item->description ?? '-' }}</td>
            </tr>
        </table>

        @include('inventory.partials.kib_signature', [
            'institution' => $institution,
            'signature_date' => $signature_date ?? null,
            'as_of_date' => $as_of_date ?? null,
        ])

        <div class="footer print-document-footer">
            Dicetak: {{ $printed_at }}@if(!empty($printed_by)) — {{ $printed_by }}@endif
        </div>
    </div>
</body>
</html>
