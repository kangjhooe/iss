<?php

namespace App\Services;

use App\Models\Student;
use App\Models\StudentAttendance;
use App\Models\TeachingJournal;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class StudentAttendanceService
{
    /**
     * List attendances for a teaching journal (one per student in that class).
     */
    public function listByTeachingJournal(int $teachingJournalId, int $institutionId): Collection
    {
        $journal = TeachingJournal::forInstitution($institutionId)->findOrFail($teachingJournalId);
        $existing = StudentAttendance::forTeachingJournal($teachingJournalId)
            ->with('student:id,nis,nisn,name,gender')
            ->get()
            ->keyBy('student_id');

        $students = Student::where('class_id', $journal->class_id)
            ->where('institution_id', $institutionId)
            ->active()
            ->orderBy('name')
            ->get(['id', 'nis', 'nisn', 'name', 'gender']);

        $result = $students->map(function (Student $student) use ($existing) {
            $att = $existing->get($student->id);
            return [
                'student_id' => $student->id,
                'student' => [
                    'id' => $student->id,
                    'nis' => $student->nis,
                    'nisn' => $student->nisn,
                    'name' => $student->name,
                    'gender' => $student->gender,
                ],
                'attendance_id' => $att?->id,
                'status' => $att?->status ?? 'hadir',
                'notes' => $att?->notes,
            ];
        });

        return $result;
    }

    /**
     * Upsert attendances for a teaching journal (bulk).
     * Validates that each student belongs to the journal's class.
     */
    public function upsertForJournal(int $teachingJournalId, int $institutionId, array $attendances): Collection
    {
        $journal = TeachingJournal::forInstitution($institutionId)->findOrFail($teachingJournalId);
        $studentIdsInClass = Student::where('class_id', $journal->class_id)
            ->where('institution_id', $institutionId)
            ->active()
            ->pluck('id')
            ->flip();

        return DB::transaction(function () use ($journal, $institutionId, $attendances, $studentIdsInClass) {
            $saved = collect();
            foreach ($attendances as $row) {
                $studentId = (int) ($row['student_id'] ?? 0);
                if (!$studentIdsInClass->has($studentId)) {
                    continue;
                }
                $status = $row['status'] ?? 'hadir';
                $notes = $row['notes'] ?? null;

                $att = StudentAttendance::updateOrCreate(
                    [
                        'teaching_journal_id' => $journal->id,
                        'student_id' => $studentId,
                    ],
                    [
                        'institution_id' => $institutionId,
                        'status' => $status,
                        'notes' => $notes,
                    ]
                );
                $saved->push($att);
            }
            return $saved->load('student:id,nis,nisn,name,gender');
        });
    }

    /**
     * Update a single student attendance record.
     */
    public function update(StudentAttendance $attendance, array $data): StudentAttendance
    {
        $attendance->update($data);
        return $attendance->fresh(['student:id,nis,nisn,name,gender']);
    }
}
