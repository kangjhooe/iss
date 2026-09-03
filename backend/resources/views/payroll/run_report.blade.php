<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Rekap Gaji — {{ $run->label ?? 'Proses Gaji' }}</title>
    <style>
        @page { margin: 1cm 1.2cm; size: A4 landscape; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 8.5pt; line-height: 1.35; color: #1e293b; }
        @include('partials.print-letterhead-styles')
        @include('partials.print-document-footer-styles')
        .doc-title {
            text-align: center;
            margin: 8px 0 2px;
            font-size: 13pt;
            font-weight: bold;
            letter-spacing: 0.08em;
            color: #0f172a;
        }
        .doc-sub { text-align: center; font-size: 8.5pt; color: #64748b; margin-bottom: 10px; }
        .meta {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
        }
        .meta td { padding: 5px 8px; font-size: 8pt; border: none; }
        .meta .lbl { color: #64748b; font-weight: bold; width: 12%; }
        .stats {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
        }
        .stats td {
            padding: 8px 10px;
            background: #0f172a;
            color: #fff;
            text-align: center;
            border-right: 1px solid #334155;
        }
        .stats td:last-child { border-right: none; }
        .stats .num { display: block; font-size: 11pt; font-weight: bold; margin-top: 2px; }
        .stats .lbl { font-size: 7pt; text-transform: uppercase; letter-spacing: 0.05em; opacity: 0.85; }
        table.data { width: 100%; border-collapse: collapse; font-size: 7.8pt; }
        table.data th {
            background: #0f172a;
            color: #fff;
            padding: 6px 5px;
            text-align: left;
            font-weight: bold;
        }
        table.data td { border: 1px solid #cbd5e1; padding: 5px; vertical-align: top; }
        table.data tr:nth-child(even) td { background: #f8fafc; }
        table.data td.num, table.data th.num { text-align: right; }
        table.data td.center, table.data th.center { text-align: center; }
        table.data tfoot td { font-weight: bold; background: #e2e8f0; }
        .footer { margin-top: 10px; font-size: 7pt; color: #94a3b8; text-align: center; }
        .sig-table { width: 100%; margin-top: 20px; }
        .sig-table td { width: 50%; text-align: center; vertical-align: top; font-size: 8.5pt; }
        .sig-space { height: 48px; }
        .sig-name { font-weight: bold; margin-top: 4px; }
    </style>
</head>
<body>
    {!! $letterheadHtml ?? '' !!}

    <div class="doc-title">REKAP PENGGAJIAN</div>
    <div class="doc-sub">{{ $run->label ?? '—' }} · {{ $period->label ?? '—' }} · Dicetak {{ $printed_at }}</div>

    <table class="meta">
        <tr>
            <td class="lbl">Periode</td>
            <td>{{ $period->start_date?->format('d/m/Y') }} — {{ $period->end_date?->format('d/m/Y') }}</td>
            <td class="lbl">Status</td>
            <td>{{ match($run->status) { 'draft' => 'Draft', 'finalized' => 'Final', 'paid' => 'Dibayar', default => $run->status } }}</td>
        </tr>
        <tr>
            <td class="lbl">Hari kerja</td>
            <td>{{ $period->working_days ?? '—' }}</td>
            <td class="lbl">Jumlah pegawai</td>
            <td>{{ $totals['count'] ?? 0 }} orang</td>
        </tr>
    </table>

    <table class="stats">
        <tr>
            <td><span class="lbl">Total Bruto</span><span class="num">Rp {{ number_format((float) ($totals['gross'] ?? 0), 0, ',', '.') }}</span></td>
            <td><span class="lbl">Total Potongan</span><span class="num">Rp {{ number_format((float) ($totals['deductions'] ?? 0), 0, ',', '.') }}</span></td>
            <td><span class="lbl">Total Dibayarkan</span><span class="num">Rp {{ number_format((float) ($totals['net'] ?? 0), 0, ',', '.') }}</span></td>
        </tr>
    </table>

    <table class="data">
        <thead>
            <tr>
                <th class="center" style="width:24px">No</th>
                <th style="width:80px">NIP</th>
                <th>Nama Pegawai</th>
                <th style="width:50px">Jenis</th>
                <th class="num" style="width:90px">Bruto</th>
                <th class="num" style="width:80px">Potongan</th>
                <th class="num" style="width:90px">Net</th>
                <th class="center" style="width:36px">α</th>
                <th style="width:70px">Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($slips as $index => $slip)
                @php $snap = $slip->attendance_snapshot ?? []; @endphp
                <tr>
                    <td class="center">{{ $index + 1 }}</td>
                    <td>{{ $slip->employee?->nip ?: '—' }}</td>
                    <td>{{ $slip->employee?->name ?? '—' }}</td>
                    <td>{{ $slip->employee?->type ?? '—' }}</td>
                    <td class="num">{{ number_format((float) $slip->gross, 0, ',', '.') }}</td>
                    <td class="num">{{ number_format((float) $slip->total_deductions, 0, ',', '.') }}</td>
                    <td class="num"><strong>{{ number_format((float) $slip->net, 0, ',', '.') }}</strong></td>
                    <td class="center">{{ (int) ($snap['alpha'] ?? 0) }}</td>
                    <td>{{ match($slip->status) { 'draft' => 'Draft', 'final' => 'Final', 'paid' => 'Dibayar', default => $slip->status } }}</td>
                </tr>
            @empty
                <tr><td colspan="9" style="text-align:center;color:#94a3b8">Tidak ada data slip.</td></tr>
            @endforelse
        </tbody>
        @if($slips->isNotEmpty())
        <tfoot>
            <tr>
                <td colspan="4" class="num" style="text-align:right">TOTAL</td>
                <td class="num">{{ number_format((float) ($totals['gross'] ?? 0), 0, ',', '.') }}</td>
                <td class="num">{{ number_format((float) ($totals['deductions'] ?? 0), 0, ',', '.') }}</td>
                <td class="num">{{ number_format((float) ($totals['net'] ?? 0), 0, ',', '.') }}</td>
                <td colspan="2"></td>
            </tr>
        </tfoot>
        @endif
    </table>

    <table class="sig-table">
        <tr>
            <td>
                <div>Mengetahui,</div>
                <div>Kepala Sekolah</div>
                <div class="sig-space"></div>
                <div class="sig-name">(_____________________)</div>
            </td>
            <td>
                <div>{{ $institution->district ?? 'Kota/Kab' }}, {{ now()->locale('id')->isoFormat('D MMMM YYYY') }}</div>
                <div>Bendahara</div>
                <div class="sig-space"></div>
                <div class="sig-name">(_____________________)</div>
            </td>
        </tr>
    </table>

    @include('partials.print-document-footer', ['footer_suffix' => '· Proses #'.$run->id])
</body>
</html>
