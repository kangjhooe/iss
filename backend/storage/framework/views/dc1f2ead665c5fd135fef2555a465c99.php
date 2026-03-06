<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kartu Peserta Ujian - <?php echo e($participant->student->name ?? 'Peserta'); ?></title>
    <style>
        @page { margin: 1cm; size: 90mm 140mm; }
        body { font-family: Arial, sans-serif; font-size: 10pt; line-height: 1.3; color: #000; }
        .card { border: 2px solid #333; padding: 12px; max-width: 85mm; }
        .card h2 { font-size: 12pt; margin: 0 0 8px 0; text-align: center; }
        .card .exam-name { font-weight: bold; margin-bottom: 4px; }
        .card .session-name { margin-bottom: 8px; }
        .card .row { margin: 4px 0; }
        .card .label { font-weight: bold; display: inline-block; width: 90px; }
        .card .row-nomor-peserta { font-family: monospace; font-size: 10pt; }
        .card .row-nomor-urut { font-family: monospace; font-size: 11pt; margin-top: 6px; }
        .card .hint { font-size: 8pt; color: #666; margin-top: 6px; }
    </style>
</head>
<body>
    <div class="card">
        <h2>KARTU PESERTA UJIAN</h2>
        <div class="exam-name"><?php echo e($exam->name); ?></div>
        <div class="session-name">Sesi: <?php echo e($session->name); ?></div>
        <div class="row row-nomor-peserta"><span class="label">Nomor Peserta</span> <strong><?php echo e($nomor_peserta ?? '–'); ?></strong></div>
        <div class="row"><span class="label">Nama</span> <?php echo e($participant->student->name ?? '-'); ?></div>
        <div class="row"><span class="label">NIS</span> <?php echo e($participant->student->nis ?? '-'); ?></div>
        <div class="row"><span class="label">NISN</span> <?php echo e($participant->student->nisn ?? '-'); ?></div>
        <div class="row"><span class="label">Kelas</span> <?php echo e($participant->student->class->name ?? '-'); ?></div>
        <div class="row"><span class="label">Mata Pelajaran</span> <?php echo e($exam->subject->name ?? '-'); ?></div>
        <div class="row"><span class="label">Durasi</span> <?php echo e($exam->duration_minutes); ?> menit</div>
        <div class="row row-nomor-urut"><span class="label">No. urut (untuk login)</span> <strong><?php echo e($nomor_urut ?? '–'); ?></strong></div>
        <div class="hint">Masuk ujian: isi kode ujian 6 huruf dari pengawas dan no. urut di atas.</div>
    </div>
</body>
</html>
<?php /**PATH C:\xampp\htdocs\iss\backend\resources\views/exam/participant_card.blade.php ENDPATH**/ ?>