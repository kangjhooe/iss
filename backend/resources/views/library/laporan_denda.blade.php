<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Pembayaran Denda - {{ $institution->name ?? 'Perpustakaan' }}</title>
    <style>
        @page { margin: 1.2cm; size: A4 portrait; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 9pt; line-height: 1.3; color: #000; }
        .header { text-align: center; margin-bottom: 12px; }
        .header h1 { font-size: 14pt; font-weight: bold; margin: 0 0 4px 0; }
        .period { font-size: 8pt; margin-bottom: 8px; color: #555; }
        .stats { margin-bottom: 10px; padding: 8px 10px; background: #f5f5f5; border: 1px solid #ddd; font-size: 8pt; }
        table { width: calc(100% - 2px); max-width: calc(100% - 2px); border-collapse: collapse; font-size: 8pt; }
        table th, table td { border: 1px solid #333; padding: 4px 6px; text-align: left; }
        table th { background: #e8e8e8; font-weight: bold; }
        table td.num { text-align: center; width: 28px; }
        table td.date { white-space: nowrap; }
        table td.right { text-align: right; }
        .footer { margin-top: 10px; font-size: 7pt; text-align: center; color: #666; }
        @include('partials.print-letterhead-styles')
        @include('partials.print-signature-styles')
    </style>
</head>
<body>
    @include('partials.print-letterhead', ['institution' => $institution])
    <div class="header">
        <h1>LAPORAN PEMBAYARAN DENDA PERPUSTAKAAN</h1>
    </div>

    @if(!empty($date_from) || !empty($date_to))
    <div class="period">
        Periode: {{ $date_from ? \Carbon\Carbon::parse($date_from)->locale('id')->isoFormat('D MMM YYYY') : '...' }}
        s/d {{ $date_to ? \Carbon\Carbon::parse($date_to)->locale('id')->isoFormat('D MMM YYYY') : '...' }}
    </div>
    @endif

    <div class="stats">
        Total transaksi: <strong>{{ $payments->count() }}</strong>
        &nbsp;|&nbsp; Total denda terkumpul (Rp): <strong>{{ number_format($total_amount ?? 0, 0, ',', '.') }}</strong>
        &nbsp;|&nbsp; Dicetak: {{ $printed_at }}
    </div>

    <table>
        <thead>
            <tr>
                <th class="num">No</th>
                <th class="date">Tanggal Bayar</th>
                <th>ID Pinjam</th>
                <th>Peminjam</th>
                <th>Judul Buku</th>
                <th class="right">Jumlah (Rp)</th>
                <th>Metode</th>
                <th>Catatan</th>
            </tr>
        </thead>
        <tbody>
            @forelse($payments as $i => $payment)
            @php
                $loan = $payment->relationLoaded('loan') ? $payment->loan : null;
                $bookTitle = ($loan && $loan->relationLoaded('copy') && $loan->copy && $loan->copy->relationLoaded('book'))
                    ? ($loan->copy->book->title ?? '-')
                    : '-';
            @endphp
            <tr>
                <td class="num">{{ $i + 1 }}</td>
                <td class="date">{{ $payment->paid_at ? \Carbon\Carbon::parse($payment->paid_at)->locale('id')->format('d/m/Y H:i') : '-' }}</td>
                <td>{{ $payment->loan_id ?? '-' }}</td>
                <td>{{ $loan?->borrower_name ?? '-' }} {{ $loan?->borrower_identifier ? '(' . $loan->borrower_identifier . ')' : '' }}</td>
                <td>{{ $bookTitle }}</td>
                <td class="right">{{ number_format((float) ($payment->amount ?? 0), 0, ',', '.') }}</td>
                <td>{{ $payment->payment_method ?? '-' }}</td>
                <td>{{ $payment->notes ?? '-' }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="8" style="text-align: center; padding: 12px;">Tidak ada data pembayaran denda dalam periode ini.</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    @include('library.partials.signature', [
        'institution' => $institution,
        'kepala_perpustakaan' => $kepala_perpustakaan ?? null,
    ])

    @include('partials.print-document-footer')
</body>
</html>
