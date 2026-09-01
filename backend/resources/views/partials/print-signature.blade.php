@php
    use App\Models\Institution;
    use App\Support\StructuralPositionResolver;

    $signerInstitution = $institution ?? null;
    $signerPlace = $place
        ?? ($signerInstitution->district ?? $signerInstitution->city ?? '........................');
    $signerDate = $date ?? now()->locale('id')->translatedFormat('d F Y');
    $signerRole = $role ?? Institution::principalTitleForLevel($signerInstitution?->level);
    $showPlaceDate = $show_place_date ?? true;

    if (!isset($name) && $signerInstitution) {
        $resolved = StructuralPositionResolver::principalAt($signerInstitution, $as_of_date ?? null);
        $signerName = $resolved['name'] ?? null;
        $signerNip = $resolved['nip'] ?? null;
        if (!isset($role) && !empty($resolved['role'])) {
            $signerRole = $resolved['role'];
        }
    } else {
        $signerName = $name ?? null;
        $signerNip = $nip ?? null;
        if (($signerName === null || $signerNip === null) && $signerInstitution) {
            $resolved = StructuralPositionResolver::principalAt($signerInstitution, $as_of_date ?? null);
            $signerName = $signerName ?? $resolved['name'];
            $signerNip = $signerNip ?? $resolved['nip'];
        }
    }
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
