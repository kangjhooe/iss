<?php

namespace App\Services;

use App\Models\BankSoal;
use App\Models\QuestionBank;
use App\Models\QuestionOption;
use App\Models\QuestionStimulus;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

class BankSoalBackupService
{
    /**
     * Backup isi bank soal (soal, opsi, stimulus, aset) ke file ZIP.
     * Tidak menyertakan data bank_soal: kode, nama, mapel, kelas, keterangan.
     */
    public function backup(BankSoal $bankSoal): string
    {
        $bankSoal->loadMissing(['questions.options', 'questions.stimulus']);
        $questions = $bankSoal->questions()->with(['options', 'stimulus'])->get();

        $tmpDir = storage_path('app/temp/bank-backup-' . $bankSoal->id . '-' . time());
        if (!is_dir($tmpDir)) {
            mkdir($tmpDir, 0755, true);
        }
        $assetsDir = $tmpDir . '/assets';
        mkdir($assetsDir, 0755, true);

        $assetMap = []; // old path -> filename in ZIP
        $stimuliData = [];
        $stimulusIdMap = []; // old_id -> index in stimuliData

        // Stimuli dipakai oleh soal di bank ini
        $stimulusIds = $questions->pluck('stimulus_id')->filter()->unique()->values();
        $stimuli = QuestionStimulus::whereIn('id', $stimulusIds)->get();
        foreach ($stimuli as $idx => $s) {
            $stimulusIdMap[$s->id] = $idx;
            $attachmentPath = $s->attachment_path;
            $assetName = null;
            if ($attachmentPath && Storage::disk('public')->exists($attachmentPath)) {
                $ext = pathinfo($attachmentPath, PATHINFO_EXTENSION) ?: 'bin';
                $assetName = 'stimulus_' . $s->id . '.' . $ext;
                copy(Storage::disk('public')->path($attachmentPath), $assetsDir . '/' . $assetName);
                $assetMap[$attachmentPath] = 'assets/' . $assetName;
            }
            $stimuliData[] = [
                'id' => $s->id,
                'subject_id' => $s->subject_id,
                'title' => $s->title,
                'content' => $s->content,
                'type' => $s->type,
                'attachment_path' => $attachmentPath ? ($assetMap[$attachmentPath] ?? $attachmentPath) : null,
            ];
        }

        // Soal + opsi
        $questionsData = [];
        $optionsData = [];
        foreach ($questions as $q) {
            $questionsData[] = [
                'id' => $q->id,
                'subject_id' => $q->subject_id,
                'stimulus_id' => $q->stimulus_id,
                'type' => $q->type,
                'body' => $this->rewriteBodyPathsForBackup($q->body, $assetsDir, $assetMap),
                'weight' => $q->weight,
                'key_answer' => $q->key_answer,
                'matching_data' => $q->matching_data,
            ];
            foreach ($q->options as $opt) {
                $optionsData[] = [
                    'question_bank_id' => $q->id,
                    'option_key' => $opt->option_key,
                    'body' => $this->rewriteBodyPathsForBackup($opt->body ?? '', $assetsDir, $assetMap),
                    'is_correct' => $opt->is_correct,
                    'option_weight' => $opt->option_weight,
                    'sort_order' => $opt->sort_order,
                ];
            }
        }

        $manifest = [
            'version' => 1,
            'exported_at' => now()->toIso8601String(),
            'question_count' => count($questionsData),
            'stimulus_count' => count($stimuliData),
            'asset_map' => $assetMap,
        ];

        file_put_contents($tmpDir . '/manifest.json', json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        file_put_contents($tmpDir . '/stimuli.json', json_encode($stimuliData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        file_put_contents($tmpDir . '/questions.json', json_encode($questionsData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        file_put_contents($tmpDir . '/options.json', json_encode($optionsData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        $zipPath = storage_path('app/temp/bank-soal-backup-' . $bankSoal->id . '-' . time() . '.zip');
        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException('Tidak dapat membuat file ZIP.');
        }
        $this->addDirToZip($zip, $tmpDir, '');
        $zip->close();

        $this->removeDirectory($tmpDir);
        return $zipPath;
    }

    private function rewriteBodyPathsForBackup(string $body, string $assetsDir, array &$assetMap): string
    {
        if ($body === '') {
            return $body;
        }
        // Ekstrak path storage dari src/href: .../storage/question_assets/... atau question_assets/...
        $pattern = '#(?:href|src)=(["\'])(?:(?:[^"\']*?/)?(storage/)?(question_assets/[^"\']+))\\1#i';
        $out = preg_replace_callback($pattern, function ($m) use ($assetsDir, &$assetMap) {
            $fullValue = $m[2]; // full URL or path
            $storagePath = $m[4]; // question_assets/... (path relative to public disk)
            $fullPath = Storage::disk('public')->path($storagePath);
            if (!file_exists($fullPath)) {
                return $m[0];
            }
            $base = basename($storagePath);
            $i = 0;
            $assetName = $base;
            while (isset($assetMap[$storagePath]) || file_exists($assetsDir . '/' . $assetName)) {
                $i++;
                $assetName = pathinfo($base, PATHINFO_FILENAME) . '_' . $i . '.' . (pathinfo($base, PATHINFO_EXTENSION) ?: 'bin');
            }
            copy($fullPath, $assetsDir . '/' . $assetName);
            $assetMap[$storagePath] = 'assets/' . $assetName;
            $placeholder = '{{ASSET:' . $assetMap[$storagePath] . '}}';
            return str_replace($fullValue, $placeholder, $m[0]);
        }, $body);
        return $out ?: $body;
    }

    private function addDirToZip(ZipArchive $zip, string $dir, string $zipPath): void
    {
        $items = scandir($dir);
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $full = $dir . '/' . $item;
            $entry = $zipPath ? $zipPath . '/' . $item : $item;
            if (is_dir($full)) {
                $this->addDirToZip($zip, $full, $entry);
            } else {
                $zip->addFile($full, $entry);
            }
        }
    }

    private function removeDirectory(string $dir): void
    {
        $items = scandir($dir);
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $full = $dir . '/' . $item;
            if (is_dir($full)) {
                $this->removeDirectory($full);
            } else {
                unlink($full);
            }
        }
        rmdir($dir);
    }

    /**
     * Restore isi backup ZIP ke bank soal yang sudah ada (target).
     */
    public function restore(UploadedFile $file, BankSoal $targetBank): array
    {
        $tmpDir = storage_path('app/temp/restore-' . time());
        if (!is_dir($tmpDir)) {
            mkdir($tmpDir, 0755, true);
        }

        $zip = new ZipArchive();
        if ($zip->open($file->getRealPath(), ZipArchive::RDONLY) !== true) {
            $this->removeDirectory($tmpDir);
            throw new \RuntimeException('File ZIP tidak valid.');
        }
        $zip->extractTo($tmpDir);
        $zip->close();

        $manifestPath = $tmpDir . '/manifest.json';
        $stimuliPath = $tmpDir . '/stimuli.json';
        $questionsPath = $tmpDir . '/questions.json';
        $optionsPath = $tmpDir . '/options.json';
        if (!file_exists($manifestPath) || !file_exists($stimuliPath) || !file_exists($questionsPath) || !file_exists($optionsPath)) {
            $this->removeDirectory($tmpDir);
            throw new \RuntimeException('Format backup tidak valid (file manifest/questions/options/stimuli tidak lengkap).');
        }

        $manifest = json_decode(file_get_contents($manifestPath), true);
        $stimuliData = json_decode(file_get_contents($stimuliPath), true);
        $questionsData = json_decode(file_get_contents($questionsPath), true);
        $optionsData = json_decode(file_get_contents($optionsPath), true);
        $assetMap = $manifest['asset_map'] ?? [];

        $institutionId = $targetBank->institution_id;
        $subjectId = $targetBank->subject_id;

        $oldToNewStimulusId = [];
        $oldToNewQuestionId = [];

        $result = ['stimuli_created' => 0, 'questions_created' => 0, 'options_created' => 0];

        DB::transaction(function () use (
            $tmpDir,
            $institutionId,
            $subjectId,
            $targetBank,
            $stimuliData,
            $questionsData,
            $optionsData,
            $assetMap,
            &$oldToNewStimulusId,
            &$oldToNewQuestionId,
            &$result
        ) {
            // Buat stimulus baru (pakai subject_id dari target bank agar konsisten)
            foreach ($stimuliData as $s) {
                $oldId = $s['id'];
                $attachmentPath = null;
                if (!empty($s['attachment_path'])) {
                    $zipAssetPath = $s['attachment_path'];
                    if (str_starts_with($zipAssetPath, 'assets/')) {
                        $localFile = $tmpDir . '/' . $zipAssetPath;
                        if (file_exists($localFile)) {
                            $storagePath = 'question_stimuli/' . $institutionId . '/' . date('Y/m') . '/' . basename($zipAssetPath);
                            Storage::disk('public')->put($storagePath, file_get_contents($localFile));
                            $attachmentPath = $storagePath;
                        }
                    }
                }
                $newStimulus = QuestionStimulus::create([
                    'institution_id' => $institutionId,
                    'bank_soal_id' => $targetBank->id,
                    'subject_id' => $subjectId,
                    'title' => $s['title'] ?? '',
                    'content' => $s['content'] ?? '',
                    'type' => $s['type'] ?? 'text',
                    'attachment_path' => $attachmentPath,
                ]);
                $oldToNewStimulusId[$oldId] = $newStimulus->id;
                $result['stimuli_created']++;
            }

            // Buat soal baru
            foreach ($questionsData as $q) {
                $oldQId = $q['id'];
                $newStimulusId = isset($q['stimulus_id'], $oldToNewStimulusId[$q['stimulus_id']])
                    ? $oldToNewStimulusId[$q['stimulus_id']]
                    : null;
                $body = $this->rewriteBodyPathsForRestore($q['body'], $tmpDir, $assetMap, $institutionId);
                $newQuestion = QuestionBank::create([
                    'institution_id' => $institutionId,
                    'bank_soal_id' => $targetBank->id,
                    'subject_id' => $subjectId,
                    'stimulus_id' => $newStimulusId,
                    'type' => $q['type'],
                    'body' => $body,
                    'weight' => $q['weight'] ?? 1,
                    'key_answer' => $q['key_answer'] ?? null,
                    'matching_data' => $q['matching_data'] ?? null,
                ]);
                $oldToNewQuestionId[$oldQId] = $newQuestion->id;
                $result['questions_created']++;
            }

            // Buat opsi baru
            foreach ($optionsData as $opt) {
                $oldQId = $opt['question_bank_id'];
                if (!isset($oldToNewQuestionId[$oldQId])) {
                    continue;
                }
                $body = $this->rewriteBodyPathsForRestore($opt['body'] ?? '', $tmpDir, $assetMap, $institutionId);
                QuestionOption::create([
                    'question_bank_id' => $oldToNewQuestionId[$oldQId],
                    'option_key' => $opt['option_key'],
                    'body' => $body,
                    'is_correct' => $opt['is_correct'] ?? false,
                    'option_weight' => $opt['option_weight'] ?? null,
                    'sort_order' => $opt['sort_order'] ?? 0,
                ]);
                $result['options_created']++;
            }
        });

        $this->removeDirectory($tmpDir);
        return $result;
    }

    private function rewriteBodyPathsForRestore(string $body, string $tmpDir, array $assetMap, int $institutionId): string
    {
        if ($body === '') {
            return $body;
        }
        // Ganti {{ASSET:assets/xxx}} dengan path storage baru setelah kita upload file
        $pattern = '#{{ASSET:(assets/[^}}]+)}}#';
        $body = preg_replace_callback($pattern, function ($m) use ($tmpDir, $assetMap, $institutionId) {
            $zipRel = $m[1];
            $localFile = $tmpDir . '/' . $zipRel;
            if (!file_exists($localFile)) {
                return '';
            }
            $storagePath = 'question_assets/' . $institutionId . '/' . date('Y/m') . '/' . basename($zipRel);
            Storage::disk('public')->put($storagePath, file_get_contents($localFile));
            return '/storage/' . $storagePath;
        }, $body);

        return $body;
    }
}
