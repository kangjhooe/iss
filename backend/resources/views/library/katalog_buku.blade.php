<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Katalog Buku - {{ $institution->name ?? 'Perpustakaan' }}</title>
    <style>
        @page { margin: 1.2cm; size: A4 landscape; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 9pt; line-height: 1.3; color: #000; }
        .header { text-align: center; margin-bottom: 12px; }
        .header h1 { font-size: 14pt; font-weight: bold; margin: 0 0 4px 0; }
        .header p { font-size: 8pt; margin: 0; color: #444; }
        .period { font-size: 8pt; margin-bottom: 8px; color: #555; }
        .stats { margin-bottom: 10px; padding: 8px 10px; background: #f5f5f5; border: 1px solid #ddd; font-size: 8pt; }
        table { width: calc(100% - 2px); max-width: calc(100% - 2px); border-collapse: collapse; font-size: 8pt; }
        table th, table td { border: 1px solid #333; padding: 4px 6px; text-align: left; }
        table th { background: #e8e8e8; font-weight: bold; }
        table td.num { text-align: center; width: 28px; }
        table td.center { text-align: center; }
        .footer { margin-top: 10px; font-size: 7pt; text-align: center; color: #666; }
        @include('partials.print-letterhead-styles')
        @include('partials.print-signature-styles')
    </style>
</head>
<body>
    @include('partials.print-letterhead', ['institution' => $institution])
    <div class="header">
        <h1>KATALOG BUKU PERPUSTAKAAN</h1>
    </div>

    @if(!empty($filter_label))
    <div class="period">Filter: {{ $filter_label }}</div>
    @endif

    <div class="stats">
        Total judul: <strong>{{ $books->count() }}</strong>
        &nbsp;|&nbsp; Dicetak: {{ $printed_at }}
    </div>

    <table>
        <thead>
            <tr>
                <th class="num">No</th>
                <th>Judul</th>
                <th>Pengarang</th>
                <th>Kategori</th>
                <th>ISBN</th>
                <th>Tahun</th>
                <th>Rak</th>
                <th class="center">Eks.</th>
                <th class="center">Ebook</th>
            </tr>
        </thead>
        <tbody>
            @forelse($books as $i => $book)
            <tr>
                <td class="num">{{ $i + 1 }}</td>
                <td>{{ $book->title }}</td>
                <td>{{ $book->author ?? '-' }}</td>
                <td>{{ $book->category ? ($book->category->code . ' - ' . $book->category->name) : '-' }}</td>
                <td>{{ $book->isbn ?? '-' }}</td>
                <td class="center">{{ $book->year ?? '-' }}</td>
                <td>{{ $book->shelf_code ?? '-' }}</td>
                <td class="center">{{ $book->copies_count ?? 0 }}</td>
                <td class="center">{{ $book->ebook_path ? 'Ya' : 'Tidak' }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="9" class="center">Tidak ada data buku.</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    @include('library.partials.signature', [
        'institution' => $institution,
        'kepala_perpustakaan' => $kepala_perpustakaan ?? null,
        'as_of_date' => $as_of_date ?? null,
    ])

    <div class="footer print-document-footer">Dokumen digenerate sistem · {{ $printed_at }}</div>
</body>
</html>
