<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title><?php echo e($surat->judul ?? 'Surat'); ?></title>
    <style>
        <?php echo $__env->make('surat.partials.document-styles', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    </style>
</head>
<body>
    <div class="surat-body">
        <?php echo $kopHtml ?? ''; ?>
        <?php echo $isiHtml; ?>
        <?php echo $ttdHtml ?? ''; ?>
    </div>
</body>
</html>
<?php /**PATH C:\xampp\htdocs\iss\backend\resources\views/surat/print.blade.php ENDPATH**/ ?>