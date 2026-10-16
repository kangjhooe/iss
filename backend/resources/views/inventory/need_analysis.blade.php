<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Analisis Kebutuhan Sarana Prasarana - {{ $institution->name ?? 'Institusi' }}</title>
    <style>
        @page { margin: 1.2cm 1.2cm 1.6cm 1.2cm; size: A4 landscape; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 8pt; line-height: 1.3; color: #000; }
        .header { text-align: center; margin-bottom: 8px; }
        .header h1 { font-size: 12pt; font-weight: bold; margin: 0 0 2px 0; text-transform: uppercase; }
        .header p { font-size: 8pt; margin: 0; color: #444; }
        .meta { margin-bottom: 8px; padding: 6px 8px; background: #f5f5f5; border: 1px solid #ddd; font-size: 7.5pt; }
        .meta table { width: 100%; border: none; border-collapse: collapse; }
        .meta td { border: none; padding: 1px 10px 1px 0; vertical-align: top; }
        .meta td.lbl { font-weight: bold; white-space: nowrap; width: 1%; }
        .section-title { font-size: 9.5pt; font-weight: bold; margin: 12px 0 4px; text-transform: uppercase; border-bottom: 1px solid #333; }
        table.data { width: 100%; border-collapse: collapse; font-size: 7pt; margin-bottom: 4px; }
        table.data th, table.data td { border: 1px solid #333; padding: 2px 3px; text-align: left; vertical-align: top; }
        table.data th { background: #e8e8e8; font-weight: bold; text-align: center; }
        table.data td.num, table.data th.num { text-align: center; width: 18px; }
        table.data td.center { text-align: center; }
        table.data td.right { text-align: right; white-space: nowrap; }
        table.data tfoot td { font-weight: bold; background: #f3f3f3; }
        .note { color: #444; font-size: 6.5pt; }
        .empty { text-align: center; padding: 8px; color: #666; }
        .footnote { margin-top: 6px; font-size: 7pt; color: #444; }
        @include('partials.print-letterhead-styles')
        @include('partials.print-signature-styles')
        .standard-signature-wrap { margin-top: 16px; }
        .standard-signature-space { height: 48px; }
    </style>
</head>
<body>
    @include('partials.print-letterhead', ['institution' => $institution])

    <div class="header">
        <h1>Analisis Kebutuhan Sarana Prasarana</h1>
        <p>{{ $analysis->title }}</p>
    </div>

    <div class="meta">
        <table>
            <tr>
                <td class="lbl">Tahun ajaran:</td>
                <td>{{ $analysis->academic_year_label ?: '-' }}</td>
                <td class="lbl">Status:</td>
                <td>{{ $analysis->statusLabel() }}@if($analysis->status === 'final' && $analysis->finalized_at) (dikunci {{ $analysis->finalized_at->locale('id')->isoFormat('D MMMM YYYY') }})@endif</td>
            </tr>
            <tr>
                <td class="lbl">Siswa aktif:</td>
                <td>{{ number_format((int) $analysis->student_count, 0, ',', '.') }}</td>
                <td class="lbl">Rombel aktif:</td>
                <td>{{ number_format((int) $analysis->rombel_count, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td class="lbl">Jenis kurang:</td>
                <td>{{ (int) $analysis->shortage_lines }}</td>
                <td class="lbl">Prioritas tinggi yang kurang:</td>
                <td>{{ (int) $analysis->high_priority_lines }}</td>
            </tr>
            <tr>
                <td class="lbl">Anggaran beli:</td>
                <td>Rp {{ number_format((float) $analysis->procure_budget, 0, ',', '.') }}</td>
                <td class="lbl">Anggaran perbaikan:</td>
                <td>Rp {{ number_format((float) $analysis->repair_budget, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td class="lbl">Dicetak:</td>
                <td colspan="3">{{ $printed_at ?? '-' }}@if(!empty($printed_by)) — {{ $printed_by }}@endif</td>
            </tr>
            @if($analysis->notes)
            <tr>
                <td class="lbl">Catatan:</td>
                <td colspan="3">{{ $analysis->notes }}</td>
            </tr>
            @endif
        </table>
    </div>

    @php
        $sections = [
            'prasarana' => 'Prasarana',
            'sarana' => 'Sarana',
        ];
    @endphp

    @foreach($sections as $sectionKey => $sectionLabel)
        @php $rows = $analysis->lines->where('section', $sectionKey)->values(); @endphp
        <div class="section-title">{{ $sectionLabel }}</div>
        <table class="data">
            <thead>
                <tr>
                    <th class="num">No</th>
                    <th>Jenis</th>
                    <th>Satuan</th>
                    <th>Dasar</th>
                    <th>Rasio</th>
                    <th>Butuh</th>
                    <th>Baik</th>
                    <th>Ringan</th>
                    <th>Sedang</th>
                    <th>Berat</th>
                    <th>Acuan</th>
                    <th>Layak</th>
                    <th>Kurang</th>
                    <th>Prioritas</th>
                    <th>Anggaran</th>
                </tr>
            </thead>
            <tbody>
                @forelse($rows as $i => $line)
                <tr>
                    <td class="num">{{ $i + 1 }}</td>
                    <td>
                        {{ $line->name }}
                        @if($line->notes)<div class="note">{{ $line->notes }}</div>@endif
                        @if((int) $line->existing_habis > 0)<div class="note">Habis pakai: {{ (int) $line->existing_habis }} (tidak dihitung layak)</div>@endif
                    </td>
                    <td class="center">{{ $line->unit }}</td>
                    <td>{{ $line->basisLabel() }}</td>
                    <td class="center">{{ $line->ratioLabel() }}</td>
                    <td class="center">{{ (int) $line->required_qty }}</td>
                    <td class="center">{{ (int) $line->existing_baik }}</td>
                    <td class="center">{{ (int) $line->existing_rusak_ringan }}</td>
                    <td class="center">{{ (int) $line->existing_rusak_sedang }}</td>
                    <td class="center">{{ (int) $line->existing_rusak_berat }}</td>
                    <td class="center">{{ $line->usablePolicyLabel() }}</td>
                    <td class="center">{{ (int) $line->usable_qty }}</td>
                    <td class="center">
                        @if((int) $line->shortage_qty > 0)
                            {{ (int) $line->shortage_qty }}
                            <div class="note">Beli {{ (int) $line->procure_qty }} · Perbaiki {{ (int) $line->repair_qty }}</div>
                        @elseif((int) $line->surplus_qty > 0)
                            Lebih {{ (int) $line->surplus_qty }}
                        @else
                            0
                        @endif
                    </td>
                    <td class="center">{{ $line->priorityLabel() }}</td>
                    <td class="right">
                        Beli {{ $line->procure_cost !== null ? 'Rp '.number_format((float) $line->procure_cost, 0, ',', '.') : '-' }}
                        <div class="note">Perbaiki {{ $line->repair_cost !== null ? 'Rp '.number_format((float) $line->repair_cost, 0, ',', '.') : '-' }}</div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="15" class="empty">Belum ada baris {{ strtolower($sectionLabel) }}.</td></tr>
                @endforelse
            </tbody>
        </table>
    @endforeach

    <p class="footnote">
        Kebutuhan = pembulatan ke atas dari dasar × rasio. Layak mengikuti acuan tiap baris.
        Kekurangan yang masih berupa barang atau ruang rusak dihitung sebagai perbaikan. Sisanya dihitung sebagai pembelian. Habis pakai tidak diperbaiki.
        Barang hilang, dijual, atau sudah dihapus tidak dihitung tersedia. Barang yang sedang dipinjam tetap dihitung.
        @if($analysis->status !== 'final') Angka draf mengikuti data saat dokumen ini dicetak. @endif
    </p>

    <div class="standard-signature-wrap">
        <div class="standard-signature-left"></div>
        <div class="standard-signature-right">
            @include('partials.print-signature', [
                'institution' => $institution,
                'date' => ($as_of_date ?? now())->locale('id')->translatedFormat('d F Y'),
                'as_of_date' => $as_of_date ?? null,
            ])
        </div>
    </div>

    @include('partials.print-document-footer')
</body>
</html>
