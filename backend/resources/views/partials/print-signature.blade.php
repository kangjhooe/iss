@php
    $signerInstitution = $institution ?? null;
    $signerPlace = $place
        ?? ($signerInstitution->district ?? $signerInstitution->city ?? '........................');
    $signerDate = $date ?? now()->locale('id')->translatedFormat('d F Y');
    $signerRole = $role ?? \App\Models\Institution::principalTitleForLevel($signerInstitution?->level);
    $signerName = $name ?? ($signerInstitution->principal_name ?? null);
    $signerNip = $nip ?? ($signerInstitution->principal_nip ?? null);
    $showPlaceDate = $show_place_date ?? true;
@endphp

<div class="standard-signature">
    @if($showPlaceDate)
        <div class="standard-signature-place">{{ $signerPlace }}, {{ $signerDate }}</div>
    @else
        <div class="standard-signature-place">&nbsp;</div>
    @endif
    <div class="standard-signature-role">{{ $signerRole }}</div>
    <div class="standard-signature-space"></div>
    <div class="standard-signature-name">{{ $signerName ?: '___________________' }}</div>
    <div class="standard-signature-nip">NIP. {{ $signerNip ?: '___________________' }}</div>
</div>
