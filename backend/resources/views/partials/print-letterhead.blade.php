@php
    use App\Support\StandardLetterhead;

    $standardInstitution = $institution ?? null;
    $standardLogoPath = StandardLetterhead::resolveLogoPathForPdf($standardInstitution);

    $standardAddress = $standardInstitution
        ? collect([
            $standardInstitution->address,
            $standardInstitution->village ? 'Desa/Kel. ' . $standardInstitution->village : null,
            $standardInstitution->sub_district ? 'Kec. ' . $standardInstitution->sub_district : null,
            $standardInstitution->district,
            $standardInstitution->province,
            $standardInstitution->postal_code,
        ])->filter()->implode(', ')
        : '';

    $standardInfo = $standardInstitution
        ? collect([
            'NPSN: ' . ($standardInstitution->npsn ?: '-'),
            $standardInstitution->nss
                ? \App\Models\Institution::nssLabelForLevel($standardInstitution->level) . ': ' . $standardInstitution->nss
                : null,
            $standardInstitution->phone ? 'Telp: ' . $standardInstitution->phone : null,
            $standardInstitution->email ? 'Email: ' . $standardInstitution->email : null,
            $standardInstitution->website ?: null,
        ])->filter()->implode(' · ')
        : 'NPSN: -';
@endphp

<header class="standard-kop">
    <table class="standard-kop-inner">
        <tr>
            <td class="standard-kop-logo-cell">
                @if($standardLogoPath)
                    <img src="{{ $standardLogoPath }}" alt="Logo institusi" class="standard-kop-logo">
                @endif
            </td>
            <td class="standard-kop-text">
                @if($standardInstitution && !empty($standardInstitution->foundation_name))
                    <p class="standard-kop-foundation">{{ $standardInstitution->foundation_name }}</p>
                @endif
                <p class="standard-kop-school">{{ $standardInstitution->name ?? 'Institusi' }}</p>
                <p class="standard-kop-address">{{ $standardAddress ?: '-' }}</p>
                <p class="standard-kop-info">{{ $standardInfo }}</p>
            </td>
        </tr>
    </table>
</header>
