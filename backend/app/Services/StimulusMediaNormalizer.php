<?php

namespace App\Services;

/**
 * Normalisasi & validasi blok media stimulus (image/audio/video/youtube).
 */
class StimulusMediaNormalizer
{
    public const TYPES = ['image', 'audio', 'video', 'youtube'];

    /**
     * @param  mixed  $media
     * @return list<array{type: string, url: string, caption: ?string, video_id: ?string}>
     */
    public static function normalize(mixed $media): array
    {
        if (! is_array($media)) {
            return [];
        }

        $out = [];
        foreach ($media as $item) {
            if (! is_array($item)) {
                continue;
            }
            $type = strtolower(trim((string) ($item['type'] ?? '')));
            if (! in_array($type, self::TYPES, true)) {
                continue;
            }

            $url = trim((string) ($item['url'] ?? ''));
            $caption = isset($item['caption']) ? trim((string) $item['caption']) : null;
            if ($caption === '') {
                $caption = null;
            }

            $videoId = null;
            if ($type === 'youtube') {
                $videoId = self::extractYoutubeId($url) ?: (isset($item['video_id']) ? trim((string) $item['video_id']) : null);
                if (! $videoId || ! preg_match('/^[A-Za-z0-9_-]{6,20}$/', $videoId)) {
                    continue;
                }
                $url = 'https://www.youtube-nocookie.com/embed/' . $videoId;
            } else {
                if ($url === '' || (! str_starts_with($url, 'http://') && ! str_starts_with($url, 'https://') && ! str_starts_with($url, '/storage/'))) {
                    continue;
                }
            }

            $out[] = [
                'type' => $type,
                'url' => $url,
                'caption' => $caption,
                'video_id' => $videoId,
            ];

            if (count($out) >= 20) {
                break;
            }
        }

        return $out;
    }

    public static function extractYoutubeId(string $url): ?string
    {
        $url = trim($url);
        if ($url === '') {
            return null;
        }

        // Already an ID
        if (preg_match('/^[A-Za-z0-9_-]{6,20}$/', $url)) {
            return $url;
        }

        $patterns = [
            '/(?:youtube\.com\/watch\?[^#]*v=|youtube\.com\/embed\/|youtube-nocookie\.com\/embed\/|youtu\.be\/|youtube\.com\/shorts\/)([A-Za-z0-9_-]{6,20})/',
        ];
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $url, $m)) {
                return $m[1];
            }
        }

        return null;
    }

    /**
     * Infer stimulus type label from media + content.
     */
    public static function inferType(array $media, string $content = ''): string
    {
        if ($media === []) {
            return 'text';
        }
        $types = array_unique(array_column($media, 'type'));
        if (count($types) === 1) {
            return $types[0];
        }

        return 'mixed';
    }
}
