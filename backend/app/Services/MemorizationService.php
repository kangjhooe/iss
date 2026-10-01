<?php

namespace App\Services;

use App\Models\Extracurricular;
use App\Models\ExtracurricularStudent;
use App\Models\MemorizationAyahProgress;
use App\Models\MemorizationDeposit;
use App\Models\MemorizationTarget;
use App\Models\QuranSurah;
use App\Models\Student;
use App\Support\StudentRosterSort;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MemorizationService
{
    /**
     * Expand target items into unique ayah keys "surah:ayah".
     *
     * @param  array<int, array{surah:int, from?:int, to?:int}>  $items
     * @return array<int, string>
     */
    public function expandItemsToAyahKeys(array $items): array
    {
        $surahCounts = QuranSurah::query()
            ->whereIn('number', collect($items)->pluck('surah')->unique()->filter()->all())
            ->pluck('ayah_count', 'number');

        $keys = [];
        foreach ($items as $item) {
            $surah = (int) ($item['surah'] ?? 0);
            if ($surah < 1 || $surah > 114) {
                continue;
            }
            $max = (int) ($surahCounts[$surah] ?? 0);
            if ($max < 1) {
                continue;
            }
            $from = isset($item['from']) ? max(1, (int) $item['from']) : 1;
            $to = isset($item['to']) ? min($max, (int) $item['to']) : $max;
            if ($from > $to) {
                [$from, $to] = [$to, $from];
            }
            for ($a = $from; $a <= $to; $a++) {
                $keys["{$surah}:{$a}"] = true;
            }
        }

        return array_keys($keys);
    }

    public function countTargetAyahs(array $items): int
    {
        return count($this->expandItemsToAyahKeys($items));
    }

    /**
     * Resolve effective target for a student (student > class > extracurricular).
     */
    public function resolveTargetForStudent(Extracurricular $extracurricular, Student $student, ?int $semesterId = null): ?MemorizationTarget
    {
        $base = MemorizationTarget::query()
            ->where('extracurricular_id', $extracurricular->id)
            ->when($semesterId, function ($q) use ($semesterId) {
                $q->where(function ($inner) use ($semesterId) {
                    $inner->whereNull('semester_id')->orWhere('semester_id', $semesterId);
                });
            });

        $studentTarget = (clone $base)
            ->where('scope', MemorizationTarget::SCOPE_STUDENT)
            ->where('student_id', $student->id)
            ->orderByDesc('semester_id')
            ->first();
        if ($studentTarget) {
            return $studentTarget;
        }

        $classId = $student->class_id ?? null;
        if ($classId) {
            $classTarget = (clone $base)
                ->where('scope', MemorizationTarget::SCOPE_CLASS)
                ->where('class_id', $classId)
                ->orderByDesc('semester_id')
                ->first();
            if ($classTarget) {
                return $classTarget;
            }
        }

        return (clone $base)
            ->where('scope', MemorizationTarget::SCOPE_EXTRACURRICULAR)
            ->whereNull('student_id')
            ->whereNull('class_id')
            ->orderByDesc('semester_id')
            ->first();
    }

    /**
     * @return array{target: ?MemorizationTarget, target_ayahs: int, deposited_ayahs: int, percent: float, surahs: array}
     */
    public function studentProgress(Extracurricular $extracurricular, Student $student, ?int $semesterId = null): array
    {
        $target = $this->resolveTargetForStudent($extracurricular, $student, $semesterId);
        $items = $target?->items ?? [];
        $targetKeys = $this->expandItemsToAyahKeys($items);
        $targetCount = count($targetKeys);

        $progressQuery = MemorizationAyahProgress::query()
            ->where('extracurricular_id', $extracurricular->id)
            ->where('student_id', $student->id);

        $depositedTotal = (clone $progressQuery)->count();

        $depositedInTarget = 0;
        if ($targetCount > 0) {
            $depositedSet = (clone $progressQuery)
                ->get(['surah_number', 'ayah_number'])
                ->map(fn ($r) => $r->surah_number . ':' . $r->ayah_number)
                ->flip();
            foreach ($targetKeys as $key) {
                if ($depositedSet->has($key)) {
                    $depositedInTarget++;
                }
            }
        }

        $percent = $targetCount > 0
            ? round(($depositedInTarget / $targetCount) * 100, 1)
            : 0.0;

        $bySurah = MemorizationAyahProgress::query()
            ->where('extracurricular_id', $extracurricular->id)
            ->where('student_id', $student->id)
            ->selectRaw('surah_number, COUNT(*) as deposited_count')
            ->groupBy('surah_number')
            ->pluck('deposited_count', 'surah_number');

        $surahMeta = QuranSurah::query()
            ->orderBy('number')
            ->get(['number', 'name_id', 'name_latin', 'ayah_count'])
            ->keyBy('number');

        $targetSurahNumbers = collect($items)->pluck('surah')->unique()->filter()->map(fn ($n) => (int) $n)->values();
        $surahs = $targetSurahNumbers->map(function (int $num) use ($surahMeta, $bySurah, $items) {
            $meta = $surahMeta->get($num);
            $ayahCount = (int) ($meta?->ayah_count ?? 0);
            $item = collect($items)->first(fn ($i) => (int) ($i['surah'] ?? 0) === $num) ?? ['surah' => $num];
            $from = isset($item['from']) ? (int) $item['from'] : 1;
            $to = isset($item['to']) ? (int) $item['to'] : $ayahCount;
            $targetAyahs = max(0, $to - $from + 1);
            $deposited = (int) ($bySurah[$num] ?? 0);

            return [
                'surah_number' => $num,
                'name_id' => $meta?->name_id,
                'name_latin' => $meta?->name_latin,
                'ayah_count' => $ayahCount,
                'target_from' => $from,
                'target_to' => $to,
                'target_ayahs' => $targetAyahs,
                'deposited_ayahs' => min($deposited, $targetAyahs ?: $deposited),
            ];
        })->all();

        return [
            'target' => $target,
            'target_ayahs' => $targetCount,
            'deposited_ayahs' => $targetCount > 0 ? $depositedInTarget : $depositedTotal,
            'deposited_total' => $depositedTotal,
            'percent' => $percent,
            'surahs' => $surahs,
        ];
    }

    /**
     * Roster progress for all active participants.
     */
    public function rosterProgress(Extracurricular $extracurricular, ?int $semesterId = null): Collection
    {
        $enrollments = ExtracurricularStudent::query()
            ->where('extracurricular_id', $extracurricular->id)
            ->where('status', 'aktif')
            ->when($semesterId, fn ($q) => $q->where('semester_id', $semesterId))
            ->with(['student.class'])
            ->get();

        $students = $enrollments->map(fn ($en) => $en->student)->filter()->values();
        $sorted = $students->sort(fn (Student $a, Student $b) => StudentRosterSort::compareStudents($a, $b))->values();

        return $sorted->map(function (Student $student) use ($extracurricular, $semesterId) {
            $progress = $this->studentProgress($extracurricular, $student, $semesterId);

            return [
                'student' => [
                    'id' => $student->id,
                    'name' => $student->name,
                    'nis' => $student->nis,
                    'class' => StudentRosterSort::classArray($student),
                ],
                'target_id' => $progress['target']?->id,
                'target_name' => $progress['target']?->name,
                'target_ayahs' => $progress['target_ayahs'],
                'deposited_ayahs' => $progress['deposited_ayahs'],
                'percent' => $progress['percent'],
            ];
        })->values();
    }

    /**
     * @param  array{student_id:int, surah_number:int, ayah_from:int, ayah_to:int, quality?:?string, notes?:?string, session_id?:?int, deposited_at?:?string}  $data
     */
    public function recordDeposit(Extracurricular $extracurricular, array $data, ?int $recordedBy): MemorizationDeposit
    {
        $surah = QuranSurah::find($data['surah_number']);
        if (!$surah) {
            throw ValidationException::withMessages([
                'surah_number' => ['Surat tidak ditemukan. Jalankan seeder Al-Qur\'an terlebih dahulu.'],
            ]);
        }

        $from = (int) $data['ayah_from'];
        $to = (int) $data['ayah_to'];
        if ($from < 1 || $to < 1 || $from > $surah->ayah_count || $to > $surah->ayah_count) {
            throw ValidationException::withMessages([
                'ayah_from' => ["Rentang ayat harus antara 1–{$surah->ayah_count}."],
            ]);
        }
        if ($from > $to) {
            [$from, $to] = [$to, $from];
        }

        return DB::transaction(function () use ($extracurricular, $data, $recordedBy, $from, $to) {
            $deposit = MemorizationDeposit::create([
                'institution_id' => $extracurricular->institution_id,
                'extracurricular_id' => $extracurricular->id,
                'student_id' => $data['student_id'],
                'session_id' => $data['session_id'] ?? null,
                'surah_number' => $data['surah_number'],
                'ayah_from' => $from,
                'ayah_to' => $to,
                'quality' => $data['quality'] ?? null,
                'notes' => $data['notes'] ?? null,
                'recorded_by' => $recordedBy,
                'deposited_at' => $data['deposited_at'] ?? now(),
            ]);

            $now = $deposit->deposited_at;
            for ($ayah = $from; $ayah <= $to; $ayah++) {
                MemorizationAyahProgress::updateOrCreate(
                    [
                        'extracurricular_id' => $extracurricular->id,
                        'student_id' => $data['student_id'],
                        'surah_number' => $data['surah_number'],
                        'ayah_number' => $ayah,
                    ],
                    [
                        'status' => MemorizationAyahProgress::STATUS_DEPOSITED,
                        'last_deposit_id' => $deposit->id,
                        'deposited_at' => $now,
                    ]
                );
            }

            return $deposit->load(['surah', 'recorder:id,name']);
        });
    }

    /**
     * Toggle / set checklist of ayahs for a student on one surah.
     *
     * @param  array<int, int>  $ayahNumbers  ayah numbers that should be deposited
     * @param  bool  $replace  if true, uncheck ayahs in range not in list
     */
    public function syncSurahChecklist(
        Extracurricular $extracurricular,
        int $studentId,
        int $surahNumber,
        array $ayahNumbers,
        ?int $recordedBy,
        ?string $quality = null,
        bool $replace = false,
        ?int $ayahFrom = null,
        ?int $ayahTo = null
    ): array {
        $surah = QuranSurah::find($surahNumber);
        if (!$surah) {
            throw ValidationException::withMessages([
                'surah_number' => ['Surat tidak ditemukan.'],
            ]);
        }

        $ayahNumbers = collect($ayahNumbers)
            ->map(fn ($n) => (int) $n)
            ->filter(fn ($n) => $n >= 1 && $n <= $surah->ayah_count)
            ->unique()
            ->sort()
            ->values()
            ->all();

        return DB::transaction(function () use (
            $extracurricular,
            $studentId,
            $surahNumber,
            $ayahNumbers,
            $recordedBy,
            $quality,
            $replace,
            $ayahFrom,
            $ayahTo,
            $surah
        ) {
            $existing = MemorizationAyahProgress::query()
                ->where('extracurricular_id', $extracurricular->id)
                ->where('student_id', $studentId)
                ->where('surah_number', $surahNumber)
                ->pluck('ayah_number')
                ->map(fn ($n) => (int) $n)
                ->all();

            $toAdd = array_values(array_diff($ayahNumbers, $existing));

            if ($toAdd) {
                // Group contiguous ranges for fewer deposit rows
                sort($toAdd);
                $ranges = $this->groupContiguous($toAdd);
                foreach ($ranges as [$from, $to]) {
                    $this->recordDeposit($extracurricular, [
                        'student_id' => $studentId,
                        'surah_number' => $surahNumber,
                        'ayah_from' => $from,
                        'ayah_to' => $to,
                        'quality' => $quality,
                    ], $recordedBy);
                }
            }

            if ($replace) {
                $from = $ayahFrom ?? 1;
                $to = $ayahTo ?? $surah->ayah_count;
                $wanted = array_flip($ayahNumbers);
                MemorizationAyahProgress::query()
                    ->where('extracurricular_id', $extracurricular->id)
                    ->where('student_id', $studentId)
                    ->where('surah_number', $surahNumber)
                    ->whereBetween('ayah_number', [$from, $to])
                    ->get()
                    ->each(function (MemorizationAyahProgress $row) use ($wanted) {
                        if (!isset($wanted[$row->ayah_number])) {
                            $row->delete();
                        }
                    });
            }

            return $this->surahChecklist($extracurricular, $studentId, $surahNumber);
        });
    }

    /**
     * @param  array<int, int>  $numbers
     * @return array<int, array{0:int,1:int}>
     */
    private function groupContiguous(array $numbers): array
    {
        if (!$numbers) {
            return [];
        }
        $ranges = [];
        $start = $numbers[0];
        $prev = $numbers[0];
        for ($i = 1; $i < count($numbers); $i++) {
            if ($numbers[$i] === $prev + 1) {
                $prev = $numbers[$i];
                continue;
            }
            $ranges[] = [$start, $prev];
            $start = $numbers[$i];
            $prev = $numbers[$i];
        }
        $ranges[] = [$start, $prev];

        return $ranges;
    }

    public function surahChecklist(Extracurricular $extracurricular, int $studentId, int $surahNumber): array
    {
        $surah = QuranSurah::findOrFail($surahNumber);
        $deposited = MemorizationAyahProgress::query()
            ->where('extracurricular_id', $extracurricular->id)
            ->where('student_id', $studentId)
            ->where('surah_number', $surahNumber)
            ->pluck('ayah_number')
            ->map(fn ($n) => (int) $n)
            ->all();

        $ayahTexts = $surah->ayahs()
            ->orderBy('ayah_number')
            ->get(['ayah_number', 'text_ar', 'text_id'])
            ->keyBy('ayah_number');

        $ayahs = [];
        for ($i = 1; $i <= $surah->ayah_count; $i++) {
            $text = $ayahTexts->get($i);
            $ayahs[] = [
                'ayah_number' => $i,
                'deposited' => in_array($i, $deposited, true),
                'text_ar' => $text?->text_ar,
                'text_id' => $text?->text_id,
            ];
        }

        return [
            'surah' => [
                'number' => $surah->number,
                'name_ar' => $surah->name_ar,
                'name_id' => $surah->name_id,
                'name_latin' => $surah->name_latin,
                'ayah_count' => $surah->ayah_count,
            ],
            'deposited_count' => count($deposited),
            'ayahs' => $ayahs,
        ];
    }

    /**
     * Template items for Juz 30 (surah 78–114).
     *
     * @return array<int, array{surah:int}>
     */
    public static function juz30Items(): array
    {
        $items = [];
        for ($n = 78; $n <= 114; $n++) {
            $items[] = ['surah' => $n];
        }

        return $items;
    }
}
