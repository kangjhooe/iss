<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kontak Orang Tua - {{ $class->name ?? '' }}</title>
    <style>
        @page { margin: 1.2cm; size: A4 landscape; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 9pt; color: #000; }
        .header { text-align: center; margin-bottom: 10px; }
        .header h1 { font-size: 13pt; margin: 0 0 4px 0; text-transform: uppercase; }
        .meta { font-size: 8pt; margin-bottom: 8px; color: #333; }
        table.data { width: 100%; border-collapse: collapse; font-size: 8pt; }
        table.data th, table.data td { border: 1px solid #333; padding: 3px 5px; }
        table.data th { background: #e8e8e8; text-align: center; }
        table.data td.center { text-align: center; }
        .footer { margin-top: 10px; font-size: 7pt; text-align: center; color: #666; }
        @include('partials.print-letterhead-styles')
        @include('partials.print-signature-styles')
    </style>
</head>
<body>
    @include('partials.print-letterhead', ['institution' => $institution])

    <div class="header">
        <h1>Daftar Kontak Orang Tua / Wali</h1>
        <div>Kelas {{ $class->name ?? '' }}</div>
    </div>
    <div class="meta">Jumlah: {{ count($students) }} siswa aktif</div>

    <table class="data">
        <thead>
            <tr>
                <th style="width:4%;">No</th>
                <th>Nama Siswa</th>
                <th style="width:10%;">NIS</th>
                <th style="width:12%;">HP Siswa</th>
                <th style="width:16%;">Nama Wali</th>
                <th style="width:12%;">HP Wali</th>
                <th>Alamat</th>
            </tr>
        </thead>
        <tbody>
            @forelse($students as $i => $s)
                <tr>
                    <td class="center">{{ $i + 1 }}</td>
                    <td>{{ $s->name }}</td>
                    <td class="center">{{ $s->nis ?: '—' }}</td>
                    <td class="center">{{ $s->phone ?: '—' }}</td>
                    <td>{{ $s->guardian_name ?: '—' }}</td>
                    <td class="center">{{ $s->guardian_phone ?: '—' }}</td>
                    <td>{{ \App\Support\RegionAddress::format($s) ?: '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="7" class="center">Belum ada data</td></tr>
            @endforelse
        </tbody>
    </table>

    @include('partials.print-wali-signatures', [
        'institution' => $institution,
        'wali_kelas' => $wali_kelas ?? null,
        'signature_date' => $signature_date ?? null,
    ])

    @include('partials.print-document-footer')
</body>
</html>
