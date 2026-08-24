<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>{{ $report_title }} - {{ $institution->name ?? 'Institusi' }}</title>
    <style>
        @page { margin: 1.2cm 1.2cm 1.6cm 1.2cm; size: A4 {{ $orientation === 'landscape' ? 'landscape' : 'portrait' }}; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 9pt; line-height: 1.35; color: #111; }
        .header { text-align: center; margin-bottom: 10px; }
        .header h1 { font-size: 13pt; font-weight: bold; margin: 0 0 2px; text-transform: uppercase; }
        .header .subtitle { font-size: 9pt; color: #444; margin: 0; }
        .meta {
            margin-bottom: 10px;
            padding: 6px 8px;
            background: #f5f5f5;
            border: 1px solid #ddd;
            font-size: 8pt;
        }
        .meta table { width: 100%; border: none; border-collapse: collapse; }
        .meta td { border: none; padding: 1px 10px 1px 0; vertical-align: top; }
        .meta td.lbl { font-weight: bold; white-space: nowrap; width: 1%; }
        .stats { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
        .stats td {
            width: 20%;
            text-align: center;
            border: 1px solid #cbd5e1;
            padding: 7px 4px;
            background: #f8fafc;
        }
        .stats .val { font-size: 13pt; font-weight: bold; }
        .stats .lbl { font-size: 7.5pt; color: #555; text-transform: uppercase; margin-top: 2px; }
        .section-title {
            font-size: 10pt;
            font-weight: bold;
            margin: 14px 0 5px;
            padding: 2px 0;
            border-bottom: 1px solid #333;
            text-transform: uppercase;
        }
        table.data { width: 100%; border-collapse: collapse; font-size: 8pt; margin-bottom: 6px; }
        table.data th, table.data td { border: 1px solid #333; padding: 3px 5px; text-align: left; vertical-align: top; }
        table.data th { background: #e8e8e8; font-weight: bold; }
        table.data td.num, table.data th.num { text-align: right; }
        table.data td.center, table.data th.center { text-align: center; }
        table.data tr.group td { background: #e2e8f0; font-weight: bold; }
        .empty { text-align: center; padding: 8px; color: #666; }
        .footer { margin-top: 10px; font-size: 7.5pt; text-align: center; color: #666; }
        @include('partials.print-letterhead-styles')
        @include('partials.print-signature-styles')
        .standard-signature-wrap { margin-top: 18px; }
        .standard-signature-space { height: 48px; }
    </style>
</head>
<body>
    @include('partials.print-letterhead', ['institution' => $institution])

    <div class="header">
        <h1>{{ $report_title }}</h1>
        <p class="subtitle">Unit Kesehatan Sekolah (UKS)</p>
    </div>

    <div class="meta">
        <table>
            <tr>
                <td class="lbl">Periode / filter:</td>
                <td>{{ $filter_legend ? implode(' · ', $filter_legend) : 'Semua data' }}</td>
                <td class="lbl">Dicetak:</td>
                <td>{{ $printed_at }}@if(!empty($printed_by)) — {{ $printed_by }}@endif</td>
            </tr>
        </table>
    </div>

    @php
        $summary = $summary ?? [];
        $statusLabels = ['selesai' => 'Selesai', 'observasi' => 'Observasi', 'rujuk' => 'Rujuk'];
        $monthNames = [1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    @endphp

    <table class="stats">
        <tr>
            <td>
                <div class="val">{{ $summary['total_visits'] ?? 0 }}</div>
                <div class="lbl">Total Kunjungan</div>
            </td>
            <td>
                <div class="val">{{ $summary['students_served'] ?? 0 }}</div>
                <div class="lbl">Siswa Dilayani</div>
            </td>
            <td>
                <div class="val">{{ $summary['visits_this_month'] ?? 0 }}</div>
                <div class="lbl">Bulan Ini</div>
            </td>
            <td>
                <div class="val">{{ $summary['total_observation'] ?? 0 }}</div>
                <div class="lbl">Observasi</div>
            </td>
            <td>
                <div class="val">{{ $summary['total_referral'] ?? 0 }}</div>
                <div class="lbl">Rujukan</div>
            </td>
        </tr>
    </table>

    @if(($mode ?? 'summary') === 'detail')
        <div class="section-title">1. Rekap per Siswa</div>
        <table class="data">
            <thead>
                <tr>
                    <th class="center" style="width:28px">No</th>
                    <th>NIS</th>
                    <th>Nama</th>
                    <th>Kelas</th>
                    <th class="num">Kunjungan</th>
                    <th class="num">Rujukan</th>
                </tr>
            </thead>
            <tbody>
                @forelse($student_groups as $className => $rows)
                    <tr class="group"><td colspan="6">Kelas {{ $className }} · {{ count($rows) }} siswa</td></tr>
                    @foreach($rows as $i => $row)
                    <tr>
                        <td class="center">{{ $i + 1 }}</td>
                        <td>{{ $row['nis'] ?: '—' }}</td>
                        <td>{{ $row['student_name'] }}</td>
                        <td>{{ $row['class_name'] }}</td>
                        <td class="num">{{ $row['visit_count'] }}</td>
                        <td class="num">{{ $row['referral_count'] }}</td>
                    </tr>
                    @endforeach
                @empty
                    <tr><td colspan="6" class="empty">Belum ada data kunjungan.</td></tr>
                @endforelse
            </tbody>
        </table>

        <div class="section-title">2. Daftar Kunjungan</div>
        <table class="data">
            <thead>
                <tr>
                    <th class="center" style="width:28px">No</th>
                    <th>Tanggal</th>
                    <th>NIS</th>
                    <th>Nama</th>
                    <th>Kelas</th>
                    <th>Jenis</th>
                    <th>Status</th>
                    <th>Keluhan / Tindakan</th>
                    <th>Petugas</th>
                </tr>
            </thead>
            <tbody>
                @forelse($items as $i => $row)
                <tr>
                    <td class="center">{{ $i + 1 }}</td>
                    <td>{{ !empty($row['visit_date']) ? \Carbon\Carbon::parse($row['visit_date'])->locale('id')->isoFormat('D MMM YYYY') : '—' }}</td>
                    <td>{{ $row['nis'] ?: '—' }}</td>
                    <td>{{ $row['student_name'] }}</td>
                    <td>{{ $row['class_name'] }}</td>
                    <td>{{ $row['visit_type'] }}</td>
                    <td>{{ $statusLabels[$row['status'] ?? ''] ?? ($row['status'] ?: '—') }}</td>
                    <td>{{ $row['complaint'] ?: ($row['action_taken'] ?: '—') }}</td>
                    <td>{{ $row['recorder_name'] ?: '—' }}</td>
                </tr>
                @empty
                    <tr><td colspan="9" class="empty">Belum ada data kunjungan.</td></tr>
                @endforelse
            </tbody>
        </table>
        @if(!empty($truncated))
            <p class="footer">Daftar dibatasi 2.000 kunjungan terbaru. Persempit filter untuk data lengkap.</p>
        @endif
    @else
        <div class="section-title">1. Rekap per Kelas</div>
        <table class="data">
            <thead>
                <tr>
                    <th class="center" style="width:28px">No</th>
                    <th>Kelas</th>
                    <th class="num">Kunjungan</th>
                    <th class="num">Rujukan</th>
                </tr>
            </thead>
            <tbody>
                @forelse($by_class as $i => $row)
                <tr>
                    <td class="center">{{ $i + 1 }}</td>
                    <td>{{ $row['class_name'] }}</td>
                    <td class="num">{{ $row['visit_count'] }}</td>
                    <td class="num">{{ $row['referral_count'] }}</td>
                </tr>
                @empty
                    <tr><td colspan="4" class="empty">Belum ada data.</td></tr>
                @endforelse
            </tbody>
        </table>

        <div class="section-title">2. Rekap per Bulan ({{ $by_month['year'] ?? '' }})</div>
        <table class="data">
            <thead>
                <tr>
                    <th>Bulan</th>
                    <th class="num">Kunjungan</th>
                    <th class="num">Rujukan</th>
                </tr>
            </thead>
            <tbody>
                @foreach(($by_month['months'] ?? []) as $row)
                <tr>
                    <td>{{ $monthNames[$row['month']] ?? $row['month'] }}</td>
                    <td class="num">{{ $row['visit_count'] }}</td>
                    <td class="num">{{ $row['referral_count'] }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <div class="section-title">3. Per Jenis Kunjungan</div>
        <table class="data">
            <thead>
                <tr>
                    <th class="center" style="width:28px">No</th>
                    <th>Jenis</th>
                    <th class="num">Jumlah</th>
                </tr>
            </thead>
            <tbody>
                @forelse($by_type as $i => $row)
                <tr>
                    <td class="center">{{ $i + 1 }}</td>
                    <td>{{ $row['type_name'] }}</td>
                    <td class="num">{{ $row['visit_count'] }}</td>
                </tr>
                @empty
                    <tr><td colspan="3" class="empty">Belum ada data.</td></tr>
                @endforelse
            </tbody>
        </table>

        <div class="section-title">4. Per Status</div>
        <table class="data">
            <thead>
                <tr>
                    <th>Status</th>
                    <th class="num">Jumlah</th>
                </tr>
            </thead>
            <tbody>
                @forelse($by_status as $row)
                <tr>
                    <td>{{ $statusLabels[$row['status'] ?? ''] ?? ($row['status'] ?: '—') }}</td>
                    <td class="num">{{ $row['visit_count'] }}</td>
                </tr>
                @empty
                    <tr><td colspan="2" class="empty">Belum ada data.</td></tr>
                @endforelse
            </tbody>
        </table>
    @endif

    <div class="footer">
        Dicetak pada {{ $printed_at }}
        @if(!empty($printed_by)) &mdash; oleh {{ $printed_by }}@endif
    </div>

    <div class="standard-signature-wrap">
        <div class="standard-signature-left">
            @include('partials.print-signature', [
                'institution' => $institution,
                'role' => 'Mengetahui, '.$principal_role,
                'name' => $principal_name,
                'nip' => $principal_nip,
                'show_place_date' => false,
            ])
        </div>
        <div class="standard-signature-right">
            @include('partials.print-signature', [
                'institution' => $institution,
                'role' => $uks_role,
                'name' => $uks_name,
                'nip' => $uks_nip,
                'date' => now()->locale('id')->translatedFormat('d F Y'),
            ])
        </div>
    </div>
</body>
</html>
