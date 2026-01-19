<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Surat - <?php echo e($correspondence->subject); ?></title>
    <style>
        @page {
            margin: 2.5cm;
        }
        body {
            font-family: 'Times New Roman', serif;
            font-size: 12pt;
            line-height: 1.6;
            color: #000;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .header img {
            max-width: 80px;
            height: auto;
        }
        .header h1 {
            font-size: 16pt;
            font-weight: bold;
            margin: 10px 0 5px 0;
            text-transform: uppercase;
        }
        .header p {
            font-size: 11pt;
            margin: 2px 0;
        }
        .header .address {
            font-size: 10pt;
            margin-top: 5px;
        }
        .divider {
            border-top: 3px solid #000;
            margin: 20px 0;
        }
        .letter-info {
            margin-bottom: 20px;
        }
        .letter-info table {
            width: 100%;
            border-collapse: collapse;
        }
        .letter-info td {
            padding: 5px 10px;
            vertical-align: top;
        }
        .letter-info td:first-child {
            width: 150px;
            font-weight: bold;
        }
        .subject {
            text-align: center;
            font-weight: bold;
            font-size: 13pt;
            margin: 20px 0;
            text-decoration: underline;
        }
        .content {
            text-align: justify;
            margin: 20px 0;
            min-height: 200px;
        }
        .signature {
            margin-top: 50px;
            text-align: right;
        }
        .signature table {
            width: 100%;
            border-collapse: collapse;
        }
        .signature td {
            padding: 10px;
            vertical-align: top;
        }
        .signature .signature-left {
            text-align: left;
        }
        .signature .signature-right {
            text-align: right;
        }
        .signature-name {
            font-weight: bold;
            margin-top: 60px;
            text-decoration: underline;
        }
        .signature-nip {
            font-size: 10pt;
            margin-top: 5px;
        }
        .footer {
            margin-top: 30px;
            font-size: 10pt;
            text-align: center;
        }
        .copy {
            margin-top: 20px;
            font-size: 10pt;
        }
        .copy-title {
            font-weight: bold;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <!-- Header Sekolah -->
    <div class="header">
        <?php if($institution->logo): ?>
            <img src="<?php echo e(public_path('storage/' . $institution->logo)); ?>" alt="Logo">
        <?php endif; ?>
        <h1><?php echo e($institution->name); ?></h1>
        <p>NPSN: <?php echo e($institution->npsn); ?></p>
        <?php if($institution->nss): ?>
            <p>NSS: <?php echo e($institution->nss); ?></p>
        <?php endif; ?>
        <div class="address">
            <p><?php echo e($institution->address); ?></p>
            <p><?php echo e($institution->village); ?>, <?php echo e($institution->sub_district); ?>, <?php echo e($institution->district); ?></p>
            <p><?php echo e($institution->province); ?> <?php echo e($institution->postal_code); ?></p>
            <?php if($institution->phone): ?>
                <p>Telp: <?php echo e($institution->phone); ?></p>
            <?php endif; ?>
            <?php if($institution->email): ?>
                <p>Email: <?php echo e($institution->email); ?></p>
            <?php endif; ?>
        </div>
    </div>

    <div class="divider"></div>

    <!-- Informasi Surat -->
    <div class="letter-info">
        <table>
            <?php if($correspondence->letter_number): ?>
                <tr>
                    <td>Nomor</td>
                    <td>: <?php echo e($correspondence->letter_number); ?></td>
                </tr>
            <?php endif; ?>
            <?php if($correspondence->reference_number): ?>
                <tr>
                    <td>Nomor Referensi</td>
                    <td>: <?php echo e($correspondence->reference_number); ?></td>
                </tr>
            <?php endif; ?>
            <?php if($correspondence->letter_type_name): ?>
                <tr>
                    <td>Jenis Surat</td>
                    <td>: <?php echo e($correspondence->letter_type_code); ?> - <?php echo e($correspondence->letter_type_abbr); ?> (<?php echo e($correspondence->letter_type_name); ?>)</td>
                </tr>
            <?php endif; ?>
            <tr>
                <td>Perihal</td>
                <td>: <?php echo e($correspondence->subject); ?></td>
            </tr>
            <?php if($correspondence->type === 'masuk' && $correspondence->from): ?>
                <tr>
                    <td>Dari</td>
                    <td>: <?php echo e($correspondence->from); ?></td>
                </tr>
            <?php endif; ?>
            <?php if(in_array($correspondence->type, ['keluar', 'internal']) && $correspondence->to): ?>
                <tr>
                    <td>Kepada</td>
                    <td>: <?php echo e($correspondence->to); ?></td>
                </tr>
            <?php endif; ?>
            <tr>
                <td>Tanggal</td>
                <td>: <?php echo e(\Carbon\Carbon::parse($correspondence->date)->locale('id')->isoFormat('D MMMM YYYY')); ?></td>
            </tr>
            <?php if($correspondence->type === 'masuk' && $correspondence->received_date): ?>
                <tr>
                    <td>Tanggal Terima</td>
                    <td>: <?php echo e(\Carbon\Carbon::parse($correspondence->received_date)->locale('id')->isoFormat('D MMMM YYYY')); ?></td>
                </tr>
            <?php endif; ?>
        </table>
    </div>

    <!-- Perihal -->
    <div class="subject">
        <?php echo e($correspondence->subject); ?>

    </div>

    <!-- Isi Surat -->
    <div class="content">
        <?php echo nl2br(e($correspondence->description ?? 'Isi surat tidak tersedia.')); ?>

    </div>

    <!-- Tanda Tangan -->
    <div class="signature">
            <div class="signature-right">
                <div><?php echo e($institution->district); ?>, <?php echo e(\Carbon\Carbon::parse($correspondence->date)->locale('id')->isoFormat('D MMMM YYYY')); ?></div>
                <?php if($correspondence->type === 'keluar' || $correspondence->type === 'internal'): ?>
                    <div style="margin-top: 10px;">Yang bertanda tangan di bawah ini,</div>
                <?php endif; ?>
                <div class="signature-name">
                    <?php if($institution->principal_name): ?>
                        <?php echo e($institution->principal_name); ?>

                    <?php else: ?>
                        Kepala Sekolah
                    <?php endif; ?>
                </div>
                <?php if($institution->principal_nip): ?>
                    <div class="signature-nip">NIP. <?php echo e($institution->principal_nip); ?></div>
                <?php endif; ?>
            </div>
    </div>

    <!-- Tembusan (jika ada) -->
    <?php if($correspondence->type === 'keluar' && $correspondence->to): ?>
        <div class="copy">
            <div class="copy-title">Tembusan:</div>
            <div>- Arsip</div>
        </div>
    <?php endif; ?>

    <!-- Footer -->
    <div class="footer">
        <p>Dicetak pada: <?php echo e(\Carbon\Carbon::now()->locale('id')->isoFormat('D MMMM YYYY HH:mm')); ?></p>
    </div>
</body>
</html>
<?php /**PATH C:\xampp\htdocs\iss\backend\resources\views/correspondence/print.blade.php ENDPATH**/ ?>