<?php

namespace App\Helpers;

class LetterHelper
{
    /**
     * Konversi angka bulan ke Romawi.
     */
    public static function monthToRoman(int $month): string
    {
        $romans = [
            1 => 'I', 2 => 'II', 3 => 'III', 4 => 'IV', 5 => 'V', 6 => 'VI',
            7 => 'VII', 8 => 'VIII', 9 => 'IX', 10 => 'X', 11 => 'XI', 12 => 'XII'
        ];

        return $romans[$month] ?? '';
    }

    /**
     * Generate nomor surat resmi.
     * Format: KK-NNN/JS/{NPSN}/BLN/TAHUN
     * 
     * @param string $letterTypeCode Kode jenis surat (01-16)
     * @param int $sequenceNumber Nomor urut (001, 002, dst)
     * @param string $npsn Nomor Pokok Sekolah Nasional
     * @param \DateTime|string $date Tanggal surat
     * @return string Nomor surat lengkap
     */
    public static function generateLetterNumber(
        string $letterTypeCode,
        int $sequenceNumber,
        string $npsn,
        $date
    ): string {
        // Get jenis surat abbreviation
        $letterType = \App\Models\Correspondence::getLetterType($letterTypeCode);
        $abbr = $letterType['abbr'] ?? 'SL';

        // Parse date
        if (is_string($date)) {
            $date = new \DateTime($date);
        }

        $month = (int)$date->format('m');
        $year = $date->format('Y');
        $monthRoman = self::monthToRoman($month);

        // Format nomor urut 3 digit
        $sequence = str_pad($sequenceNumber, 3, '0', STR_PAD_LEFT);

        // Format: KK-NNN/JS/{NPSN}/BLN/TAHUN
        return sprintf(
            '%s-%s/%s/%s/%s/%s',
            $letterTypeCode,
            $sequence,
            $abbr,
            $npsn,
            $monthRoman,
            $year
        );
    }
}
