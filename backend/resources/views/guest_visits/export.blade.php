<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Buku Tamu - {{ $institution->name ?? 'Export' }}</title>
    <style>
        @page { margin: 1.2cm; size: A4 landscape; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 9pt; line-height: 1.3; color: #000; }
        .header { text-align: center; margin-bottom: 12px; }
        .header h1 { font-size: 14pt; font-weight: bold; margin: 0 0 4px 0; }
        .header p { font-size: 8pt; margin: 0; color: #444; }
        .period { font-size: 8pt; margin-bottom: 10px; color: #555; }
        table { width: calc(100% - 2px); max-width: calc(100% - 2px); border-collapse: collapse; font-size: 8pt; }
        table th, table td { border: 1px solid #333; padding: 4px 6px; text-align: left; }
        table th { background: #e8e8e8; font-weight: bold; }
        table td.num { text-align: center; width: 28px; }
        table td.time { white-space: nowrap; }
        .footer { margin-top: 10px; font-size: 7pt; text-align: center; color: #666; }
        @include('partials.print-letterhead-styles')
        @include('partials.print-signature-styles')
    </style>
</head>
<body>
    @include('partials.print-letterhead', ['institution' => $institution])
    <div class="header">
        <h1>BUKU TAMU</h1>
    </div>

    @if($date_from || $date_to)
    <div class="period">
        Periode: {{ $date_from ? \Carbon\Carbon::parse($date_from)->locale('id')->isoFormat('D MMM YYYY') : '...' }}
        s/d {{ $date_to ? \Carbon\Carbon::parse($date_to)->locale('id')->isoFormat('D MMM YYYY') : '...' }}
    </div>
    @endif

    <table>
        <thead>
            <tr>
                <th class="num">No</th>
                <th>Nama Tamu</th>
                <th>No. Identitas</th>
                <th>Instansi / Asal</th>
                <th>Tujuan Kunjungan</th>
                <th>Ditemui</th>
                <th class="time">Waktu Masuk</th>
                <th class="time">Waktu Keluar</th>
            </tr>
        </thead>
        <tbody>
            @forelse($visits as $index => $visit)
            <tr>
                <td class="num">{{ $index + 1 }}</td>
                <td>{{ $visit->nama_tamu }}</td>
                <td>{{ $visit->no_identitas ?? '-' }}</td>
                <td>{{ $visit->instansi_asal ?? '-' }}</td>
                <td>{{ $visit->tujuan_kunjungan }}</td>
                <td>{{ $visit->orang_ditemui ?? '-' }}</td>
                <td class="time">{{ $visit->waktu_masuk ? $visit->waktu_masuk->locale('id')->format('d/m/Y H:i') : '-' }}</td>
                <td class="time">{{ $visit->waktu_keluar ? $visit->waktu_keluar->locale('id')->format('d/m/Y H:i') : '-' }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="8" style="text-align: center; padding: 12px;">Tidak ada data kunjungan.</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        Dicetak pada {{ now()->locale('id')->isoFormat('D MMMM YYYY HH:mm') }} &mdash; {{ $visits->count() }} catatan
    </div>
    <div class="standard-signature-wrap">
        <div class="standard-signature-left"></div>
        <div class="standard-signature-right">
            @include('partials.print-signature', ['institution' => $institution])
        </div>
    </div>
</body>
</html>
