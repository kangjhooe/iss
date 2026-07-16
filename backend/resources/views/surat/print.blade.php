<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>{{ $surat->judul ?? 'Surat' }}</title>
    <style>
        @page {
            size: A4;
            margin: 15mm 20mm 20mm 25mm;
        }
        * { box-sizing: border-box; }
        body {
            font-family: 'Times New Roman', Times, serif;
            font-size: 12pt;
            line-height: 1.6;
            color: #000;
            margin: 0;
            padding: 0;
        }
        .surat-body img {
            max-width: 100%;
            height: auto;
        }
        .surat-body table {
            border-collapse: collapse;
            width: 100%;
        }
        .surat-body table td,
        .surat-body table th {
            border: 1px solid #000;
            padding: 4px 8px;
        }
        .surat-body .kop-table td,
        .surat-body .kop-table th {
            border: none !important;
        }
        .surat-body p {
            margin: 0 0 0.5em 0;
        }
        .blok-ttd img {
            max-width: none;
        }
    </style>
</head>
<body>
    <div class="surat-body">
        {!! $kopHtml ?? '' !!}
        {!! $isiHtml !!}
        {!! $ttdHtml ?? '' !!}
    </div>
</body>
</html>
