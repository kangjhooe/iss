<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Peminjaman Buku - {{ $institution->name ?? 'Perpustakaan' }}</title>
    <style>
        @page { margin: 1.2cm; size: A4 landscape; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 9pt; line-height: 1.3; color: #000; }
        .header { text-align: center; margin-bottom: 12px; }
        .header h1 { font-size: 14pt; font-weight: bold; margin: 0 0 4px 0; }
        .header p { font-size: 8pt; margin: 0; color: #444; }
        .period { font-size: 8pt; margin-bottom: 8px; color: #555; }
        .stats { margin-bottom: 10px; padding: 8px 10px; background: #f5f5f5; border: 1px solid #ddd; font-size: 8pt; }
        .stats table { width: auto; border: none; }
        .stats td { border: none; padding: 2px 12px 2px 0; }
        .stats td:first-child { font-weight: bold; }
        table { width: 100%; border-collapse: collapse; font-size: 8pt; }
        table th, table td { border: 1px solid #333; padding: 4px 6px; text-align: left; }
        table th { background: #e8e8e8; font-weight: bold; }
        table td.num { text-align: center; width: 26px; }
        table td.date { white-space: nowrap; }
        table td.right { text-align: right; }
        .footer { margin-top: 10px; font-size: 7pt; text-align: center; color: #666; }
    </style>
</head>
<body>
    <div class="header">
        @if($institution)
            <h1>{{ $institution->name }}</h1>
            <p>NPSN: {{ $institution->npsn ?? '-' }}</p>
            <p>LAPORAN PEMINJAMAN BUKU PERPUSTAKAAN</p>
        @else
            <h1>LAPORAN PEMINJAMAN BUKU PERPUSTAKAAN</h1>
        @endif
    </div>

    @if(!empty($date_from) || !empty($date_to))
    <div class="period">
        Periode: {{ $date_from ? \Carbon\Carbon::parse($date_from)->locale('id')->isoFormat('D MMM YYYY') : '...' }}
        s/d {{ $date_to ? \Carbon\Carbon::parse($date_to)->locale('id')->isoFormat('D MMM YYYY') : '...' }}
    </div>
    @endif

    <div class="stats">
        <table>
            <tr>
                <td>Total Peminjaman (periode):</td><td>{{ $stats['total_loans'] ?? 0 }}</td>
                <td style="padding-left: 20px;">Masih Dipinjam:</td><td>{{ $stats['still_borrowed'] ?? 0 }}</td>
                <td style="padding-left: 20px;">Terlambat:</td><td>{{ $stats['overdue_count'] ?? 0 }}</td>
                <td style="padding-left: 20px;">Sudah Dikembalikan:</td><td>{{ $stats['returned_count'] ?? 0 }}</td>
                <td style="padding-left: 20px;">Total Denda (Rp):</td><td class="right">{{ number_format($stats['total_fines'] ?? 0, 0, ',', '.') }}</td>
            </tr>
        </table>
    </div>

    <table>
        <thead>
            <tr>
                <th class="num">No</th>
                <th class="date">Tgl Pinjam</th>
                <th class="date">Jatuh Tempo</th>
                <th class="date">Tgl Kembali</th>
                <th>Peminjam</th>
                <th>Tipe</th>
                <th>Judul Buku</th>
                <th>Eksemplar</th>
                <th>Status</th>
                <th class="right">Denda (Rp)</th>
            </tr>
        </thead>
        <tbody>
            @forelse($loans as $index => $loan)
            <tr>
                <td class="num">{{ $index + 1 }}</td>
                <td class="date">{{ $loan->loan_date ? \Carbon\Carbon::parse($loan->loan_date)->locale('id')->format('d/m/Y') : '-' }}</td>
                <td class="date">{{ $loan->due_date ? \Carbon\Carbon::parse($loan->due_date)->locale('id')->format('d/m/Y') : '-' }}</td>
                <td class="date">{{ $loan->returned_at ? \Carbon\Carbon::parse($loan->returned_at)->locale('id')->format('d/m/Y H:i') : '-' }}</td>
                <td>{{ $loan->borrower_name ?? '-' }} {{ $loan->borrower_identifier ? '(' . $loan->borrower_identifier . ')' : '' }}</td>
                <td>{{ $loan->borrower_type ?? '-' }}</td>
                <td>{{ $loan->relationLoaded('copy') && $loan->copy && $loan->copy->relationLoaded('book') ? ($loan->copy->book->title ?? '-') : '-' }}</td>
                <td>{{ $loan->relationLoaded('copy') ? ($loan->copy->copy_code ?? '-') : '-' }}</td>
                <td>{{ $loan->status ?? '-' }}</td>
                <td class="right">{{ number_format((float)($loan->fine_amount ?? 0), 0, ',', '.') }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="10" style="text-align: center; padding: 12px;">Tidak ada data peminjaman dalam periode ini.</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        Dicetak pada {{ $printed_at }} &mdash; {{ $loans->count() }} catatan
    </div>
</body>
</html>
