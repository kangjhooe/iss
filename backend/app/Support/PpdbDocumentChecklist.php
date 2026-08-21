<?php

namespace App\Support;

use App\Models\PpdbChannel;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class PpdbDocumentChecklist
{
    public const PRESETS = [
        ['key' => 'foto', 'label' => 'Foto 3x4', 'required' => true],
        ['key' => 'kk', 'label' => 'Kartu Keluarga', 'required' => true],
        ['key' => 'akte', 'label' => 'Akte Kelahiran', 'required' => true],
        ['key' => 'rapor', 'label' => 'Rapor terakhir', 'required' => false],
        ['key' => 'ijazah', 'label' => 'Ijazah / SKL', 'required' => false],
    ];

    /**
     * @return array<int, array{key:string,label:string,required:bool}>
     */
    public static function normalize(mixed $raw): array
    {
        if (! is_array($raw)) {
            return [];
        }

        $out = [];
        $seen = [];
        foreach ($raw as $item) {
            if (count($out) >= 12) {
                break;
            }
            $label = '';
            $key = '';
            $required = true;
            if (is_string($item)) {
                $label = trim($item);
            } elseif (is_array($item)) {
                $label = trim((string) ($item['label'] ?? $item['name'] ?? ''));
                $key = trim((string) ($item['key'] ?? ''));
                $required = array_key_exists('required', $item)
                    ? filter_var($item['required'], FILTER_VALIDATE_BOOLEAN)
                    : true;
            }
            if ($label === '') {
                continue;
            }
            if ($key === '') {
                $key = Str::slug($label, '_') ?: ('dok_'.(count($out) + 1));
            }
            $key = substr(preg_replace('/[^a-z0-9_\-]/', '', strtolower($key)) ?: ('dok_'.(count($out) + 1)), 0, 40);
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $out[] = [
                'key' => $key,
                'label' => substr($label, 0, 80),
                'required' => $required,
            ];
        }

        return $out;
    }

    /**
     * @param  Collection<int, mixed>|iterable  $documents
     * @return array{items: array<int, array{key:string,label:string,required:bool,uploaded:bool}>, required_total:int, required_uploaded:int, complete:bool}
     */
    public static function summarize(?PpdbChannel $channel, $documents): array
    {
        $required = self::normalize($channel?->required_documents);
        $docs = Collection::make($documents);
        $items = [];
        foreach ($required as $item) {
            $uploaded = $docs->contains(function ($d) use ($item) {
                $key = is_array($d) ? ($d['document_key'] ?? null) : ($d->document_key ?? null);
                $name = trim((string) (is_array($d) ? ($d['name'] ?? '') : ($d->name ?? '')));

                return ($key && $key === $item['key'])
                    || ($name !== '' && strcasecmp($name, $item['label']) === 0);
            });
            $items[] = [...$item, 'uploaded' => (bool) $uploaded];
        }

        $must = array_values(array_filter($items, fn ($i) => $i['required']));
        $uploadedMust = array_values(array_filter($must, fn ($i) => $i['uploaded']));
        $requiredTotal = count($must);

        return [
            'items' => $items,
            'required_total' => $requiredTotal,
            'required_uploaded' => count($uploadedMust),
            'complete' => $requiredTotal === 0 || count($uploadedMust) === $requiredTotal,
        ];
    }

    public static function labelForKey(?PpdbChannel $channel, ?string $key): ?string
    {
        $key = trim((string) $key);
        if ($key === '') {
            return null;
        }
        foreach (self::normalize($channel?->required_documents) as $item) {
            if ($item['key'] === $key) {
                return $item['label'];
            }
        }

        return null;
    }

    /**
     * @return array{0:?string,1:string} [document_key, name]
     */
    public static function resolveUpload(?PpdbChannel $channel, mixed $key, mixed $name): array
    {
        $rawKey = strtolower(trim((string) $key));
        $cleanKey = $rawKey === ''
            ? ''
            : substr(preg_replace('/[^a-z0-9_\-]/', '', $rawKey) ?: '', 0, 64);
        $documentKey = $cleanKey !== '' ? $cleanKey : null;
        $resolvedName = trim((string) $name);
        $label = $documentKey ? self::labelForKey($channel, $documentKey) : null;
        if ($label && $resolvedName === '') {
            $resolvedName = $label;
        }

        return [$documentKey, $resolvedName];
    }
}
