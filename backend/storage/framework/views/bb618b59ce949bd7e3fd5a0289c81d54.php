
@page {
    size: A4 portrait;
    margin: 0;
}
html, body {
    margin: 0;
    padding: 0;
}
body {
    margin: 0;
    padding: 15mm 20mm 20mm 25mm;
    font-family: 'Times New Roman', Times, serif;
    font-size: 12pt;
    line-height: 1.6;
    color: #000;
}
<?php echo $__env->make('partials.print-letterhead-styles', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php echo $__env->make('partials.print-signature-styles', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
.standard-kop {
    width: 100%;
    max-width: 100%;
    display: block;
}
.standard-kop-inner {
    width: 100%;
    max-width: 100%;
    border-collapse: collapse;
    table-layout: auto;
}
.standard-kop-logo-cell {
    width: 82px;
    vertical-align: top;
    padding-right: 10px !important;
}
.standard-kop-spacer {
    width: 82px;
    border: none !important;
    padding: 0 !important;
}
.standard-kop-text {
    text-align: center;
    vertical-align: top;
}
.standard-kop-address,
.standard-kop-info {
    word-wrap: break-word;
    overflow-wrap: break-word;
}
.surat-body {
    width: 100%;
    max-width: 100%;
    word-wrap: break-word;
    overflow-wrap: break-word;
}
.surat-body img {
    max-width: 100%;
    height: auto;
}
.surat-body p {
    margin: 0 0 0.5em 0;
    word-wrap: break-word;
    overflow-wrap: break-word;
}
.surat-body h2 {
    margin: 0 0 0.5em 0;
    font-size: 14pt;
}
.surat-body figure.table {
    width: 100%;
    max-width: 100%;
    margin: 0;
    padding: 0;
}
.surat-body table {
    border-collapse: collapse;
    width: 100%;
    max-width: 100%;
}
.surat-body table:not(.standard-kop-inner):not(.kop-table):not(.blok-ttd-table) {
    table-layout: fixed;
}
.surat-body table:not(.standard-kop-inner):not(.kop-table):not(.blok-ttd-table) td,
.surat-body table:not(.standard-kop-inner):not(.kop-table):not(.blok-ttd-table) th {
    word-wrap: break-word;
    overflow-wrap: break-word;
    word-break: break-word;
}
.surat-body .standard-kop-inner td,
.surat-body .standard-kop-inner th,
.surat-body .kop-table td,
.surat-body .kop-table th,
.surat-body .blok-ttd-table td,
.surat-body .blok-ttd-table th,
.surat-body table[style*="border:none"] td,
.surat-body table[style*="border: none"] td,
.surat-body table[style*="border-style:none"] td,
.surat-body table[style*="border-style: none"] td,
.surat-body td[style*="border:none"],
.surat-body td[style*="border: none"],
.surat-body td[style*="border-style:none"],
.surat-body td[style*="border-style: none"],
.surat-body th[style*="border:none"],
.surat-body th[style*="border: none"],
.surat-body th[style*="border-style:none"],
.surat-body th[style*="border-style: none"] {
    border: none !important;
}
.blok-ttd {
    margin-top: 28px;
    page-break-inside: avoid;
}
.blok-ttd-table {
    width: 100%;
    max-width: 100%;
    border: none;
    border-collapse: collapse;
    table-layout: fixed;
}
.blok-ttd-table td {
    border: none !important;
    vertical-align: top;
    padding: 0;
}
.blok-ttd-inner {
    text-align: center;
    max-width: 100%;
}
.blok-ttd-role {
    margin-bottom: 4px;
}
.blok-ttd-space {
    height: 72px;
    margin: 8px 0;
    text-align: center;
}
.blok-ttd-space img {
    max-height: 70px;
    object-fit: contain;
}
.blok-ttd-name {
    font-weight: bold;
    text-decoration: underline;
}
.blok-ttd-nip {
    font-size: 10pt;
    margin-top: 4px;
}
<?php /**PATH C:\xampp\htdocs\iss\backend\resources\views/surat/partials/document-styles.blade.php ENDPATH**/ ?>