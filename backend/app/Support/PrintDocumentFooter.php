<?php

namespace App\Support;

use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class PrintDocumentFooter
{
    public static function applicationName(): string
    {
        $name = config('app.name');

        if ($name !== null && $name !== '' && $name !== 'Laravel') {
            return (string) $name;
        }

        return 'servr.in';
    }

    public static function resolvePrintedBy(?string $printedBy = null): string
    {
        $name = $printedBy ?? Auth::user()?->name;

        return ($name !== null && trim($name) !== '') ? trim($name) : 'Pengguna';
    }

    public static function formatPrintedAt(mixed $printedAt = null): string
    {
        if ($printedAt === null || $printedAt === '') {
            return now()->locale('id')->isoFormat('D MMMM YYYY HH:mm');
        }

        if ($printedAt instanceof \DateTimeInterface) {
            return Carbon::instance($printedAt)->locale('id')->isoFormat('D MMMM YYYY HH:mm');
        }

        try {
            return Carbon::parse((string) $printedAt)->locale('id')->isoFormat('D MMMM YYYY HH:mm');
        } catch (\Throwable) {
            return (string) $printedAt;
        }
    }

    public static function line(?string $printedBy = null, mixed $printedAt = null): string
    {
        $by = self::resolvePrintedBy($printedBy);
        $app = self::applicationName();
        $at = self::formatPrintedAt($printedAt);

        return "Dicetak oleh {$by} melalui {$app} pada tanggal {$at}";
    }
}
