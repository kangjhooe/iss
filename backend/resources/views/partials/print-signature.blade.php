@php
    $signerInstitution = $institution ?? null;
    $signerPlace = $place
        ?? ($signerInstitution->district ?? $signerInstitution->city ?? '........................');
    $signerDate = $date ?? now()->locale('id')->translatedFormat('d F Y');
    $signerRole = $role ?? \App\Models\Institution::principalTitleForLevel($signerInstitution?->level);
    $signerName = $name ?? ($signerInstitution->principal_name ?? null);
    $signerNip = $nip ?? ($signerInstitution->principal_nip ?? null);
@endphp

<div class="standard-signature">
    <div class="standard-signature-place">{{ $signerPlace }}, {{ $signerDate }}</div>
    <div class="standard-signature-role">{{ $signerRole }}</div>
    <div class="standard-signature-space"></div>
    <div class="standard-signature-name">{{ $signerName ?: '___________________' }}</div>
    <div class="standard-signature-nip">NIP. {{ $signerNip ?: '___________________' }}</div>
</div>
