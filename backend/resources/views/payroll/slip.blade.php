<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Slip Gaji — {{ $employee->name ?? 'Pegawai' }}</title>
    <style>
        @page { margin: 1.2cm 1.4cm; size: A4 portrait; }
        * { box-sizing: border-box; }
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 9.5pt;
            line-height: 1.45;
            color: #1e293b;
            margin: 0;
            padding: 0;
        }
        @include('partials.print-letterhead-styles')
        .doc-wrap { margin-top: 6px; }
        .doc-title {
            text-align: center;
            margin: 10px 0 2px;
            font-size: 15pt;
            font-weight: bold;
            letter-spacing: 0.12em;
            color: #0f172a;
        }
        .doc-subtitle {
            text-align: center;
            font-size: 9pt;
            color: #64748b;
            margin-bottom: 14px;
        }
        .badge-period {
            display: inline-block;
            background: #eff6ff;
            color: #1d4ed8;
            border: 1px solid #bfdbfe;
            border-radius: 999px;
            padding: 3px 12px;
            font-size: 8.5pt;
            font-weight: bold;
        }
        .info-grid {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 14px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
        }
        .info-grid td {
            padding: 7px 10px;
            vertical-align: top;
            border-bottom: 1px solid #e2e8f0;
        }
        .info-grid tr:last-child td { border-bottom: none; }
        .info-grid .label {
            width: 22%;
            color: #64748b;
            font-size: 8.5pt;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }
        .info-grid .value { font-weight: 600; color: #0f172a; }
        .columns {
            width: 100%;
            border-collapse: collapse;
            margin-top: 4px;
        }
        .columns th {
            background: #0f172a;
            color: #fff;
            font-size: 8.5pt;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            padding: 8px 10px;
            text-align: left;
        }
        .columns th.right, .columns td.right { text-align: right; }
        .columns td {
            padding: 7px 10px;
            border-bottom: 1px solid #e2e8f0;
            vertical-align: top;
        }
        .columns tr:nth-child(even) td { background: #f8fafc; }
        .col-half { width: 50%; vertical-align: top; }
        .col-gap { width: 12px; }
        .summary-box {
            margin-top: 16px;
            border: 2px solid #0f172a;
            border-radius: 8px;
            overflow: hidden;
        }
        .summary-row {
            width: 100%;
            border-collapse: collapse;
        }
        .summary-row td {
            padding: 8px 14px;
            font-size: 9.5pt;
        }
        .summary-row .label { color: #475569; }
        .summary-row .amount { text-align: right; font-weight: bold; }
        .summary-row.total {
            background: #0f172a;
            color: #fff;
        }
        .summary-row.total td {
            padding: 12px 14px;
            font-size: 11pt;
        }
        .attendance-note {
            margin-top: 12px;
            padding: 8px 10px;
            background: #fffbeb;
            border: 1px solid #fde68a;
            border-radius: 6px;
            font-size: 8.5pt;
            color: #92400e;
        }
        .footer-note {
            margin-top: 14px;
            font-size: 8pt;
            color: #94a3b8;
            text-align: center;
        }
        @include('partials.print-signature-styles')
        .sig-table { width: 100%; margin-top: 28px; }
        .sig-table td { width: 50%; text-align: center; vertical-align: top; font-size: 9pt; }
        .sig-space { height: 56px; }
        .sig-name { font-weight: bold; margin-top: 4px; color: #0f172a; }
        .sig-role { color: #64748b; font-size: 8.5pt; }
        .watermark-draft {
            position: fixed;
            top: 45%;
            left: 18%;
            font-size: 48pt;
            color: rgba(220, 38, 38, 0.12);
            font-weight: bold;
            transform: rotate(-25deg);
            letter-spacing: 0.2em;
        }
    </style>
</head>
<body>
    @if(($slip->status ?? '') === 'draft')
        <div class="watermark-draft">DRAFT</div>
    @endif

    {!! $letterheadHtml ?? '' !!}

    <div class="doc-wrap">
        <div class="doc-title">SLIP GAJI</div>
        <div class="doc-subtitle">
            <span class="badge-period">{{ $period->label ?? '—' }}</span>
            &nbsp;·&nbsp; Dicetak {{ $printed_at }}
        </div>

        <table class="info-grid">
            <tr>
                <td class="label">Nama</td>
                <td class="value">{{ $employee->name ?? '—' }}</td>
                <td class="label">NIP</td>
                <td class="value">{{ $employee->nip ?: '—' }}</td>
            </tr>
            <tr>
                <td class="label">Jabatan</td>
                <td class="value">{{ $employee->type ?? '—' }}</td>
                <td class="label">Status</td>
                <td class="value">{{ $employee->employment_status ?? '—' }}</td>
            </tr>
            <tr>
                <td class="label">Periode</td>
                <td class="value" colspan="3">
                    {{ $period->start_date?->locale('id')->isoFormat('D MMM YYYY') }}
                    s/d
                    {{ $period->end_date?->locale('id')->isoFormat('D MMM YYYY') }}
                    ({{ $period->working_days ?? '—' }} hari kerja)
                </td>
            </tr>
        </table>

        <table class="columns">
            <tr>
                <th class="col-half">Pendapatan</th>
                <th class="col-gap"></th>
                <th class="col-half">Potongan</th>
            </tr>
            <tr>
                <td class="col-half" style="padding:0;border:none;vertical-align:top">
                    <table style="width:100%;border-collapse:collapse">
                        @forelse($earnings as $line)
                            <tr>
                                <td>{{ $line->label }}</td>
                                <td class="right" style="width:38%;white-space:nowrap">Rp {{ number_format((float) $line->amount, 0, ',', '.') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="2" style="color:#94a3b8">—</td></tr>
                        @endforelse
                    </table>
                </td>
                <td class="col-gap" style="border:none"></td>
                <td class="col-half" style="padding:0;border:none;vertical-align:top">
                    <table style="width:100%;border-collapse:collapse">
                        @forelse($deductions as $line)
                            <tr>
                                <td>{{ $line->label }}</td>
                                <td class="right" style="width:38%;white-space:nowrap">Rp {{ number_format((float) $line->amount, 0, ',', '.') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="2" style="color:#94a3b8">—</td></tr>
                        @endforelse
                    </table>
                </td>
            </tr>
        </table>

        <div class="summary-box">
            <table class="summary-row">
                <tr>
                    <td class="label">Total Pendapatan</td>
                    <td class="amount">Rp {{ number_format((float) $slip->gross, 0, ',', '.') }}</td>
                </tr>
                <tr>
                    <td class="label">Total Potongan</td>
                    <td class="amount">Rp {{ number_format((float) $slip->total_deductions, 0, ',', '.') }}</td>
                </tr>
                <tr class="total">
                    <td>Gaji Diterima</td>
                    <td class="amount">Rp {{ number_format((float) $slip->net, 0, ',', '.') }}</td>
                </tr>
            </table>
        </div>

        @php
            $snap = $slip->attendance_snapshot ?? [];
            $alpha = (int) ($snap['alpha'] ?? 0);
            $tanpaGaji = (int) ($snap['tanpa_gaji'] ?? 0);
        @endphp
        @if($alpha > 0 || $tanpaGaji > 0)
            <div class="attendance-note">
                Rekap kehadiran periode:
                @if($alpha > 0) Alpha {{ $alpha }} hari.@endif
                @if($tanpaGaji > 0) Cuti tanpa gaji {{ $tanpaGaji }} hari.@endif
            </div>
        @endif

        @if(!empty($slip->notes))
            <div class="attendance-note" style="background:#f0f9ff;border-color:#bae6fd;color:#0c4a6e">
                Catatan: {{ $slip->notes }}
            </div>
        @endif

        <table class="sig-table">
            <tr>
                <td>
                    <div>Pegawai,</div>
                    <div class="sig-space"></div>
                    <div class="sig-name">{{ $employee->name ?? '—' }}</div>
                    <div class="sig-role">NIP: {{ $employee->nip ?: '—' }}</div>
                </td>
                <td>
                    <div>Bendahara,</div>
                    <div class="sig-space"></div>
                    <div class="sig-name">(_____________________)</div>
                    <div class="sig-role">{{ $institution->name ?? '' }}</div>
                </td>
            </tr>
        </table>

        <div class="footer-note">
            Dokumen ini dicetak dari sistem ISS · Slip No. {{ $slip->id }}
        </div>
    </div>
</body>
</html>
