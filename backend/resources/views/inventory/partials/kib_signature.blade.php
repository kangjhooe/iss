<div class="standard-signature-wrap">
    <div class="standard-signature-left"></div>
    <div class="standard-signature-right">
        @include('partials.print-signature', [
            'institution' => $institution,
            'date' => $signature_date ?? now()->locale('id')->translatedFormat('d F Y'),
            'as_of_date' => $as_of_date ?? null,
        ])
    </div>
</div>
