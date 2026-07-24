<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kwitansi #{{ $payment->id }}</title>
    <style>
        @page { margin: 1.5cm; size: A4 portrait; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 10pt; line-height: 1.4; color: #000; }
        .doc-title { text-align: center; margin: 8px 0 4px; font-size: 14pt; font-weight: bold; letter-spacing: 0.04em; }
        .doc-sub { text-align: center; font-size: 9pt; color: #444; margin-bottom: 14px; }
        .meta { margin-bottom: 12px; font-size: 9pt; }
        .meta strong { font-weight: bold; }
        table.info { width: 100%; border-collapse: collapse; margin-top: 8px; font-size: 10pt; }
        table.info td { padding: 6px 4px; vertical-align: top; }
        table.info td.label { width: 32%; color: #444; }
        .amount-box {
            margin-top: 14px;
            padding: 10px 12px;
            border: 1px solid #333;
            background: #f5f5f5;
        }
        .amount-box .label { font-size: 9pt; color: #555; }
        .amount-box .value { font-size: 16pt; font-weight: bold; margin-top: 2px; }
        .notes { margin-top: 10px; font-size: 9pt; color: #444; }
        .footer { margin-top: 8px; font-size: 8pt; color: #666; text-align: center; }
        @include('partials.print-letterhead-styles')
        @include('partials.print-signature-styles')
        .standard-signature-wrap { margin-top: 28px; }
        .standard-signature-space { height: 52px; }
        .sig-row { width: 100%; margin-top: 24px; }
        .sig-row td { width: 50%; vertical-align: top; }
        .receiver { font-size: 9pt; text-align: center; }
        .receiver .space { height: 52px; }
        .receiver .name { font-weight: bold; margin-top: 4px; }
    </style>
</head>
<body>
    @include('partials.print-letterhead', ['institution' => $institution])

    <div class="doc-title">KWITANSI PEMBAYARAN</div>
    <div class="doc-sub">No. {{ $payment->id }} · Dicetak {{ $printed_at }}</div>

    @php
        $invoice = $payment->invoice;
        $student = $invoice?->student;
        $methodLabels = [
            'cash' => 'Tunai',
            'transfer' => 'Transfer',
            'other' => 'Lainnya',
        ];
        $methodLabel = $methodLabels[$payment->method ?? ''] ?? ($payment->method ?: '—');
        $amountFormatted = 'Rp ' . number_format((float) $payment->amount, 0, ',', '.');
    @endphp

    <table class="info">
        <tr>
            <td class="label">Tanggal bayar</td>
            <td>{{ $payment->paid_at ? $payment->paid_at->locale('id')->isoFormat('D MMMM YYYY HH:mm') : '—' }}</td>
        </tr>
        <tr>
            <td class="label">Nama siswa</td>
            <td>{{ $student->name ?? '—' }}@if(!empty($student?->nis)) ({{ $student->nis }})@endif</td>
        </tr>
        <tr>
            <td class="label">Kelas</td>
            <td>{{ $invoice?->schoolClass?->name ?? '—' }}</td>
        </tr>
        <tr>
            <td class="label">Tagihan</td>
            <td>{{ $invoice->title ?? '—' }}</td>
        </tr>
        <tr>
            <td class="label">Jenis biaya</td>
            <td>{{ $invoice?->feeType?->name ?? '—' }}</td>
        </tr>
        <tr>
            <td class="label">Metode</td>
            <td>{{ $methodLabel }}</td>
        </tr>
        <tr>
            <td class="label">Referensi</td>
            <td>{{ $payment->reference ?: '—' }}</td>
        </tr>
    </table>

    <div class="amount-box">
        <div class="label">Jumlah diterima</div>
        <div class="value">{{ $amountFormatted }}</div>
    </div>

    @if(!empty($payment->notes))
        <div class="notes">Catatan: {{ $payment->notes }}</div>
    @endif

    <table class="sig-row">
        <tr>
            <td>
                <div class="receiver">
                    <div>Yang membayar,</div>
                    <div class="space"></div>
                    <div class="name">{{ $student->name ?? '___________________' }}</div>
                </div>
            </td>
            <td>
                <div class="standard-signature-wrap">
                    @include('partials.print-signature', [
                        'institution' => $institution,
                        'role' => 'Bendahara / Petugas',
                        'name' => $payment->recorder->name ?? null,
                        'nip' => null,
                        'show_place_date' => true,
                    ])
                </div>
            </td>
        </tr>
    </table>

    <div class="footer">Dokumen ini dicetak dari sistem ISS · Kwitansi #{{ $payment->id }}</div>
</body>
</html>
