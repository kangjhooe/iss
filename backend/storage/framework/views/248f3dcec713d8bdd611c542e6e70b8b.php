<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Buku Tamu - <?php echo e($institution->name ?? 'Export'); ?></title>
    <style>
        @page { margin: 1.2cm; size: A4 landscape; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 9pt; line-height: 1.3; color: #000; }
        .header { text-align: center; margin-bottom: 12px; }
        .header h1 { font-size: 14pt; font-weight: bold; margin: 0 0 4px 0; }
        .header p { font-size: 8pt; margin: 0; color: #444; }
        .period { font-size: 8pt; margin-bottom: 10px; color: #555; }
        table { width: 100%; border-collapse: collapse; font-size: 8pt; }
        table th, table td { border: 1px solid #333; padding: 4px 6px; text-align: left; }
        table th { background: #e8e8e8; font-weight: bold; }
        table td.num { text-align: center; width: 28px; }
        table td.time { white-space: nowrap; }
        .footer { margin-top: 10px; font-size: 7pt; text-align: center; color: #666; }
    </style>
</head>
<body>
    <div class="header">
        <?php if($institution): ?>
            <h1><?php echo e($institution->name); ?></h1>
            <p>NPSN: <?php echo e($institution->npsn ?? '-'); ?></p>
            <p>BUKU TAMU</p>
        <?php else: ?>
            <h1>BUKU TAMU</h1>
        <?php endif; ?>
    </div>

    <?php if($date_from || $date_to): ?>
    <div class="period">
        Periode: <?php echo e($date_from ? \Carbon\Carbon::parse($date_from)->locale('id')->isoFormat('D MMM YYYY') : '...'); ?>

        s/d <?php echo e($date_to ? \Carbon\Carbon::parse($date_to)->locale('id')->isoFormat('D MMM YYYY') : '...'); ?>

    </div>
    <?php endif; ?>

    <table>
        <thead>
            <tr>
                <th class="num">No</th>
                <th>Nama Tamu</th>
                <th>No. Identitas</th>
                <th>Instansi / Asal</th>
                <th>Tujuan Kunjungan</th>
                <th>Ditemui</th>
                <th class="time">Waktu Masuk</th>
                <th class="time">Waktu Keluar</th>
            </tr>
        </thead>
        <tbody>
            <?php $__empty_1 = true; $__currentLoopData = $visits; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $visit): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
            <tr>
                <td class="num"><?php echo e($index + 1); ?></td>
                <td><?php echo e($visit->nama_tamu); ?></td>
                <td><?php echo e($visit->no_identitas ?? '-'); ?></td>
                <td><?php echo e($visit->instansi_asal ?? '-'); ?></td>
                <td><?php echo e($visit->tujuan_kunjungan); ?></td>
                <td><?php echo e($visit->orang_ditemui ?? '-'); ?></td>
                <td class="time"><?php echo e($visit->waktu_masuk ? $visit->waktu_masuk->locale('id')->format('d/m/Y H:i') : '-'); ?></td>
                <td class="time"><?php echo e($visit->waktu_keluar ? $visit->waktu_keluar->locale('id')->format('d/m/Y H:i') : '-'); ?></td>
            </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
            <tr>
                <td colspan="8" style="text-align: center; padding: 12px;">Tidak ada data kunjungan.</td>
            </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <div class="footer">
        Dicetak pada <?php echo e(now()->locale('id')->isoFormat('D MMMM YYYY HH:mm')); ?> &mdash; <?php echo e($visits->count()); ?> catatan
    </div>
</body>
</html>
<?php /**PATH C:\xampp\htdocs\iss\backend\resources\views/guest_visits/export.blade.php ENDPATH**/ ?>