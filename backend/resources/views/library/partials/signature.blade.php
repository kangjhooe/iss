{{-- Tanda tangan Kepala Perpustakaan (dari tugas tambahan ketua_perpus) --}}
<div class="standard-signature-wrap">
    <div class="standard-signature-left"></div>
    <div class="standard-signature-right">
        @include('partials.print-signature', [
            'institution' => $institution,
            'role' => 'Kepala Perpustakaan',
            'name' => $kepala_perpustakaan?->name ?? '',
            'nip' => $kepala_perpustakaan?->nip ?? '',
            'date' => $signature_date ?? now()->locale('id')->translatedFormat('d F Y'),
        ])
    </div>
</div>
