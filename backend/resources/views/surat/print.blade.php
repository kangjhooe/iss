<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>{{ $surat->judul ?? 'Surat' }}</title>
    <style>
        @include('surat.partials.document-styles')
    </style>
</head>
<body class="surat-density-{{ $density ?? 'compact' }}">
    <div class="surat-body">
        {!! $kopHtml ?? '' !!}
        {!! $isiHtml !!}
        {!! $ttdHtml ?? '' !!}
    </div>
</body>
</html>
