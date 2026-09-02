<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Daftar Siswa - {{ $class->name ?? '' }}</title>
    <style>
        @page { margin: 1.5cm; size: A4 portrait; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 10pt; color: #000; }
        .header { text-align: center; margin-bottom: 12px; }
        .header h1 { font-size: 14pt; margin: 0 0 4px 0; text-transform: uppercase; }
        .meta { font-size: 9pt; margin-bottom: 10px; color: #333; }
        table.data { width: 100%; border-collapse: collapse; font-size: 9pt; }
        table.data th, table.data td { border: 1px solid #333; padding: 4px 6px; }
        table.data th { background: #e8e8e8; text-align: center; }
        table.data td.center { text-align: center; }
        .footer { margin-top: 12px; font-size: 8pt; text-align: center; color: #666; }
        @include('partials.print-letterhead-styles')
        @include('partials.print-signature-styles')
    </style>
</head>
<body>
    @include('partials.print-letterhead', ['institution' => $institution])

    <div class="header">
        <h1>Daftar Siswa Kelas {{ $class->name ?? '' }}</h1>
    </div>
    <div class="meta">
        @if(!empty($class->grade)) Tingkat {{ $class->grade }} &nbsp;|&nbsp; @endif
        Jumlah: {{ count($students) }} siswa aktif
    </div>

    <table class="data">
        <thead>
            <tr>
                <th style="width:6%;">No</th>
                <th>Nama</th>
                <th style="width:16%;">NIS</th>
                <th style="width:16%;">NISN</th>
                <th style="width:8%;">L/P</th>
            </tr>
        </thead>
        <tbody>
            @forelse($students as $i => $s)
                <tr>
                    <td class="center">{{ $i + 1 }}</td>
                    <td>{{ $s->name }}</td>
                    <td class="center">{{ $s->nis ?: '—' }}</td>
                    <td class="center">{{ $s->nisn ?: '—' }}</td>
                    <td class="center">{{ $s->gender ?: '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="5" class="center">Belum ada siswa aktif</td></tr>
            @endforelse
        </tbody>
    </table>

    @include('partials.print-wali-signatures', [
        'institution' => $institution,
        'wali_kelas' => $wali_kelas ?? null,
        'signature_date' => $signature_date ?? null,
    ])

    <div class="footer print-document-footer">Dicetak: {{ $printed_at ?? now() }}</div>
</body>
</html>
