<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kartu QR Absensi - {{ $title ?? 'Absensi' }}</title>
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
            background: #047857;
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
            font-size: 11.5pt;
            font-weight: bold;
            color: #065f46;
            margin-bottom: 5px;
            line-height: 1.15;
            word-break: break-word;
        }
        .meta-rows { width: 100%; border-collapse: collapse; }
        .meta-rows td { padding: 1px 0; vertical-align: top; font-size: 7.5pt; }
        .meta-rows .lbl {
            width: 12mm;
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
                                    <div class="scan-hint">Scan saat absensi</div>
                                </td>
                                <td class="meta-cell">
                                    <div class="primary-id">{{ $card['name'] }}</div>
                                    <table class="meta-rows">
                                        @if(!empty($card['nis']))
                                        <tr>
                                            <td class="lbl">NIS</td>
                                            <td class="val">{{ $card['nis'] }}</td>
                                        </tr>
                                        @endif
                                        @if(!empty($card['nip']))
                                        <tr>
                                            <td class="lbl">NIP</td>
                                            <td class="val">{{ $card['nip'] }}</td>
                                        </tr>
                                        @endif
                                        @if(!empty($card['class_name']))
                                        <tr>
                                            <td class="lbl">Kelas</td>
                                            <td class="val">{{ $card['class_name'] }}</td>
                                        </tr>
                                        @endif
                                        @if(!empty($card['type']))
                                        <tr>
                                            <td class="lbl">Jenis</td>
                                            <td class="val">{{ $card['type'] }}</td>
                                        </tr>
                                        @endif
                                    </table>
                                </td>
                            </tr>
                        </table>
                        <div class="card-foot">Kartu QR Absensi</div>
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
