<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Label QR Aset - {{ $title ?? 'Inventaris' }}</title>
    <style>
        @page { margin: 8mm; size: A4 portrait; }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 8.5pt;
            color: #0f172a;
            line-height: 1.35;
        }
        .page { page-break-after: always; }
        .page:last-child { page-break-after: auto; }
        .cards-grid { width: 100%; border-collapse: collapse; table-layout: fixed; }
        .card-cell { width: 50%; padding: 3mm; vertical-align: top; }
        .card {
            border: 1px solid #cbd5e1;
            border-radius: 4px;
            overflow: hidden;
            height: 63mm;
            background: #ffffff;
        }
        .card-head {
            background: #1e40af;
            color: #ffffff;
            font-size: 6.5pt;
            font-weight: bold;
            letter-spacing: 0.4px;
            text-transform: uppercase;
            padding: 3px 8px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .card-body { width: 100%; border-collapse: collapse; }
        .qr-cell {
            width: 36mm;
            text-align: center;
            vertical-align: middle;
            padding: 5px 4px 4px 6px;
        }
        .qr-frame {
            width: 34mm;
            margin: 0 auto;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 3px;
            padding: 3px;
        }
        .qr-frame img { width: 30mm; height: 30mm; display: block; margin: 0 auto; }
        .scan-hint {
            font-size: 5.5pt;
            color: #64748b;
            margin-top: 2px;
            letter-spacing: 0.2px;
        }
        .meta-cell {
            vertical-align: middle;
            padding: 6px 8px 6px 2px;
        }
        .primary-id {
            font-size: 11pt;
            font-weight: bold;
            color: #1e3a8a;
            margin-bottom: 4px;
            line-height: 1.15;
            word-break: break-all;
        }
        .meta-rows { width: 100%; border-collapse: collapse; }
        .meta-rows td { padding: 1px 0; vertical-align: top; font-size: 7.5pt; }
        .meta-rows .lbl {
            width: 14mm;
            color: #64748b;
            padding-right: 3px;
            white-space: nowrap;
        }
        .meta-rows .val {
            color: #1e293b;
            font-weight: bold;
            word-break: break-word;
        }
        .card-foot {
            border-top: 1px solid #e2e8f0;
            background: #f8fafc;
            color: #475569;
            font-size: 6pt;
            padding: 2px 8px;
            text-align: right;
            letter-spacing: 0.3px;
            text-transform: uppercase;
        }
    </style>
</head>
<body>
    @foreach($pages as $pageCards)
    <div class="page">
        <table class="cards-grid">
            @for($row = 0; $row < 4; $row++)
            <tr>
                @for($col = 0; $col < 2; $col++)
                @php $card = $pageCards[$row * 2 + $col] ?? null; @endphp
                <td class="card-cell">
                    @if($card)
                    <div class="card">
                        <div class="card-head">{{ $institutionName }}</div>
                        <table class="card-body">
                            <tr>
                                <td class="qr-cell">
                                    <div class="qr-frame">
                                        <img src="{{ $card['qr_code'] }}" alt="QR" />
                                    </div>
                                    <div class="scan-hint">Scan untuk identifikasi</div>
                                </td>
                                <td class="meta-cell">
                                    <div class="primary-id">{{ $card['asset_number'] }}</div>
                                    <table class="meta-rows">
                                        <tr>
                                            <td class="lbl">Barang</td>
                                            <td class="val">{{ $card['item_name'] ?? '-' }}</td>
                                        </tr>
                                        @if(!empty($card['item_code']))
                                        <tr>
                                            <td class="lbl">Kode</td>
                                            <td class="val">{{ $card['item_code'] }}</td>
                                        </tr>
                                        @endif
                                        @if(!empty($card['inventory_number']))
                                        <tr>
                                            <td class="lbl">No. Inv.</td>
                                            <td class="val">{{ $card['inventory_number'] }}</td>
                                        </tr>
                                        @endif
                                        @if(!empty($card['serial_number']))
                                        <tr>
                                            <td class="lbl">Serial</td>
                                            <td class="val">{{ $card['serial_number'] }}</td>
                                        </tr>
                                        @endif
                                        @if(!empty($card['room_name']))
                                        <tr>
                                            <td class="lbl">Lokasi</td>
                                            <td class="val">{{ $card['room_name'] }}</td>
                                        </tr>
                                        @endif
                                    </table>
                                </td>
                            </tr>
                        </table>
                        <div class="card-foot">Label Aset Inventaris</div>
                    </div>
                    @endif
                </td>
                @endfor
            </tr>
            @endfor
        </table>
    </div>
    @endforeach
</body>
</html>
