<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Surat Menyurat</title>
    <style>
        @page {
            size: A4 landscape;
            margin: 15mm;
        }
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 11px;
            color: #333;
            margin: 0;
            padding: 10px;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #333;
            padding-bottom: 15px;
        }
        .header h1 {
            margin: 0;
            font-size: 20px;
            font-weight: bold;
        }
        .header p {
            margin: 5px 0;
            font-size: 11px;
        }
        .info-section {
            margin-bottom: 15px;
        }
        .info-section table {
            width: 100%;
            border-collapse: collapse;
        }
        .info-section td {
            padding: 8px;
            font-size: 11px;
            vertical-align: top;
        }
        .info-section td:first-child {
            font-weight: bold;
            width: 150px;
            background-color: #f2f2f2;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 6px 8px;
            text-align: left;
            font-size: 9px;
        }
        th:nth-child(1), td:nth-child(1) {
            width: 3%;
        }
        th:nth-child(2), td:nth-child(2) {
            width: 12%;
        }
        th:nth-child(3), td:nth-child(3) {
            width: 15%;
        }
        th:nth-child(4), td:nth-child(4) {
            width: 25%;
        }
        th:nth-child(5), td:nth-child(5) {
            width: 15%;
        }
        th:nth-child(6), td:nth-child(6) {
            width: 8%;
        }
        th:nth-child(7), td:nth-child(7) {
            width: 10%;
        }
        th:nth-child(8), td:nth-child(8) {
            width: 12%;
        }
        .info-section td:last-child {
            border: 1px solid #ddd;
            background-color: #f9f9f9;
            min-height: 20px;
        }
        th {
            background-color: #f2f2f2;
            font-weight: bold;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .footer {
            margin-top: 20px;
            text-align: right;
            font-size: 9px;
            color: #666;
        }
        .no-data {
            text-align: center;
            padding: 40px;
            color: #999;
            font-style: italic;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>
            <?php if(isset($filters['type'])): ?>
                <?php if($filters['type'] === 'masuk'): ?>
                    LAPORAN SURAT MASUK
                <?php elseif($filters['type'] === 'keluar'): ?>
                    LAPORAN SURAT KELUAR
                <?php elseif($filters['type'] === 'internal'): ?>
                    LAPORAN SURAT INTERNAL
                <?php else: ?>
                    LAPORAN SURAT MENYURAT
                <?php endif; ?>
            <?php else: ?>
                LAPORAN SURAT MENYURAT
            <?php endif; ?>
        </h1>
        <?php if($institution): ?>
        <p><?php echo e($institution->name); ?></p>
        <p>NPSN: <?php echo e($institution->npsn ?? '-'); ?></p>
        <?php endif; ?>
        <p>Dicetak pada: <?php echo e($generated_at->format('d F Y H:i:s')); ?></p>
    </div>

    <?php
        $hasOtherFilters = false;
        $filterText = '';
        
        if (isset($filters['status']) && !empty($filters['status'])) {
            $hasOtherFilters = true;
            $filterText .= 'Status: ' . ucfirst($filters['status']) . '<br>';
        }
        
        if (isset($filters['priority']) && !empty($filters['priority'])) {
            $hasOtherFilters = true;
            $filterText .= 'Prioritas: ' . ucfirst(str_replace('_', ' ', $filters['priority'])) . '<br>';
        }
        
        if ((isset($filters['date_from']) && !empty($filters['date_from'])) || (isset($filters['date_to']) && !empty($filters['date_to']))) {
            $hasOtherFilters = true;
            $filterText .= 'Periode: ';
            $filterText .= isset($filters['date_from']) && !empty($filters['date_from']) ? date('d/m/Y', strtotime($filters['date_from'])) : 'Awal';
            $filterText .= ' - ';
            $filterText .= isset($filters['date_to']) && !empty($filters['date_to']) ? date('d/m/Y', strtotime($filters['date_to'])) : 'Akhir';
        }
    ?>

    <?php if($hasOtherFilters): ?>
    <div class="info-section">
        <table>
            <tr>
                <td>Filter yang Diterapkan:</td>
                <td><?php echo $filterText; ?></td>
            </tr>
        </table>
    </div>
    <?php endif; ?>

    <?php if(count($correspondence) > 0): ?>
    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Jenis</th>
                <th>Nomor Surat</th>
                <th>Perihal</th>
                <th>Dari/Kepada</th>
                <th>Tanggal</th>
                <th>Prioritas</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php $__currentLoopData = $correspondence; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <tr>
                <td><?php echo e($index + 1); ?></td>
                <td><?php echo e($item->letter_type_name ?? '-'); ?></td>
                <td><?php echo e($item->letter_number ?? $item->reference_number ?? '-'); ?></td>
                <td><?php echo e($item->subject); ?></td>
                <td><?php echo e($item->type === 'masuk' ? ($item->from ?? '-') : ($item->to ?? '-')); ?></td>
                <td><?php echo e($item->date ? $item->date->format('d/m/Y') : '-'); ?></td>
                <td><?php echo e(ucfirst(str_replace('_', ' ', $item->priority))); ?></td>
                <td><?php echo e(ucfirst($item->status)); ?></td>
            </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </tbody>
    </table>
    <?php else: ?>
    <div class="no-data">
        Tidak ada data surat yang ditemukan
    </div>
    <?php endif; ?>

    <div class="footer">
        Total: <?php echo e(count($correspondence)); ?> surat
    </div>
</body>
</html>
<?php /**PATH C:\xampp\htdocs\iss\backend\resources\views/correspondence/report.blade.php ENDPATH**/ ?>