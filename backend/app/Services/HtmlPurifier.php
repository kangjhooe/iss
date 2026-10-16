<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

/**
 * Sanitasi HTML untuk soal/stimulus. Pakai Mews\Purifier jika terpasang, fallback strip_tags.
 * Aman dipanggil saat paket mews/purifier tidak terpasang (tanpa Fatal Error).
 */
class HtmlPurifier
{
    private const ALLOWED_TAGS = '<p><br><strong><b><em><i><u><s><sub><sup><span><ul><ol><li><a><img><table><thead><tbody><tr><th><td><div><h2><h3><h4><figure><figcaption><audio><video><source><iframe>';

    public static function sanitizeQuestion(string $html, string $profile = 'question'): string
    {
        $html = trim($html);
        if ($html === '') {
            return $html;
        }

        $html = self::collapseMath($html);

        if (class_exists(\Mews\Purifier\Facades\Purifier::class)) {
            try {
                return \Mews\Purifier\Facades\Purifier::clean($html, $profile);
            } catch (\Throwable $e) {
                Log::warning('HtmlPurifier: Purifier::clean failed', ['error' => $e->getMessage()]);
            }
        }

        return strip_tags($html, self::ALLOWED_TAGS);
    }

    /**
     * Simpan rumus sebagai sumber LaTeX, bukan HTML KaTeX.
     * Sisa dokumen tidak diubah.
     */
    private static function collapseMath(string $html): string
    {
        if (! str_contains($html, 'data-math') && ! str_contains($html, 'data-value')) {
            return $html;
        }

        $offset = 0;
        $out = '';
        $length = strlen($html);

        while ($offset < $length) {
            $pos = stripos($html, '<span', $offset);
            if ($pos === false) {
                $out .= substr($html, $offset);
                break;
            }

            $open = self::readTag($html, $pos);
            if ($open === null) {
                $out .= substr($html, $offset);
                break;
            }

            $tex = self::formulaSource($open['tag']);
            if ($tex === null) {
                $out .= substr($html, $offset, $open['end'] + 1 - $offset);
                $offset = $open['end'] + 1;
                continue;
            }

            $closeAt = self::matchingSpanClose($html, $open['end'] + 1);
            if ($closeAt === null) {
                $out .= substr($html, $offset);
                break;
            }

            $out .= substr($html, $offset, $pos - $offset);
            $out .= self::formulaSpan($tex['tex'], $tex['display']);
            $offset = $closeAt;
        }

        return $out;
    }

    /**
     * @return array{tag: string, end: int}|null
     */
    private static function readTag(string $html, int $pos): ?array
    {
        $length = strlen($html);
        $quote = null;
        for ($i = $pos + 1; $i < $length; $i++) {
            $ch = $html[$i];
            if ($quote !== null) {
                if ($ch === $quote) {
                    $quote = null;
                }
                continue;
            }
            if ($ch === '"' || $ch === "'") {
                $quote = $ch;
                continue;
            }
            if ($ch === '>') {
                return ['tag' => substr($html, $pos, $i - $pos + 1), 'end' => $i];
            }
        }

        return null;
    }

    /**
     * @return array{tex: string, display: bool}|null
     */
    private static function formulaSource(string $openTag): ?array
    {
        $tex = self::attrValue($openTag, 'data-math');
        if ($tex === null) {
            $tex = self::attrValue($openTag, 'data-value');
        }
        if ($tex === null) {
            return null;
        }

        return [
            'tex' => $tex,
            'display' => self::attrValue($openTag, 'data-display') === 'block',
        ];
    }

    private static function attrValue(string $openTag, string $name): ?string
    {
        $pattern = '/\b'.preg_quote($name, '/').'\s*=\s*(["\'])([\s\S]*?)\1/i';
        if (! preg_match($pattern, $openTag, $matches)) {
            return null;
        }

        return html_entity_decode($matches[2], ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    private static function matchingSpanClose(string $html, int $from): ?int
    {
        $depth = 1;
        $i = $from;
        $length = strlen($html);
        while ($i < $length && $depth > 0) {
            $nextOpen = stripos($html, '<span', $i);
            $nextClose = stripos($html, '</span>', $i);
            if ($nextClose === false) {
                return null;
            }
            if ($nextOpen !== false && $nextOpen < $nextClose) {
                $depth++;
                $i = $nextOpen + 5;
                continue;
            }
            $depth--;
            $i = $nextClose + 7;
            if ($depth === 0) {
                return $i;
            }
        }

        return null;
    }

    private static function formulaSpan(string $tex, bool $display): string
    {
        $attr = htmlspecialchars($tex, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $displayAttr = $display ? ' data-display="block"' : '';

        return '<span class="ql-formula" data-math="'.$attr.'"'.$displayAttr.'>'.$attr.'</span>';
    }
}
