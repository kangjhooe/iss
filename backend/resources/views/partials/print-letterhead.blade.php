@php
    $standardInstitution = $institution ?? null;
    $standardLogoPath = null;

    if ($standardInstitution && !empty($standardInstitution->logo)) {
        $standardLogoValue = (string) $standardInstitution->logo;
        $standardLogoUrlPath = parse_url($standardLogoValue, PHP_URL_PATH) ?: $standardLogoValue;
        $standardLogoUrlPath = ltrim($standardLogoUrlPath, '/\\');

        if (str_starts_with($standardLogoUrlPath, 'storage/')) {
            $standardLogoCandidate = public_path(str_replace('/', DIRECTORY_SEPARATOR, $standardLogoUrlPath));
        } else {
            $standardLogoCandidate = public_path(
                'storage' . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $standardLogoUrlPath)
            );
        }

        if (is_file($standardLogoCandidate)) {
            $standardLogoPath = $standardLogoCandidate;
        }
    }

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
            $standardInstitution->nss ? 'NSS: ' . $standardInstitution->nss : null,
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
            <td class="standard-kop-logo-cell"></td>
        </tr>
    </table>
</header>
