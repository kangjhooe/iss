<div class="standard-signature-wrap">
    <div class="standard-signature-left">
        @include('partials.print-signature', [
            'institution' => $institution,
            'role' => 'Mengetahui, '.\App\Models\Institution::principalTitleForLevel($institution?->level),
            'show_place_date' => false,
            'as_of_date' => $as_of_date ?? null,
        ])
    </div>
    <div class="standard-signature-right">
        @include('partials.print-signature', [
            'institution' => $institution,
            'role' => 'Wali Kelas',
            'name' => $wali_kelas?->name ?? '',
            'nip' => $wali_kelas?->nip ?? '',
            'date' => $signature_date ?? now()->locale('id')->translatedFormat('d F Y'),
            'as_of_date' => $as_of_date ?? null,
            'show_place_date' => true,
        ])
    </div>
</div>
