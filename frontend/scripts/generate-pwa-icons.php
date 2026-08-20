<?php

/**
 * Generate PWA icons from the existing servr.in brand logo.
 */

$source = dirname(__DIR__, 2) . '/backend/public/storage/app_branding/favicon_1771841108_logo_servr.id.png';
$fallback = dirname(__DIR__, 2) . '/backend/public/storage/app_branding/app_logo_1771841100_logo_servr.id.png';
$outDir = dirname(__DIR__) . '/public';

if (!is_file($source)) {
    $source = $fallback;
}

if (!is_file($source)) {
    fwrite(STDERR, "Source logo not found\n");
    exit(1);
}

if (!is_dir($outDir) && !mkdir($outDir, 0777, true) && !is_dir($outDir)) {
    fwrite(STDERR, "Cannot create {$outDir}\n");
    exit(1);
}

$srcImg = imagecreatefrompng($source);
if (!$srcImg) {
    fwrite(STDERR, "Cannot read source PNG\n");
    exit(1);
}

imagesavealpha($srcImg, true);

$srcW = imagesx($srcImg);
$srcH = imagesy($srcImg);

function makeIcon($srcImg, $srcW, $srcH, $size, $paddingRatio, $bgRgb, $destPath): void
{
    $canvas = imagecreatetruecolor($size, $size);
    imagealphablending($canvas, false);
    imagesavealpha($canvas, true);

    if ($bgRgb === null) {
        $bg = imagecolorallocatealpha($canvas, 0, 0, 0, 127);
    } else {
        $bg = imagecolorallocate($canvas, $bgRgb[0], $bgRgb[1], $bgRgb[2]);
    }
    imagefilledrectangle($canvas, 0, 0, $size, $size, $bg);
    imagealphablending($canvas, true);

    $inner = (int) round($size * (1 - $paddingRatio * 2));
    $scale = min($inner / $srcW, $inner / $srcH);
    $drawW = (int) round($srcW * $scale);
    $drawH = (int) round($srcH * $scale);
    $x = (int) round(($size - $drawW) / 2);
    $y = (int) round(($size - $drawH) / 2);

    imagecopyresampled($canvas, $srcImg, $x, $y, 0, 0, $drawW, $drawH, $srcW, $srcH);

    imagepng($canvas, $destPath, 6);
    imagedestroy($canvas);
}

$white = [255, 255, 255];
$sky = [14, 165, 233]; // #0ea5e9 theme_color

$jobs = [
    ['pwa-192x192.png', 192, 0.08, $white],
    ['pwa-512x512.png', 512, 0.08, $white],
    ['pwa-maskable-192x192.png', 192, 0.18, $sky],
    ['pwa-maskable-512x512.png', 512, 0.18, $sky],
    ['apple-touch-icon.png', 180, 0.10, $white],
    ['favicon-32x32.png', 32, 0.06, $white],
    ['favicon-192x192.png', 192, 0.08, $white],
];

foreach ($jobs as [$name, $size, $pad, $bg]) {
    $path = $outDir . '/' . $name;
    makeIcon($srcImg, $srcW, $srcH, $size, $pad, $bg, $path);
    echo "Wrote {$path}\n";
}

// Minimal 32x32 ICO wrapping the PNG (Windows-compatible single-image ICO).
$png32 = file_get_contents($outDir . '/favicon-32x32.png');
$pngSize = strlen($png32);
$ico = pack('vvv', 0, 1, 1);
$ico .= pack('CCCCvvVV', 32, 32, 0, 0, 1, 32, $pngSize, 22);
$ico .= $png32;
file_put_contents($outDir . '/favicon.ico', $ico);
echo "Wrote {$outDir}/favicon.ico\n";

imagedestroy($srcImg);
echo "Done.\n";
