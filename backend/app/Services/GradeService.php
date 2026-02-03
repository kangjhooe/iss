<?php

namespace App\Services;

use App\Models\Grade;
use App\Models\SchoolClass;
use App\Models\Student;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class GradeService
{
    /** Bobot default: UH 20%, UTS 30%, UAS 30%, Tugas 20% */
    public const WEIGHT_UH = 0.20;
    public const WEIGHT_UTS = 0.30;
    public const WEIGHT_UAS = 0.30;
    public const WEIGHT_TUGAS = 0.20;

    /**
     * Hitung nilai akhir dari komponen (jika semua ada).
     */
    public static function computeNilaiAkhir(?float $uh, ?float $uts, ?float $uas, ?float $tugas): ?float
    {
        $vals = array_filter([$uh, $uts, $uas, $tugas], fn ($v) => $v !== null && $v !== '');
        if (count($vals) < 4) {
            return null;
        }
        $uh = (float) $uh;
        $uts = (float) $uts;
        $uas = (float) $uas;
        $tugas = (float) $tugas;
        $nilai = $uh * self::WEIGHT_UH + $uts * self::WEIGHT_UTS + $uas * self::WEIGHT_UAS + $tugas * self::WEIGHT_TUGAS;
        return round($nilai, 2);
    }

    /**
     * Get grades for a class + subject + semester (for buku nilai view).
     * Returns list of students with their grades keyed by type (uh, uts, uas, tugas, nilai_akhir).
     */
    public function getByClassSubjectSemester(int $institutionId, int $classId, int $subjectId, int $semesterId): array
    {
        $students = Student::where('class_id', $classId)
            ->where('institution_id', $institutionId)
            ->orderBy('name')
            ->get(['id', 'name', 'nis', 'nisn']);

        $grades = Grade::where('institution_id', $institutionId)
            ->where('class_id', $classId)
            ->where('subject_id', $subjectId)
            ->where('semester_id', $semesterId)
            ->get();

        $byStudent = $grades->groupBy('student_id');

        $rows = [];
        foreach ($students as $student) {
            $studentGrades = $byStudent->get($student->id, collect());
            $rows[] = [
                'student_id' => $student->id,
                'student' => [
                    'id' => $student->id,
                    'name' => $student->name,
                    'nis' => $student->nis,
                    'nisn' => $student->nisn,
                ],
                'uh' => $this->findGradeValue($studentGrades, Grade::TYPE_UH),
                'uts' => $this->findGradeValue($studentGrades, Grade::TYPE_UTS),
                'uas' => $this->findGradeValue($studentGrades, Grade::TYPE_UAS),
                'tugas' => $this->findGradeValue($studentGrades, Grade::TYPE_TUGAS),
                'nilai_akhir' => $this->findGradeValue($studentGrades, Grade::TYPE_NILAI_AKHIR),
            ];
        }

        return $rows;
    }

    private function findGradeValue(Collection $studentGrades, string $type): ?float
    {
        $g = $studentGrades->firstWhere('grade_type', $type);
        return $g && $g->value !== null ? (float) $g->value : null;
    }

    /**
     * Bulk upsert grades for a class + subject + semester.
     * grades array: [ ['student_id' => 1, 'uh' => 80, 'uts' => 75, ... ], ... ]
     */
    public function bulkUpsert(int $institutionId, int $semesterId, int $classId, int $subjectId, array $grades, ?int $employeeId = null): int
    {
        $academicYearId = \App\Models\Semester::find($semesterId)?->academic_year_id;

        $count = 0;
        DB::transaction(function () use ($institutionId, $academicYearId, $semesterId, $classId, $subjectId, $grades, $employeeId, &$count) {
            $types = [Grade::TYPE_UH, Grade::TYPE_UTS, Grade::TYPE_UAS, Grade::TYPE_TUGAS, Grade::TYPE_NILAI_AKHIR];
            foreach ($grades as $row) {
                $studentId = (int) ($row['student_id'] ?? 0);
                if (!$studentId) {
                    continue;
                }
                foreach ($types as $type) {
                    $value = isset($row[$type]) && $row[$type] !== '' && $row[$type] !== null
                        ? (float) $row[$type]
                        : null;
                    if ($type === Grade::TYPE_NILAI_AKHIR && $value === null) {
                        $computed = self::computeNilaiAkhir(
                            isset($row[Grade::TYPE_UH]) && $row[Grade::TYPE_UH] !== '' && $row[Grade::TYPE_UH] !== null ? (float) $row[Grade::TYPE_UH] : null,
                            isset($row[Grade::TYPE_UTS]) && $row[Grade::TYPE_UTS] !== '' && $row[Grade::TYPE_UTS] !== null ? (float) $row[Grade::TYPE_UTS] : null,
                            isset($row[Grade::TYPE_UAS]) && $row[Grade::TYPE_UAS] !== '' && $row[Grade::TYPE_UAS] !== null ? (float) $row[Grade::TYPE_UAS] : null,
                            isset($row[Grade::TYPE_TUGAS]) && $row[Grade::TYPE_TUGAS] !== '' && $row[Grade::TYPE_TUGAS] !== null ? (float) $row[Grade::TYPE_TUGAS] : null,
                        );
                        if ($computed !== null) {
                            $value = $computed;
                        }
                    }
                    if ($value !== null && $value >= 0 && $value <= 100) {
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
                        $count++;
                    }
                }
            }
        });

        return $count;
    }

    /**
     * Get all grades for a student in a semester (raport per siswa).
     * Returns list of subjects with grades (uh, uts, uas, tugas, nilai_akhir).
     */
    public function getByStudentSemester(int $institutionId, int $studentId, int $semesterId): array
    {
        $grades = Grade::with(['subject:id,name,code'])
            ->where('institution_id', $institutionId)
            ->where('student_id', $studentId)
            ->where('semester_id', $semesterId)
            ->orderBy('subject_id')
            ->get();

        $bySubject = $grades->groupBy('subject_id');
        $subjectIds = $grades->pluck('subject_id')->unique()->sort()->values();
        $subjects = \App\Models\Subject::whereIn('id', $subjectIds)->get()->keyBy('id');

        $rows = [];
        foreach ($subjectIds as $sid) {
            $subject = $subjects->get($sid);
            $sg = $bySubject->get($sid, collect());
            $rows[] = [
                'subject_id' => $sid,
                'subject' => $subject ? ['id' => $subject->id, 'name' => $subject->name, 'code' => $subject->code] : null,
                'uh' => $this->findGradeValue($sg, Grade::TYPE_UH),
                'uts' => $this->findGradeValue($sg, Grade::TYPE_UTS),
                'uas' => $this->findGradeValue($sg, Grade::TYPE_UAS),
                'tugas' => $this->findGradeValue($sg, Grade::TYPE_TUGAS),
                'nilai_akhir' => $this->findGradeValue($sg, Grade::TYPE_NILAI_AKHIR),
            ];
        }

        return $rows;
    }

    /**
     * List grades with filters (for index/export).
     */
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

        return $query->paginate($perPage);
    }
}
