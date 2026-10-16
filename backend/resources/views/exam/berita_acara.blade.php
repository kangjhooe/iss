<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Berita Acara Ujian</title>
    <style>
        @page { margin: 1.2cm; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #111; }
        h1 { font-size: 14px; text-align: center; margin: 12px 0 4px; }
        .meta { width: 100%; margin: 8px 0 12px; }
        .meta td { padding: 2px 6px 2px 0; vertical-align: top; }
        .meta .label { width: 130px; }
        table.roster { width: 100%; border-collapse: collapse; }
        table.roster th, table.roster td { border: 1px solid #333; padding: 4px 6px; }
        table.roster th { background: #f3f4f6; text-align: left; }
        .summary { margin: 10px 0; }
        .signs { width: 100%; margin-top: 28px; }
        .signs td { width: 50%; text-align: center; vertical-align: top; }
        .sign-space { height: 64px; }
        .muted { color: #444; font-size: 10px; }
    </style>
    @include('partials.print-letterhead-styles')
</head>
<body>
    @include('partials.print-letterhead', ['institution' => $institution])
    <h1>BERITA ACARA PELAKSANAAN UJIAN</h1>
    <table class="meta">
        <tr><td class="label">Mata pelajaran</td><td>: {{ $exam->subject->name ?? '–' }}</td></tr>
        <tr><td class="label">Ujian</td><td>: {{ $exam->name }}</td></tr>
        <tr><td class="label">Sesi</td><td>: {{ $session->name }}</td></tr>
        <tr><td class="label">Jadwal</td><td>: {{ $session->scheduled_start_at?->timezone('Asia/Jakarta')->format('d/m/Y H:i') ?? '–' }} s.d. {{ $session->scheduled_end_at?->timezone('Asia/Jakarta')->format('d/m/Y H:i') ?? '–' }}</td></tr>
        <tr><td class="label">Mulai / selesai aktual</td><td>: {{ $session->started_at?->timezone('Asia/Jakarta')->format('d/m/Y H:i') ?? '–' }} / {{ $session->ended_at?->timezone('Asia/Jakarta')->format('H:i') ?? '–' }}</td></tr>
    </table>
    <p class="summary">Peserta {{ $total }}. Hadir {{ $hadir }}. Tidak hadir {{ $tidak_hadir }}. Selesai mengumpulkan {{ $selesai }}.</p>
    <table class="roster">
        <thead>
            <tr>
                <th>No</th>
                <th>Nama</th>
                <th>NIS</th>
                <th>Kelas</th>
                <th>Kehadiran</th>
                <th>Mulai</th>
                <th>Kumpul</th>
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $row)
                <tr>
                    <td>{{ $row['no'] }}</td>
                    <td>{{ $row['name'] }}</td>
                    <td>{{ $row['nis'] }}</td>
                    <td>{{ $row['class'] }}</td>
                    <td>{{ $row['status'] }}</td>
                    <td>{{ $row['started_at'] ?? '–' }}</td>
                    <td>{{ $row['submitted_at'] ?? '–' }}</td>
                </tr>
            @empty
                <tr><td colspan="7">Belum ada peserta.</td></tr>
            @endforelse
        </tbody>
    </table>
    <p class="muted">Dicetak {{ $printed_at }}.</p>
    <table class="signs">
        <tr>
            <td>
                Pengawas<br>
                <div class="sign-space"></div>
                ( .............................. )
            </td>
            <td>
                Guru mata pelajaran<br>
                <div class="sign-space"></div>
                ( .............................. )
            </td>
        </tr>
    </table>
</body>
</html>
