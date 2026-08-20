<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kartu QR Absensi - {{ $title ?? 'Absensi' }}</title>
    <style>
        @page { margin: 10mm; size: A4 portrait; }
        * { box-sizing: border-box; }
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 9pt; color: #1e293b; margin: 0; padding: 0; }
        .page { page-break-after: always; }
        .page:last-child { page-break-after: auto; }
        .cards-grid { width: 100%; border-collapse: collapse; table-layout: fixed; }
        .card-cell { width: 50%; padding: 5px; vertical-align: top; }
        .card {
            border: 1.5px solid #047857;
            border-radius: 6px;
            overflow: hidden;
            height: 62mm;
        }
        .card-inner { width: 100%; border-collapse: collapse; }
        .card-qr {
            width: 42%;
            text-align: center;
            vertical-align: middle;
            padding: 8px 6px;
        }
        .card-qr img { width: 38mm; height: 38mm; }
        .card-meta { vertical-align: middle; padding: 8px 10px 8px 4px; }
        .card-school { font-size: 7pt; color: #047857; font-weight: bold; margin-bottom: 4px; text-transform: uppercase; }
        .card-name { font-size: 11pt; font-weight: bold; margin: 0 0 6px 0; line-height: 1.2; }
        .card-row { font-size: 8pt; margin: 2px 0; color: #334155; }
        .card-label { color: #64748b; }
        .card-foot { font-size: 6.5pt; color: #94a3b8; margin-top: 8px; }
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
                        <table class="card-inner">
                            <tr>
                                <td class="card-qr">
                                    <img src="{{ $card['qr_code'] }}" alt="QR" />
                                </td>
                                <td class="card-meta">
                                    <div class="card-school">{{ $institutionName }}</div>
                                    <div class="card-name">{{ $card['name'] }}</div>
                                    @if(!empty($card['nis']))
                                    <div class="card-row"><span class="card-label">NIS</span> {{ $card['nis'] }}</div>
                                    @endif
                                    @if(!empty($card['nip']))
                                    <div class="card-row"><span class="card-label">NIP</span> {{ $card['nip'] }}</div>
                                    @endif
                                    @if(!empty($card['class_name']))
                                    <div class="card-row"><span class="card-label">Kelas</span> {{ $card['class_name'] }}</div>
                                    @endif
                                    @if(!empty($card['type']))
                                    <div class="card-row"><span class="card-label">Jenis</span> {{ $card['type'] }}</div>
                                    @endif
                                    <div class="card-foot">Kartu QR absensi — jangan dipindahtangankan</div>
                                </td>
                            </tr>
                        </table>
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
