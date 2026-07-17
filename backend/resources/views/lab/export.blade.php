<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Laporan Laboratorium</title>
    <style>
        @page { margin: 1.5cm 1.4cm; size: A4 portrait; }
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 10px;
            color: #1a1a1a;
            line-height: 1.4;
        }
        .kop {
            width: 100%;
            border-bottom: 2.5px solid #0f172a;
            padding-bottom: 10px;
            margin-bottom: 4px;
        }
        .kop-inner { width: 100%; }
        .kop-inner td { vertical-align: middle; border: none; padding: 0; }
        .logo-cell { width: 64px; }
        .logo-cell img { width: 58px; height: 58px; object-fit: contain; }
        .kop-text { text-align: center; }
        .kop-text .inst-name {
            font-size: 14px;
            font-weight: bold;
            text-transform: uppercase;
            margin: 0 0 2px;
            letter-spacing: 0.3px;
        }
        .kop-text .inst-foundation {
            font-size: 10px;
            font-weight: 600;
            text-transform: uppercase;
            margin: 0 0 2px;
            letter-spacing: 0.2px;
        }
        .kop-text .inst-meta {
            font-size: 8.5px;
            color: #444;
            margin: 0;
        }
        .kop-line {
            border-bottom: 0.8px solid #0f172a;
            margin-bottom: 14px;
            height: 3px;
        }
        .doc-title {
            text-align: center;
            margin: 8px 0 4px;
        }
        .doc-title h1 {
            font-size: 13px;
            margin: 0;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .doc-title .subtitle {
            font-size: 10px;
            color: #333;
            margin: 3px 0 0;
        }
        .info-box {
            width: calc(100% - 2px);
            max-width: calc(100% - 2px);
            margin: 12px 0 14px;
            border: 1px solid #cbd5e1;
            background: #f8fafc;
        }
        .info-box td {
            border: none;
            padding: 4px 8px;
            font-size: 9.5px;
            vertical-align: top;
        }
        .info-box .lbl {
            width: 28%;
            color: #475569;
            font-weight: bold;
        }
        .section-title {
            font-size: 11px;
            font-weight: bold;
            margin: 16px 0 6px;
            padding: 4px 8px;
            background: #0f172a;
            color: #fff;
            text-transform: uppercase;
            letter-spacing: 0.4px;
        }
        .summary {
            width: calc(100% - 2px);
            max-width: calc(100% - 2px);
            margin-bottom: 8px;
            border-collapse: collapse;
        }
        .summary td {
            border: 1px solid #cbd5e1;
            padding: 6px 8px;
            text-align: center;
            width: 25%;
            background: #fff;
        }
        .summary .num {
            display: block;
            font-size: 14px;
            font-weight: bold;
            color: #0f172a;
        }
        .summary .cap {
            font-size: 8px;
            color: #64748b;
            text-transform: uppercase;
        }
        table.data {
            width: calc(100% - 2px);
            max-width: calc(100% - 2px);
            border-collapse: collapse;
            margin-bottom: 4px;
        }
        table.data th, table.data td {
            border: 1px solid #94a3b8;
            padding: 4px 5px;
            text-align: left;
            font-size: 9px;
        }
        table.data th {
            background: #e2e8f0;
            font-weight: bold;
            text-align: center;
        }
        table.data td.center { text-align: center; }
        table.data td.num { text-align: center; width: 28px; }
        .empty { color: #64748b; font-style: italic; text-align: center; }
        .sign-block {
            width: 100%;
            margin-top: 28px;
            page-break-inside: avoid;
        }
        .sign-block td {
            border: none;
            vertical-align: top;
            width: 50%;
            padding: 0 12px;
        }
        .sign-box { text-align: center; font-size: 9.5px; }
        .sign-box .place {
            margin-bottom: 4px;
        }
        .sign-box .role {
            font-weight: bold;
            margin-bottom: 48px;
        }
        .sign-box .name {
            font-weight: bold;
            text-decoration: underline;
            margin: 0;
        }
        .sign-box .nip {
            margin: 2px 0 0;
            font-size: 9px;
        }
        .footer {
            margin-top: 18px;
            border-top: 1px solid #cbd5e1;
            padding-top: 6px;
            font-size: 7.5px;
            color: #64748b;
            text-align: center;
        }
        .meta-period {
            text-align: center;
            font-size: 9px;
            color: #475569;
            margin-bottom: 10px;
        }
        @include('partials.print-letterhead-styles')
        .cell-note { color: #555; font-size: 8px; margin-top: 2px; }
    </style>
</head>
<body>
@php
    $institution = $institution ?? ($room->institution ?? null);
    $pj = ($mode ?? '') === 'single' ? ($room->responsibleEmployee ?? null) : null;
    $printedAt = now()->locale('id')->translatedFormat('d F Y H:i');
    $fromLabel = \Carbon\Carbon::parse($from)->locale('id')->translatedFormat('d F Y');
    $toLabel = \Carbon\Carbon::parse($to)->locale('id')->translatedFormat('d F Y');
    $city = $institution->district ?? $institution->city ?? '';
    $signDate = now()->locale('id')->translatedFormat('d F Y');
@endphp

@include('partials.print-letterhead', ['institution' => $institution])

@if(($mode ?? '') === 'single')
    <div class="doc-title">
        <h1>Laporan Laboratorium</h1>
        <p class="subtitle">{{ $room->name }}@if($room->code) ({{ $room->code }})@endif</p>
    </div>
    <div class="meta-period">Periode pelaporan: {{ $fromLabel }} s/d {{ $toLabel }}</div>

    <table class="info-box">
        <tr>
            <td class="lbl">Jenis Lab</td>
            <td>: {{ $room->lab_type ?: '-' }}</td>
            <td class="lbl">Gedung / Lantai</td>
            <td>: {{ $room->building->name ?? '-' }} / Lt. {{ $room->floor ?? '-' }}</td>
        </tr>
        <tr>
            <td class="lbl">Kondisi Ruang</td>
            <td>: {{ $room->condition ?? '-' }}</td>
            <td class="lbl">Kapasitas</td>
            <td>: {{ $room->capacity !== null ? $room->capacity . ' orang' : '-' }}</td>
        </tr>
        <tr>
            <td class="lbl">Penanggung Jawab</td>
            <td colspan="3">
                : {{ $pj->name ?? 'Belum ditetapkan' }}
                @if($pj)
                    <div class="cell-note" style="margin-left: 8px;">{{ $pj->nip ?: ($pj->nuptk ?: 'Tanpa NIP/NUPTK') }}</div>
                @endif
            </td>
        </tr>
    </table>

    <table class="summary">
        <tr>
            <td><span class="num">{{ $items->count() }}</span><span class="cap">Inventaris</span></td>
            <td><span class="num">{{ $loans->count() }}</span><span class="cap">Peminjaman</span></td>
            <td><span class="num">{{ $journals->count() }}</span><span class="cap">Jurnal Pemakaian</span></td>
            <td><span class="num">{{ $maintenances->count() }}</span><span class="cap">Perawatan</span></td>
        </tr>
    </table>

    <div class="section-title">1. Inventaris Lab</div>
    <table class="data">
        <thead>
            <tr>
                <th class="num">No</th>
                <th>Kode</th>
                <th>Nama Barang</th>
                <th>Kategori</th>
                <th>Qty</th>
                <th>Kondisi</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
        @forelse($items as $i => $item)
            <tr>
                <td class="num">{{ $i + 1 }}</td>
                <td>{{ $item->code ?: '-' }}</td>
                <td>{{ $item->name }}</td>
                <td>{{ $item->category->name ?? '-' }}</td>
                <td class="center">{{ $item->quantity }} {{ $item->unit }}</td>
                <td class="center">{{ $item->condition }}</td>
                <td class="center">{{ $item->status }}</td>
            </tr>
        @empty
            <tr><td colspan="7" class="empty">Tidak ada data inventaris</td></tr>
        @endforelse
        </tbody>
    </table>

    <div class="section-title">2. Peminjaman Alat (Periode)</div>
    <table class="data">
        <thead>
            <tr>
                <th class="num">No</th>
                <th>Tanggal</th>
                <th>Barang</th>
                <th>Peminjam</th>
                <th>Qty</th>
                <th>Rencana Kembali</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
        @forelse($loans as $i => $loan)
            <tr>
                <td class="num">{{ $i + 1 }}</td>
                <td class="center">{{ $loan->loan_date?->format('d/m/Y') }}</td>
                <td>{{ $loan->item->name ?? '-' }}</td>
                <td>{{ $loan->borrower_name }}</td>
                <td class="center">{{ $loan->quantity }}</td>
                <td class="center">{{ $loan->expected_return_date?->format('d/m/Y') }}</td>
                <td class="center">{{ $loan->status }}</td>
            </tr>
        @empty
            <tr><td colspan="7" class="empty">Tidak ada peminjaman pada periode ini</td></tr>
        @endforelse
        </tbody>
    </table>

    <div class="section-title">3. Jurnal Pemakaian Lab</div>
    <table class="data">
        <thead>
            <tr>
                <th class="num">No</th>
                <th>Tanggal</th>
                <th>Kegiatan</th>
                <th>Kelas</th>
                <th>Peserta</th>
                <th>Catatan Insiden</th>
            </tr>
        </thead>
        <tbody>
        @forelse($journals as $i => $j)
            <tr>
                <td class="num">{{ $i + 1 }}</td>
                <td class="center">{{ $j->date?->format('d/m/Y') }}</td>
                <td>{{ $j->activity }}</td>
                <td>{{ $j->schoolClass->name ?? '-' }}</td>
                <td class="center">{{ $j->participants_count ?? '-' }}</td>
                <td>{{ $j->incident_notes ?: '-' }}</td>
            </tr>
        @empty
            <tr><td colspan="6" class="empty">Tidak ada jurnal pada periode ini</td></tr>
        @endforelse
        </tbody>
    </table>

    <div class="section-title">4. Perawatan &amp; Kerusakan</div>
    <table class="data">
        <thead>
            <tr>
                <th class="num">No</th>
                <th>Tanggal</th>
                <th>Barang</th>
                <th>Jenis</th>
                <th>Status</th>
                <th>Deskripsi</th>
            </tr>
        </thead>
        <tbody>
        @forelse($maintenances as $i => $m)
            <tr>
                <td class="num">{{ $i + 1 }}</td>
                <td class="center">{{ $m->scheduled_date?->format('d/m/Y') }}</td>
                <td>{{ $m->item->name ?? '-' }}</td>
                <td class="center">{{ $m->maintenance_type }}</td>
                <td class="center">{{ $m->status }}</td>
                <td>{{ $m->description ?: '-' }}</td>
            </tr>
        @empty
            <tr><td colspan="6" class="empty">Tidak ada catatan perawatan pada periode ini</td></tr>
        @endforelse
        </tbody>
    </table>

    {{-- Tanda tangan --}}
    <table class="sign-block">
        <tr>
            <td></td>
            <td>
                <div class="sign-box">
                    <div class="place">{{ $city ?: '................' }}, {{ $signDate }}</div>
                    <div class="role">Penanggung Jawab Lab</div>
                    <p class="name">{{ $pj->name ?? '(................................)' }}</p>
                    <p class="nip">NIP. {{ $pj->nip ?? '........................' }}</p>
                </div>
            </td>
        </tr>
    </table>
@else
    <div class="doc-title">
        <h1>Rekapitulasi Laboratorium</h1>
        <p class="subtitle">Daftar kondisi dan inventaris seluruh laboratorium</p>
    </div>
    <div class="meta-period">Periode: {{ $fromLabel }} s/d {{ $toLabel }}</div>

    <table class="data">
        <thead>
            <tr>
                <th class="num">No</th>
                <th>Nama Lab</th>
                <th>Jenis</th>
                <th>Gedung</th>
                <th>Kondisi</th>
                <th>Penanggung Jawab</th>
                <th>Inventaris</th>
                <th>Rusak</th>
            </tr>
        </thead>
        <tbody>
        @foreach($rooms as $i => $r)
            <tr>
                <td class="num">{{ $i + 1 }}</td>
                <td>{{ $r->name }}</td>
                <td class="center">{{ $r->lab_type ?: '-' }}</td>
                <td>{{ $r->building->name ?? '-' }}</td>
                <td class="center">{{ $r->condition }}</td>
                <td>
                    <div>{{ $r->responsibleEmployee->name ?? '-' }}</div>
                    @if($r->responsibleEmployee)
                        <div class="cell-note">{{ $r->responsibleEmployee->nip ?: ($r->responsibleEmployee->nuptk ?: 'Tanpa NIP/NUPTK') }}</div>
                    @endif
                </td>
                <td class="center">{{ $inventoryCounts[$r->id] ?? 0 }}</td>
                <td class="center">{{ $damagedCounts[$r->id] ?? 0 }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>

    <table class="sign-block">
        <tr>
            <td></td>
            <td>
                <div class="sign-box">
                    <div class="place">{{ $city ?: '................' }}, {{ $signDate }}</div>
                    <div class="role">Mengetahui,<br>{{ $institution->principal_title ?? \App\Models\Institution::principalTitleForLevel($institution->level ?? null) }}</div>
                    <p class="name">{{ $institution->principal_name ?? '(................................)' }}</p>
                    <p class="nip">NIP. {{ $institution->principal_nip ?? '........................' }}</p>
                </div>
            </td>
        </tr>
    </table>
@endif

<div class="footer">
    Dicetak pada {{ $printedAt }}
    @if(!empty($printedBy)) · oleh {{ $printedBy }}@endif
    · Dokumen ini digenerate oleh sistem ISS
</div>
</body>
</html>
