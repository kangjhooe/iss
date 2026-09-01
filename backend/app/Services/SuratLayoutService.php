<?php

namespace App\Services;

use App\Models\AsetTandaTangan;
use App\Models\KopSurat;
use App\Models\Surat;
use App\Support\DomPdfImage;
use App\Support\StandardLetterhead;
use Illuminate\Support\Facades\Storage;

class SuratLayoutService
{
    /**
     * Resolve absolute filesystem path for DomPDF images.
     */
    public function absolutePath(?string $relativePath): ?string
    {
        if (!$relativePath) {
            return null;
        }

        $full = Storage::disk('public')->path($relativePath);
        return file_exists($full) ? $full : null;
    }

    public function absolutePathForPdf(?string $relativePath): ?string
    {
        return DomPdfImage::resolvePath($this->absolutePath($relativePath));
    }

    public function resolveKop(Surat $surat): ?KopSurat
    {
        if (!$surat->tampilkan_kop) {
            return null;
        }

        if ($surat->kop_id) {
            return KopSurat::aktif()
                ->where('institution_id', $surat->institution_id)
                ->find($surat->kop_id);
        }

        return KopSurat::aktif()
            ->where('institution_id', $surat->institution_id)
            ->where('is_default', true)
            ->first();
    }

    public function resolveTandaTangan(Surat $surat): ?AsetTandaTangan
    {
        if (!$surat->tampilkan_tanda_tangan || !$surat->tanda_tangan_id) {
            return null;
        }

        return AsetTandaTangan::aktif()
            ->jenis('tanda_tangan')
            ->where('institution_id', $surat->institution_id)
            ->find($surat->tanda_tangan_id);
    }

    public function resolveStempel(Surat $surat): ?AsetTandaTangan
    {
        if (!$surat->tampilkan_stempel || !$surat->stempel_id) {
            return null;
        }

        return AsetTandaTangan::aktif()
            ->jenis('stempel')
            ->where('institution_id', $surat->institution_id)
            ->find($surat->stempel_id);
    }

    public function renderKopForSurat(Surat $surat, bool $forPdf = false): string
    {
        if (!$surat->tampilkan_kop) {
            return '';
        }

        $surat->loadMissing('institution');
        $kop = $this->resolveKop($surat);

        if (StandardLetterhead::shouldUseInstitutionLayout($kop)) {
            return StandardLetterhead::renderHtml($surat->institution, $forPdf);
        }

        return $this->renderKopHtml($kop, $forPdf);
    }

    /**
     * Build HTML fragment for kop (frontend preview uses URLs; PDF uses absolute paths).
     */
    public function renderKopHtml(?KopSurat $kop, bool $forPdf = false): string
    {
        if (!$kop) {
            return '';
        }

        if (!empty($kop->isi_html)) {
            return $kop->isi_html;
        }

        $logoKiri = $forPdf
            ? $this->absolutePathForPdf($kop->logo_kiri)
            : $kop->logo_kiri_url;
        $logoKanan = $forPdf
            ? $this->absolutePathForPdf($kop->logo_kanan)
            : $kop->logo_kanan_url;

        $meta = array_filter([
            $kop->telepon ? 'Telp. ' . e($kop->telepon) : null,
            $kop->email ? 'Email: ' . e($kop->email) : null,
            $kop->website ? e($kop->website) : null,
        ]);

        $html = '<div class="kop-surat">';
        $html .= '<table class="kop-table" style="width:100%;border:none;border-collapse:collapse;"><tr>';
        $html .= '<td style="border:none;width:80px;vertical-align:middle;text-align:left;">';
        if ($logoKiri) {
            $src = $forPdf ? $logoKiri : $logoKiri;
            $html .= '<img src="' . e($src) . '" alt="Logo" style="max-width:70px;max-height:70px;object-fit:contain;" />';
        }
        $html .= '</td>';
        $html .= '<td style="border:none;vertical-align:middle;text-align:center;">';
        if ($kop->baris_1) {
            $html .= '<div style="font-size:14pt;font-weight:bold;text-transform:uppercase;line-height:1.2;">' . e($kop->baris_1) . '</div>';
        }
        if ($kop->baris_2) {
            $html .= '<div style="font-size:12pt;font-weight:bold;text-transform:uppercase;line-height:1.2;">' . e($kop->baris_2) . '</div>';
        }
        if ($kop->baris_3) {
            $html .= '<div style="font-size:11pt;font-weight:bold;line-height:1.2;">' . e($kop->baris_3) . '</div>';
        }
        if ($kop->alamat) {
            $html .= '<div style="font-size:9pt;margin-top:4px;">' . e($kop->alamat) . '</div>';
        }
        if ($meta) {
            $html .= '<div style="font-size:8pt;margin-top:2px;">' . implode(' | ', $meta) . '</div>';
        }
        $html .= '</td>';
        $html .= '<td style="border:none;width:80px;vertical-align:middle;text-align:right;">';
        if ($logoKanan) {
            $html .= '<img src="' . e($logoKanan) . '" alt="Logo" style="max-width:70px;max-height:70px;object-fit:contain;" />';
        }
        $html .= '</td></tr></table>';

        if ($kop->tampilkan_garis) {
            $html .= '<div style="border-top:3px solid #000;border-bottom:1px solid #000;height:4px;margin:8px 0 16px;"></div>';
        }

        $html .= '</div>';

        return $html;
    }

    public function renderSignatureHtml(
        ?AsetTandaTangan $ttd,
        ?AsetTandaTangan $stempel,
        string $posisi = 'kanan',
        bool $forPdf = false
    ): string {
        if (!$ttd && !$stempel) {
            return '';
        }

        if ($forPdf) {
            return $this->renderSignatureHtmlForPdf($ttd, $stempel, $posisi);
        }

        $align = $posisi === 'kiri' ? 'left' : 'right';
        $jabatan = $ttd?->pemilik_jabatan ?: 'Kepala Sekolah';
        $nama = $ttd?->pemilik_nama ?: '';
        $nip = $ttd?->pemilik_nip ?: '';

        $ttdSrc = null;
        $stempelSrc = null;
        $ttdW = ($ttd?->lebar_mm ?? 40) . 'mm';
        $ttdH = ($ttd?->tinggi_mm ?? 20) . 'mm';
        $stempelW = ($stempel?->lebar_mm ?? 35) . 'mm';
        $stempelH = ($stempel?->tinggi_mm ?? 35) . 'mm';

        if ($forPdf) {
            $ttdSrc = $this->absolutePathForPdf($ttd->file_path);
        } else {
            $ttdSrc = $ttd->file_url;
        }
        if ($stempel) {
            $stempelSrc = $forPdf
                ? $this->absolutePathForPdf($stempel->file_path)
                : $stempel->file_url;
        }

        $html = '<div class="blok-ttd" style="margin-top:40px;text-align:' . $align . ';">';
        $html .= '<div style="display:inline-block;text-align:center;min-width:220px;position:relative;">';
        $html .= '<div style="margin-bottom:4px;">' . e($jabatan) . ',</div>';
        $html .= '<div style="position:relative;height:90px;margin:8px 0;">';

        if ($stempelSrc) {
            $html .= '<img src="' . e($stempelSrc) . '" alt="Stempel" style="position:absolute;left:50%;top:50%;transform:translate(-70%,-50%);width:' . $stempelW . ';height:' . $stempelH . ';object-fit:contain;opacity:0.85;z-index:1;" />';
        }
        if ($ttdSrc) {
            $html .= '<img src="' . e($ttdSrc) . '" alt="Tanda Tangan" style="position:relative;z-index:2;width:' . $ttdW . ';height:' . $ttdH . ';object-fit:contain;" />';
        } elseif (!$stempelSrc) {
            $html .= '<div style="height:70px;"></div>';
        }

        $html .= '</div>';
        if ($nama) {
            $html .= '<div style="font-weight:bold;text-decoration:underline;">' . e($nama) . '</div>';
        }
        if ($nip) {
            $html .= '<div style="font-size:10pt;">NIP. ' . e($nip) . '</div>';
        }
        $html .= '</div></div>';

        return $html;
    }

    private function renderSignatureHtmlForPdf(
        ?AsetTandaTangan $ttd,
        ?AsetTandaTangan $stempel,
        string $posisi = 'kanan'
    ): string {
        $jabatan = $ttd?->pemilik_jabatan ?: 'Kepala Sekolah';
        $nama = $ttd?->pemilik_nama ?: '';
        $nip = $ttd?->pemilik_nip ?: '';

        $ttdSrc = $ttd ? $this->absolutePathForPdf($ttd->file_path) : null;
        $stempelSrc = $stempel ? $this->absolutePathForPdf($stempel->file_path) : null;

        $leftPad = $posisi === 'kiri' ? '' : '<td style="width:50%;border:none;"></td>';
        $rightPad = $posisi === 'kiri' ? '<td style="width:50%;border:none;"></td>' : '';

        $images = '';
        if ($stempelSrc) {
            $images .= '<img src="' . e($stempelSrc) . '" alt="Stempel" style="max-height:55px;margin:0 4px;vertical-align:middle;" />';
        }
        if ($ttdSrc) {
            $images .= '<img src="' . e($ttdSrc) . '" alt="Tanda Tangan" style="max-height:55px;vertical-align:middle;" />';
        }
        if ($images === '') {
            $images = '&nbsp;';
        }

        $namaHtml = $nama
            ? '<div class="blok-ttd-name">' . e($nama) . '</div>'
            : '';
        $nipHtml = $nip
            ? '<div class="blok-ttd-nip">NIP. ' . e($nip) . '</div>'
            : '';

        return '<div class="blok-ttd"><table class="blok-ttd-table"><tr>'
            . $leftPad
            . '<td style="width:50%;border:none;text-align:center;">'
            . '<div class="blok-ttd-inner">'
            . '<div class="blok-ttd-role">' . e($jabatan) . ',</div>'
            . '<div class="blok-ttd-space">' . $images . '</div>'
            . $namaHtml
            . $nipHtml
            . '</div></td>'
            . $rightPad
            . '</tr></table></div>';
    }

    public function buildPrintParts(Surat $surat, bool $forPdf = false): array
    {
        $surat->loadMissing(['kop', 'tandaTangan', 'stempel', 'institution']);

        $ttd = $this->resolveTandaTangan($surat);
        $stempel = $this->resolveStempel($surat);
        $kop = $this->resolveKop($surat);

        $isiHtml = $surat->isi_html ?? '';
        if ($forPdf) {
            $isiHtml = $this->prepareIsiHtmlForPdf($isiHtml);
        }

        return [
            'kopHtml' => $this->renderKopForSurat($surat, $forPdf),
            'isiHtml' => $isiHtml,
            'ttdHtml' => $this->renderSignatureHtml($ttd, $stempel, $surat->posisi_ttd ?? 'kanan', $forPdf),
            'kop' => $kop,
            'tandaTangan' => $ttd,
            'stempel' => $stempel,
        ];
    }

    /**
     * Normalisasi HTML CKEditor untuk DomPDF — pertahankan hasil edit user (kolom, lebar, dll.).
     */
    public function prepareIsiHtmlForPdf(string $html): string
    {
        if ($html === '') {
            return $html;
        }

        // Lepas wrapper <figure class="table"> CKEditor; isi tabel tetap utuh.
        $html = preg_replace('/<figure[^>]*class="[^"]*table[^"]*"[^>]*>\s*(<table)/i', '$1', $html) ?? $html;
        $html = preg_replace('/<\/table>\s*<\/figure>/i', '</table>', $html) ?? $html;

        // Gambar inline dari editor — path absolut untuk DomPDF.
        $html = preg_replace_callback(
            '/<img\b[^>]*\bsrc=["\']([^"\']+)["\'][^>]*>/i',
            function (array $m): string {
                $resolved = $this->resolveInlineImageForPdf($m[1]);
                if (!$resolved) {
                    return '';
                }

                return preg_replace(
                    '/\bsrc=["\'][^"\']+["\']/i',
                    'src="' . e($resolved) . '"',
                    $m[0],
                    1
                ) ?? '';
            },
            $html
        ) ?? $html;

        return $html;
    }

    private function resolveInlineImageForPdf(string $src): ?string
    {
        $src = html_entity_decode(trim($src), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        if ($src === '') {
            return null;
        }

        $path = parse_url($src, PHP_URL_PATH) ?: $src;
        $path = ltrim(str_replace('\\', '/', $path), '/');

        if (str_starts_with($path, 'storage/')) {
            $relative = substr($path, strlen('storage/'));

            return DomPdfImage::resolvePath($this->absolutePath($relative));
        }

        if (is_file($src)) {
            return DomPdfImage::resolvePath($src);
        }

        return null;
    }
}
