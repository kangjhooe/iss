<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kartu Peserta Ujian - <?php echo e($exam->name ?? 'Sesi'); ?></title>
    <style>
        @page { margin: 10mm; size: A4 landscape; }
        * { box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: 9pt; color: #1e293b; margin: 0; padding: 0; }
        .page { page-break-after: always; width: 277mm; min-height: 190mm; padding: 8px 0; }
        .page:last-child { page-break-after: auto; }
        .cards-grid { display: table; width: 100%; border-collapse: collapse; table-layout: fixed; }
        .cards-row { display: table-row; }
        .card-cell { display: table-cell; width: 25%; padding: 6px; vertical-align: top; }
        .card {
            border: 1.5px solid #334155;
            border-radius: 8px;
            overflow: hidden;
            height: 92mm;
            display: flex;
            flex-direction: column;
            background: #fff;
        }
        .card-header {
            background: linear-gradient(135deg, #059669 0%, #047857 100%);
            color: #fff;
            padding: 6px 10px;
            font-size: 8pt;
            font-weight: bold;
            text-align: center;
        }
        .card-body { padding: 8px 10px; flex: 1; display: flex; flex-direction: column; gap: 6px; }
        .card-photo-wrap {
            width: 100%;
            text-align: center;
            margin-bottom: 4px;
        }
        .card-photo {
            width: 52px;
            height: 52px;
            object-fit: cover;
            border: 2px solid #e2e8f0;
            border-radius: 6px;
        }
        .card-photo-placeholder {
            width: 52px;
            height: 52px;
            margin: 0 auto;
            background: #f1f5f9;
            border: 2px dashed #cbd5e1;
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 7pt;
            color: #94a3b8;
        }
        .card-row { display: flex; align-items: flex-start; gap: 6px; }
        .card-label { font-weight: bold; color: #64748b; font-size: 7pt; flex-shrink: 0; }
        .card-value { font-size: 8pt; word-break: break-word; }
        .card-nomor { font-family: 'Courier New', monospace; font-size: 9pt; font-weight: bold; color: #059669; }
        .card-name { font-weight: bold; font-size: 9pt; }
    </style>
</head>
<body>
    <?php $__currentLoopData = $pages; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $pageIndex => $pageCards): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
    <div class="page">
        <div class="cards-grid">
            <?php for($row = 0; $row < 2; $row++): ?>
            <div class="cards-row">
                <?php for($col = 0; $col < 4; $col++): ?>
                <?php $idx = $row * 4 + $col; $card = $pageCards[$idx] ?? null; ?>
                <div class="card-cell">
                    <?php if($card): ?>
                    <div class="card">
                        <div class="card-header">KARTU PESERTA UJIAN</div>
                        <div class="card-body">
                            <div class="card-photo-wrap">
                                <?php if(!empty($card['photo_base64'])): ?>
                                <img src="<?php echo e($card['photo_base64']); ?>" alt="" class="card-photo" />
                                <?php else: ?>
                                <div class="card-photo-placeholder">Tanpa foto</div>
                                <?php endif; ?>
                            </div>
                            <div class="card-row">
                                <span class="card-label">Nomor peserta</span>
                                <span class="card-value card-nomor"><?php echo e($card['nomor_peserta'] ?? '–'); ?></span>
                            </div>
                            <div class="card-row">
                                <span class="card-label">Nama</span>
                                <span class="card-value card-name"><?php echo e($card['nama'] ?? '–'); ?></span>
                            </div>
                            <div class="card-row">
                                <span class="card-label">Kelas</span>
                                <span class="card-value"><?php echo e($card['kelas'] ?? '–'); ?></span>
                            </div>
                            <div class="card-row">
                                <span class="card-label">Sesi</span>
                                <span class="card-value"><?php echo e($card['sesi'] ?? '–'); ?></span>
                            </div>
                            <?php if(!empty($card['nomor_urut'])): ?>
                            <div class="card-row" style="margin-top: auto; font-size: 7pt; color: #64748b;">
                                <span>No. urut login: <strong><?php echo e($card['nomor_urut']); ?></strong></span>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php else: ?>
                    <div class="card" style="border-style: dashed; background: #f8fafc;">
                        <div class="card-body" style="align-items: center; justify-content: center; color: #94a3b8; font-size: 8pt;">–</div>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endfor; ?>
            </div>
            <?php endfor; ?>
        </div>
    </div>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
</body>
</html>
<?php /**PATH C:\xampp\htdocs\iss\backend\resources\views/exam/participant_cards_a4.blade.php ENDPATH**/ ?>