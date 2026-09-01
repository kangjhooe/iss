<?php

namespace App\Support;

use App\Models\Institution;
use App\Models\KopSurat;
use Illuminate\Support\Facades\Storage;

/**
 * Kop standar laporan cetak (KOP_STANDAR.md / print-letterhead.blade.php).
 */
class StandardLetterhead
{
    public static function buildAddress(?Institution $institution): string
    {
        if (!$institution) {
            return '';
        }

        $built = collect([
            $institution->address,
            $institution->village ? 'Desa/Kel. ' . $institution->village : null,
            $institution->sub_district ? 'Kec. ' . $institution->sub_district : null,
            $institution->district,
            $institution->province,
            $institution->postal_code,
        ])->filter()->implode(', ');

        if ($built !== '') {
            return $built;
        }

        return RegionAddress::format($institution) ?: '';
    }

    public static function buildInfoLine(?Institution $institution): string
    {
        if (!$institution) {
            return 'NPSN: -';
        }

        return collect([
            'NPSN: ' . ($institution->npsn ?: '-'),
            $institution->nss
                ? Institution::nssLabelForLevel($institution->level) . ': ' . $institution->nss
                : null,
            $institution->phone ? 'Telp: ' . $institution->phone : null,
            $institution->email ? 'Email: ' . $institution->email : null,
            $institution->website ?: null,
        ])->filter()->implode(' · ');
    }

    public static function resolveLogoPath(?Institution $institution): ?string
    {
        if (!$institution || empty($institution->logo)) {
            return null;
        }

        $relative = ltrim(str_replace('\\', '/', (string) $institution->logo), '/');
        if (str_starts_with($relative, 'storage/')) {
            $relative = substr($relative, strlen('storage/'));
        }

        $full = Storage::disk('public')->path($relative);
        if (is_file($full)) {
            return $full;
        }

        $standardLogoValue = (string) $institution->logo;
        $standardLogoUrlPath = parse_url($standardLogoValue, PHP_URL_PATH) ?: $standardLogoValue;
        $standardLogoUrlPath = ltrim($standardLogoUrlPath, '/\\');

        if (str_starts_with($standardLogoUrlPath, 'storage/')) {
            $candidate = public_path(str_replace('/', DIRECTORY_SEPARATOR, $standardLogoUrlPath));
        } else {
            $candidate = public_path(
                'storage' . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $standardLogoUrlPath)
            );
        }

        return is_file($candidate) ? $candidate : null;
    }

    public static function resolveLogoPathForPdf(?Institution $institution): ?string
    {
        return DomPdfImage::resolvePath(self::resolveLogoPath($institution));
    }

    public static function resolveLogoUrl(?Institution $institution): ?string
    {
        if (!$institution || empty($institution->logo)) {
            return null;
        }

        $relative = ltrim(str_replace('\\', '/', (string) $institution->logo), '/');
        if (str_starts_with($relative, 'storage/')) {
            return asset($relative);
        }

        return asset('storage/' . $relative);
    }

    /**
     * Apakah kop surat custom memakai layout standar institusi (bukan HTML/baris kustom penuh).
     */
    public static function shouldUseInstitutionLayout(?KopSurat $kop): bool
    {
        if (!$kop) {
            return true;
        }

        if (!empty($kop->isi_html)) {
            return false;
        }

        return (bool) $kop->is_default;
    }

    public static function renderHtml(?Institution $institution, bool $forPdf = false): string
    {
        if (!$institution) {
            return '';
        }

        $logoSrc = $forPdf
            ? self::resolveLogoPathForPdf($institution)
            : self::resolveLogoUrl($institution);

        $address = e(self::buildAddress($institution) ?: '-');
        $info = e(self::buildInfoLine($institution));
        $foundation = trim((string) ($institution->foundation_name ?? ''));
        $school = e($institution->name ?? 'Institusi');

        $foundationHtml = $foundation !== ''
            ? '<p class="standard-kop-foundation">' . e($foundation) . '</p>'
            : '';

        $logoHtml = $logoSrc
            ? '<img src="' . e(str_replace('\\', '/', $logoSrc)) . '" alt="Logo institusi" class="standard-kop-logo">'
            : '';

        if ($forPdf) {
            // DomPDF: 2 kolom (logo + teks) — sama dengan print-letterhead.blade.php
            return <<<HTML
<header class="standard-kop">
    <table class="standard-kop-inner">
        <tr>
            <td class="standard-kop-logo-cell">{$logoHtml}</td>
            <td class="standard-kop-text">
                {$foundationHtml}
                <p class="standard-kop-school">{$school}</p>
                <p class="standard-kop-address">{$address}</p>
                <p class="standard-kop-info">{$info}</p>
            </td>
        </tr>
    </table>
</header>
HTML;
        }

        return <<<HTML
<header class="standard-kop">
    <table class="standard-kop-inner">
        <tr>
            <td class="standard-kop-logo-cell">{$logoHtml}</td>
            <td class="standard-kop-text">
                {$foundationHtml}
                <p class="standard-kop-school">{$school}</p>
                <p class="standard-kop-address">{$address}</p>
                <p class="standard-kop-info">{$info}</p>
            </td>
            <td class="standard-kop-spacer">&nbsp;</td>
        </tr>
    </table>
</header>
HTML;
    }
}
