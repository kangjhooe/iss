<?php

namespace App\Services;

use App\Models\Grade;
use App\Models\GradeWeight;
use App\Models\LessonSchedule;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\SubjectKkm;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class GradeService
{
    /**
     * Hitung rata-rata penilaian dari map index => value.
     *
     * @param  array<int|string, float|int|string|null>  $penilaian
     */
    public static function averagePenilaian(array $penilaian): ?float
    {
        $vals = [];
        foreach ($penilaian as $v) {
            if ($v === null || $v === '') {
                continue;
            }
            $n = (float) $v;
            if ($n >= 0 && $n <= 100) {
                $vals[] = $n;
            }
        }
        if ($vals === []) {
            return null;
        }

        return round(array_sum($vals) / count($vals), 2);
    }

    /**
     * Hitung nilai akhir dari rata penilaian + UTS + UAS dengan bobot persen.
     *
     * @param  array{weight_penilaian?:float|int,weight_uts?:float|int,weight_uas?:float|int}|null  $weights
     */
    public static function computeNilaiAkhir(
        ?float $rataPenilaian,
        ?float $uts,
        ?float $uas,
        ?array $weights = null
    ): ?float {
        $wp = (float) ($weights['weight_penilaian'] ?? GradeWeight::DEFAULT_WEIGHT_PENILAIAN);
        $wUts = (float) ($weights['weight_uts'] ?? GradeWeight::DEFAULT_WEIGHT_UTS);
        $wUas = (float) ($weights['weight_uas'] ?? GradeWeight::DEFAULT_WEIGHT_UAS);

        $parts = [];
        if ($wp > 0) {
            if ($rataPenilaian === null) {
                return null;
            }
            $parts[] = $rataPenilaian * ($wp / 100);
        }
        if ($wUts > 0) {
            if ($uts === null) {
                return null;
            }
            $parts[] = $uts * ($wUts / 100);
        }
        if ($wUas > 0) {
            if ($uas === null) {
                return null;
            }
            $parts[] = $uas * ($wUas / 100);
        }
        if ($parts === []) {
            return null;
        }

        return round(array_sum($parts), 2);
    }

    /**
     * @return array{data: array<int, array>, meta: array{current_page:int,last_page:int,per_page:int,total:int}, assessment_max: int}
     */
    public function getByClassSubjectSemester(
        int $institutionId,
        int $classId,
        int $subjectId,
        int $semesterId,
        int $page = 1,
        int $perPage = 20
    ): array {
        $page = max(1, $page);
        $perPage = max(1, min($perPage, 100));

        $query = Student::where('class_id', $classId)
            ->where('institution_id', $institutionId)
            ->orderBy('name');

        $total = (clone $query)->count();
        $lastPage = max(1, (int) ceil($total / $perPage));
        if ($page > $lastPage) {
            $page = $lastPage;
        }

        $students = $query
            ->forPage($page, $perPage)
            ->get(['id', 'name', 'nis', 'nisn']);

        $studentIds = $students->pluck('id')->all();

        $grades = $studentIds
            ? Grade::where('institution_id', $institutionId)
                ->where('class_id', $classId)
                ->where('subject_id', $subjectId)
                ->where('semester_id', $semesterId)
                ->whereIn('student_id', $studentIds)
                ->get()
            : collect();

        // Max penilaian index di seluruh kelas (bukan hanya halaman ini)
        $assessmentMax = $this->maxPenilaianIndex($institutionId, $classId, $subjectId, $semesterId);

        $byStudent = $grades->groupBy('student_id');

        $rankingScores = $this->nilaiAkhirScoresForClass($institutionId, $classId, $subjectId, $semesterId);
        $rankMap = self::computeRankMap($rankingScores);

        $rows = [];
        foreach ($students as $student) {
            $studentGrades = $byStudent->get($student->id, collect());
            $penilaian = $this->extractPenilaianMap($studentGrades);
            $rata = self::averagePenilaian($penilaian);
            $sid = (int) $student->id;
            $rows[] = [
                'student_id' => $sid,
                'student' => [
                    'id' => $sid,
                    'name' => $student->name,
                    'nis' => $student->nis,
                    'nisn' => $student->nisn,
                ],
                'penilaian' => (object) $penilaian,
                'rata_penilaian' => $rata,
                'uts' => $this->findGradeValue($studentGrades, Grade::TYPE_UTS),
                'uas' => $this->findGradeValue($studentGrades, Grade::TYPE_UAS),
                'nilai_akhir' => $this->findGradeValue($studentGrades, Grade::TYPE_NILAI_AKHIR),
                'rank' => $rankMap[$sid] ?? null,
            ];
        }

        return [
            'data' => $rows,
            'meta' => [
                'current_page' => $page,
                'last_page' => $lastPage,
                'per_page' => $perPage,
                'total' => $total,
            ],
            'assessment_max' => $assessmentMax,
            'ranking_scores' => $rankingScores,
        ];
    }

    /**
     * Nilai akhir semua siswa di kelas (untuk ranking).
     *
     * @return array<int, float|null> student_id => nilai_akhir
     */
    public function nilaiAkhirScoresForClass(
        int $institutionId,
        int $classId,
        int $subjectId,
        int $semesterId
    ): array {
        $studentIds = Student::where('class_id', $classId)
            ->where('institution_id', $institutionId)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $scores = array_fill_keys($studentIds, null);
        if ($studentIds === []) {
            return $scores;
        }

        $grades = Grade::where('institution_id', $institutionId)
            ->where('class_id', $classId)
            ->where('subject_id', $subjectId)
            ->where('semester_id', $semesterId)
            ->where('grade_type', Grade::TYPE_NILAI_AKHIR)
            ->whereIn('student_id', $studentIds)
            ->get(['student_id', 'value']);

        foreach ($grades as $g) {
            $scores[(int) $g->student_id] = $g->value !== null ? (float) $g->value : null;
        }

        return $scores;
    }

    /**
     * Competition ranking: 100,90,90,80 → 1,2,2,4. Null = tanpa peringkat.
     *
     * @param  array<int, float|null>  $scoresByStudentId
     * @return array<int, int|null>
     */
    public static function computeRankMap(array $scoresByStudentId): array
    {
        $ranked = [];
        foreach ($scoresByStudentId as $sid => $score) {
            if ($score === null || $score === '') {
                continue;
            }
            $ranked[(int) $sid] = (float) $score;
        }

        uasort($ranked, function ($a, $b) {
            if ($a === $b) {
                return 0;
            }

            return $a > $b ? -1 : 1;
        });

        $ranks = array_fill_keys(array_map('intval', array_keys($scoresByStudentId)), null);
        $position = 0;
        $prevScore = null;
        $prevRank = 0;
        foreach ($ranked as $sid => $score) {
            $position++;
            if ($prevScore !== null && abs($score - $prevScore) < 0.00001) {
                $ranks[(int) $sid] = $prevRank;
            } else {
                $ranks[(int) $sid] = $position;
                $prevRank = $position;
            }
            $prevScore = $score;
        }

        return $ranks;
    }

    public function maxPenilaianIndex(int $institutionId, int $classId, int $subjectId, int $semesterId): int
    {
        $types = Grade::where('institution_id', $institutionId)
            ->where('class_id', $classId)
            ->where('subject_id', $subjectId)
            ->where('semester_id', $semesterId)
            ->where('grade_type', 'like', 'penilaian_%')
            ->pluck('grade_type');

        $max = 0;
        foreach ($types as $type) {
            $idx = Grade::penilaianIndex((string) $type);
            if ($idx !== null && $idx > $max) {
                $max = $idx;
            }
        }

        return $max;
    }

    private function findGradeValue(Collection $studentGrades, string $type): ?float
    {
        $g = $studentGrades->firstWhere('grade_type', $type);

        return $g && $g->value !== null ? (float) $g->value : null;
    }

    /**
     * @return array<string, float> keyed by index as string "1","2",...
     */
    private function extractPenilaianMap(Collection $studentGrades): array
    {
        $map = [];
        foreach ($studentGrades as $g) {
            $idx = Grade::penilaianIndex((string) $g->grade_type);
            if ($idx === null || $g->value === null) {
                continue;
            }
            $map[(string) $idx] = (float) $g->value;
        }
        ksort($map, SORT_NUMERIC);

        return $map;
    }

    /**
     * Bulk upsert grades.
     * grades: [ ['student_id'=>1, 'penilaian'=>['1'=>80,'2'=>75], 'uts'=>..., 'uas'=>..., 'nilai_akhir'=>...], ... ]
     *
     * @param  array{weight_penilaian?:float,weight_uts?:float,weight_uas?:float}|null  $weights
     */
    public function bulkUpsert(
        int $institutionId,
        int $semesterId,
        int $classId,
        int $subjectId,
        array $grades,
        ?int $employeeId = null,
        ?array $weights = null
    ): int {
        $academicYearId = \App\Models\Semester::find($semesterId)?->academic_year_id;
        $weightPayload = $weights ?? GradeWeight::defaults();

        $count = 0;
        DB::transaction(function () use (
            $institutionId,
            $academicYearId,
            $semesterId,
            $classId,
            $subjectId,
            $grades,
            $employeeId,
            $weightPayload,
            &$count
        ) {
            foreach ($grades as $row) {
                $studentId = (int) ($row['student_id'] ?? 0);
                if (!$studentId) {
                    continue;
                }

                $penilaian = $row['penilaian'] ?? [];
                if (!is_array($penilaian)) {
                    $penilaian = [];
                }

                foreach ($penilaian as $idx => $raw) {
                    $n = (int) $idx;
                    if ($n < 1) {
                        continue;
                    }
                    $type = Grade::penilaianType($n);
                    $value = ($raw !== '' && $raw !== null) ? (float) $raw : null;
                    if ($value !== null && $value >= 0 && $value <= 100) {
                        $this->upsertOne(
                            $institutionId,
                            $academicYearId,
                            $semesterId,
                            $classId,
                            $subjectId,
                            $studentId,
                            $employeeId,
                            $type,
                            $value
                        );
                        $count++;
                    }
                }

                foreach ([Grade::TYPE_UTS, Grade::TYPE_UAS] as $type) {
                    $raw = $row[$type] ?? null;
                    $value = ($raw !== '' && $raw !== null) ? (float) $raw : null;
                    if ($value !== null && $value >= 0 && $value <= 100) {
                        $this->upsertOne(
                            $institutionId,
                            $academicYearId,
                            $semesterId,
                            $classId,
                            $subjectId,
                            $studentId,
                            $employeeId,
                            $type,
                            $value
                        );
                        $count++;
                    }
                }

                $rata = self::averagePenilaian($penilaian);
                $uts = isset($row[Grade::TYPE_UTS]) && $row[Grade::TYPE_UTS] !== '' && $row[Grade::TYPE_UTS] !== null
                    ? (float) $row[Grade::TYPE_UTS]
                    : null;
                $uas = isset($row[Grade::TYPE_UAS]) && $row[Grade::TYPE_UAS] !== '' && $row[Grade::TYPE_UAS] !== null
                    ? (float) $row[Grade::TYPE_UAS]
                    : null;

                $nilaiAkhir = isset($row[Grade::TYPE_NILAI_AKHIR]) && $row[Grade::TYPE_NILAI_AKHIR] !== '' && $row[Grade::TYPE_NILAI_AKHIR] !== null
                    ? (float) $row[Grade::TYPE_NILAI_AKHIR]
                    : null;
                if ($nilaiAkhir === null) {
                    $nilaiAkhir = self::computeNilaiAkhir($rata, $uts, $uas, $weightPayload);
                }

                if ($nilaiAkhir !== null && $nilaiAkhir >= 0 && $nilaiAkhir <= 100) {
                    $this->upsertOne(
                        $institutionId,
                        $academicYearId,
                        $semesterId,
                        $classId,
                        $subjectId,
                        $studentId,
                        $employeeId,
                        Grade::TYPE_NILAI_AKHIR,
                        $nilaiAkhir
                    );
                    $count++;
                }
            }
        });

        return $count;
    }

    private function upsertOne(
        int $institutionId,
        $academicYearId,
        int $semesterId,
        int $classId,
        int $subjectId,
        int $studentId,
        ?int $employeeId,
        string $type,
        float $value
    ): void {
        Grade::updateOrCreate(
            [
                'student_id' => $studentId,
                'subject_id' => $subjectId,
                'semester_id' => $semesterId,
                'grade_type' => $type,
            ],
            [
                'institution_id' => $institutionId,
                'academic_year_id' => $academicYearId,
                'class_id' => $classId,
                'employee_id' => $employeeId,
                'value' => $value,
            ]
        );
    }

    /**
     * Raport per siswa: penilaian (map), rata, uts, uas, nilai_akhir, KKM/predikat.
     */
    public function getByStudentSemester(int $institutionId, int $studentId, int $semesterId): array
    {
        $student = Student::query()
            ->where('id', $studentId)
            ->where('institution_id', $institutionId)
            ->first(['id', 'class_id']);

        $gradeLevel = $student?->class_id
            ? (int) SchoolClass::query()->where('id', $student->class_id)->value('grade')
            : 0;

        $grades = Grade::with(['subject:id,name,code'])
            ->where('institution_id', $institutionId)
            ->where('student_id', $studentId)
            ->where('semester_id', $semesterId)
            ->orderBy('subject_id')
            ->get();

        $bySubject = $grades->groupBy('subject_id');
        $subjectIds = $grades->pluck('subject_id')->unique()->sort()->values();
        $subjects = \App\Models\Subject::whereIn('id', $subjectIds)->get()->keyBy('id');

        $kkmBySubject = $this->kkmMapForGradeLevel(
            $institutionId,
            $semesterId,
            $gradeLevel,
            $subjectIds->all()
        );

        $rows = [];
        foreach ($subjectIds as $sid) {
            $subject = $subjects->get($sid);
            $sg = $bySubject->get($sid, collect());
            $penilaian = $this->extractPenilaianMap($sg);
            $nilaiAkhir = $this->findGradeValue($sg, Grade::TYPE_NILAI_AKHIR);
            $kkm = $kkmBySubject[(int) $sid] ?? null;
            $tuntas = SubjectKkm::isTuntas($nilaiAkhir, $kkm);

            $rows[] = [
                'subject_id' => $sid,
                'subject' => $subject ? ['id' => $subject->id, 'name' => $subject->name, 'code' => $subject->code] : null,
                'penilaian' => (object) $penilaian,
                'rata_penilaian' => self::averagePenilaian($penilaian),
                'uts' => $this->findGradeValue($sg, Grade::TYPE_UTS),
                'uas' => $this->findGradeValue($sg, Grade::TYPE_UAS),
                'nilai_akhir' => $nilaiAkhir,
                'kkm' => $kkm,
                'predicate' => SubjectKkm::predicateFromScore($nilaiAkhir, $kkm),
                'is_tuntas' => $tuntas,
                'tuntas_label' => $tuntas === null ? null : ($tuntas ? 'Tuntas' : 'Belum tuntas'),
            ];
        }

        return $rows;
    }

    /**
     * Rekap kelas: semua mapel (dari jadwal + nilai), rata-rata, ranking, KKM/predikat.
     *
     * @return array{subjects: array<int, array{id:int,name:?string,code:?string,kkm:?float}>, rows: array<int, array<string, mixed>>, grade:?int}
     */
    public function getByClassSemester(int $institutionId, int $classId, int $semesterId): array
    {
        $schoolClass = SchoolClass::query()
            ->where('id', $classId)
            ->where('institution_id', $institutionId)
            ->first(['id', 'grade']);
        $gradeLevel = $schoolClass ? (int) $schoolClass->grade : 0;

        $students = Student::where('class_id', $classId)
            ->where('institution_id', $institutionId)
            ->orderBy('name')
            ->get(['id', 'name', 'nis', 'nisn']);

        $grades = Grade::where('institution_id', $institutionId)
            ->where('class_id', $classId)
            ->where('semester_id', $semesterId)
            ->get();

        $scheduledSubjectIds = LessonSchedule::query()
            ->where('institution_id', $institutionId)
            ->where('class_id', $classId)
            ->where('semester_id', $semesterId)
            ->whereNotNull('subject_id')
            ->distinct()
            ->pluck('subject_id');

        $subjectIds = $scheduledSubjectIds
            ->merge($grades->pluck('subject_id'))
            ->unique()
            ->filter()
            ->values();

        $subjects = \App\Models\Subject::whereIn('id', $subjectIds)
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        $kkmBySubject = $this->kkmMapForGradeLevel(
            $institutionId,
            $semesterId,
            $gradeLevel,
            $subjects->pluck('id')->all()
        );

        $rows = [];
        $averageByStudent = [];
        foreach ($students as $student) {
            $bySubject = $grades->where('student_id', $student->id)->groupBy('subject_id');
            $subjectValues = [];
            $sum = 0.0;
            $count = 0;

            foreach ($subjects as $subject) {
                $sg = $bySubject->get($subject->id, collect());
                $nilai = $this->findGradeValue($sg, Grade::TYPE_NILAI_AKHIR);
                $kkm = $kkmBySubject[(int) $subject->id] ?? null;
                $tuntas = SubjectKkm::isTuntas($nilai, $kkm);
                $subjectValues[] = [
                    'subject_id' => (int) $subject->id,
                    'subject_name' => $subject->name,
                    'nilai_akhir' => $nilai,
                    'kkm' => $kkm,
                    'predicate' => SubjectKkm::predicateFromScore($nilai, $kkm),
                    'is_tuntas' => $tuntas,
                    'tuntas_label' => $tuntas === null ? null : ($tuntas ? 'Tuntas' : 'Belum tuntas'),
                ];
                if ($nilai !== null) {
                    $sum += $nilai;
                    $count++;
                }
            }

            $average = $count > 0 ? round($sum / $count, 2) : null;
            $averageByStudent[(int) $student->id] = $average;

            $rows[] = [
                'student_id' => (int) $student->id,
                'student' => [
                    'id' => (int) $student->id,
                    'name' => $student->name,
                    'nis' => $student->nis,
                    'nisn' => $student->nisn,
                ],
                'subjects' => $subjectValues,
                'average' => $average,
                'rank' => null,
            ];
        }

        $rankMap = self::computeRankMap($averageByStudent);
        foreach ($rows as &$row) {
            $row['rank'] = $rankMap[$row['student_id']] ?? null;
        }
        unset($row);

        usort($rows, function (array $a, array $b) {
            $rankA = $a['rank'];
            $rankB = $b['rank'];
            if ($rankA === null && $rankB === null) {
                return strcmp((string) ($a['student']['name'] ?? ''), (string) ($b['student']['name'] ?? ''));
            }
            if ($rankA === null) {
                return 1;
            }
            if ($rankB === null) {
                return -1;
            }
            if ($rankA === $rankB) {
                return strcmp((string) ($a['student']['name'] ?? ''), (string) ($b['student']['name'] ?? ''));
            }

            return $rankA <=> $rankB;
        });

        return [
            'grade' => $gradeLevel > 0 ? $gradeLevel : null,
            'subjects' => $subjects->map(fn ($s) => [
                'id' => (int) $s->id,
                'name' => $s->name,
                'code' => $s->code,
                'kkm' => $kkmBySubject[(int) $s->id] ?? null,
            ])->values()->all(),
            'rows' => $rows,
        ];
    }

    /**
     * Hitung ulang nilai_akhir semua siswa di kelas+mapel+semester menurut bobot saat ini.
     *
     * @return int jumlah siswa yang nilai akhirnya di-update
     */
    public function recalculateNilaiAkhirForPair(
        int $institutionId,
        int $classId,
        int $subjectId,
        int $semesterId,
        ?array $weights = null,
        ?int $employeeId = null
    ): int {
        $resolved = $weights ?? $this->resolveWeights($institutionId, $classId, $subjectId, $semesterId);
        $weightPayload = [
            'weight_penilaian' => (float) ($resolved['weight_penilaian'] ?? GradeWeight::DEFAULT_WEIGHT_PENILAIAN),
            'weight_uts' => (float) ($resolved['weight_uts'] ?? GradeWeight::DEFAULT_WEIGHT_UTS),
            'weight_uas' => (float) ($resolved['weight_uas'] ?? GradeWeight::DEFAULT_WEIGHT_UAS),
        ];

        $studentIds = Student::query()
            ->where('class_id', $classId)
            ->where('institution_id', $institutionId)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        if ($studentIds === []) {
            return 0;
        }

        $grades = Grade::query()
            ->where('institution_id', $institutionId)
            ->where('class_id', $classId)
            ->where('subject_id', $subjectId)
            ->where('semester_id', $semesterId)
            ->whereIn('student_id', $studentIds)
            ->get()
            ->groupBy('student_id');

        $payloads = [];
        foreach ($studentIds as $studentId) {
            $sg = $grades->get($studentId, collect());
            $penilaian = $this->extractPenilaianMap($sg);
            $uts = $this->findGradeValue($sg, Grade::TYPE_UTS);
            $uas = $this->findGradeValue($sg, Grade::TYPE_UAS);

            if ($penilaian === [] && $uts === null && $uas === null) {
                continue;
            }

            $nilaiAkhir = self::computeNilaiAkhir(
                self::averagePenilaian($penilaian),
                $uts,
                $uas,
                $weightPayload
            );
            if ($nilaiAkhir === null) {
                continue;
            }

            $payloads[] = [
                'student_id' => $studentId,
                'penilaian' => $penilaian,
                'uts' => $uts,
                'uas' => $uas,
            ];
        }

        if ($payloads === []) {
            return 0;
        }

        $this->bulkUpsert(
            $institutionId,
            $semesterId,
            $classId,
            $subjectId,
            $payloads,
            $employeeId,
            $weightPayload
        );

        return count($payloads);
    }

    /**
     * @param  array<int, int|string>  $subjectIds
     * @return array<int, float>
     */
    private function kkmMapForGradeLevel(
        int $institutionId,
        int $semesterId,
        int $gradeLevel,
        array $subjectIds
    ): array {
        if ($gradeLevel <= 0 || $subjectIds === []) {
            return [];
        }

        return SubjectKkm::query()
            ->where('institution_id', $institutionId)
            ->where('semester_id', $semesterId)
            ->where('grade', $gradeLevel)
            ->whereIn('subject_id', $subjectIds)
            ->get(['subject_id', 'kkm'])
            ->mapWithKeys(fn ($row) => [
                (int) $row->subject_id => $row->kkm !== null ? (float) $row->kkm : null,
            ])
            ->filter(fn ($kkm) => $kkm !== null)
            ->all();
    }

    public function listForInstitution(int $institutionId, array $filters = [], int $perPage = 50)
    {
        $query = Grade::with(['student:id,name,nis,nisn', 'subject:id,name,code', 'schoolClass:id,name', 'semester:id,name'])
            ->forInstitution($institutionId)
            ->orderBy('semester_id', 'desc')
            ->orderBy('class_id')
            ->orderBy('subject_id')
            ->orderBy('student_id')
            ->orderBy('grade_type');

        if (!empty($filters['semester_id'])) {
            $query->forSemester((int) $filters['semester_id']);
        }
        if (!empty($filters['class_id'])) {
            $query->forClass((int) $filters['class_id']);
        }
        if (!empty($filters['subject_id'])) {
            $query->forSubject((int) $filters['subject_id']);
        }
        if (!empty($filters['student_id'])) {
            $query->forStudent((int) $filters['student_id']);
        }

        if (!empty($filters['taught_by_employee_id'])) {
            $employeeId = (int) $filters['taught_by_employee_id'];
            $query->whereExists(function ($q) use ($employeeId, $institutionId) {
                $q->select(DB::raw(1))
                    ->from('lesson_schedules')
                    ->whereColumn('lesson_schedules.semester_id', 'grades.semester_id')
                    ->whereColumn('lesson_schedules.class_id', 'grades.class_id')
                    ->whereColumn('lesson_schedules.subject_id', 'grades.subject_id')
                    ->where('lesson_schedules.employee_id', $employeeId)
                    ->where('lesson_schedules.institution_id', $institutionId)
                    ->whereNull('lesson_schedules.deleted_at');
            });
        }

        return $query->paginate($perPage);
    }

    public function resolveWeights(int $institutionId, int $classId, int $subjectId, int $semesterId): array
    {
        $row = GradeWeight::query()
            ->where('institution_id', $institutionId)
            ->where('class_id', $classId)
            ->where('subject_id', $subjectId)
            ->where('semester_id', $semesterId)
            ->first();

        if ($row) {
            return [
                'weight_penilaian' => (float) $row->weight_penilaian,
                'weight_uts' => (float) $row->weight_uts,
                'weight_uas' => (float) $row->weight_uas,
                'assessment_count' => max(1, (int) $row->assessment_count),
                'deadline_penilaian' => optional($row->deadline_penilaian)?->format('Y-m-d'),
                'deadline_uts' => optional($row->deadline_uts)?->format('Y-m-d'),
                'deadline_uas' => optional($row->deadline_uas)?->format('Y-m-d'),
                'deadline_nilai_akhir' => optional($row->deadline_nilai_akhir)?->format('Y-m-d'),
                'set' => true,
                'model' => $row,
            ];
        }

        $defaults = GradeWeight::defaults();

        return [
            'weight_penilaian' => $defaults['weight_penilaian'],
            'weight_uts' => $defaults['weight_uts'],
            'weight_uas' => $defaults['weight_uas'],
            'assessment_count' => $defaults['assessment_count'],
            'deadline_penilaian' => null,
            'deadline_uts' => null,
            'deadline_uas' => null,
            'deadline_nilai_akhir' => null,
            'set' => false,
            'model' => null,
        ];
    }
}
