<?php

namespace App\Services;

use App\Models\PpdbApplicant;
use App\Models\Student;
use App\Support\StudentIdentity;
use Illuminate\Support\Facades\Log;

class PpdbAcceptedElsewhereService
{
    public const PUBLIC_NOTE = 'Pendaftaran ditutup karena sudah terdaftar sebagai siswa.';

    public const STAFF_NOTE_PREFIX = 'Terdeteksi otomatis: sudah menjadi siswa';

    private static ?int $exceptApplicantId = null;

    /**
     * @template T
     * @param  callable(): T  $callback
     * @return T
     */
    public function withExceptApplicant(?int $applicantId, callable $callback): mixed
    {
        $previous = self::$exceptApplicantId;
        self::$exceptApplicantId = $applicantId;
        try {
            return $callback();
        } finally {
            self::$exceptApplicantId = $previous;
        }
    }

    /**
     * Tandai pendaftar PPDB lain yang NISN/NIK-nya sama dengan siswa ini.
     */
    public function markForStudent(Student $student, ?int $exceptApplicantId = null): int
    {
        $exceptApplicantId = $exceptApplicantId ?? self::$exceptApplicantId;
        $nisn = trim((string) ($student->nisn ?? ''));
        $nik = trim((string) ($student->nik ?? ''));
        if ($nisn === '' && $nik === '') {
            return 0;
        }

        $student->loadMissing('institution:id,name');

        $query = PpdbApplicant::query()
            ->whereNotIn('status', PpdbApplicant::TERMINAL_STATUSES)
            ->where(function ($q) use ($student) {
                $q->whereNull('student_id')
                    ->orWhere('student_id', '!=', $student->id);
            })
            ->where(function ($q) use ($nisn, $nik) {
                if ($nisn !== '') {
                    $q->orWhere('nisn', $nisn);
                }
                if ($nik !== '') {
                    $q->orWhere('nik', $nik);
                }
            });

        if ($exceptApplicantId) {
            $query->where('id', '!=', $exceptApplicantId);
        }

        $count = 0;
        foreach ($query->get() as $applicant) {
            $this->markApplicant($applicant, $student);
            $count++;
        }

        if ($count > 0) {
            Log::info('PPDB applicants marked accepted elsewhere', [
                'student_id' => $student->id,
                'institution_id' => $student->institution_id,
                'marked' => $count,
            ]);
        }

        return $count;
    }

    public function findExistingStudent(?string $nisn, ?string $nik): ?Student
    {
        $nisn = trim((string) $nisn);
        $nik = trim((string) $nik);
        if ($nisn === '' && $nik === '') {
            return null;
        }

        if ($nisn !== '') {
            $byNisn = StudentIdentity::activeOccupant('nisn', $nisn);
            if ($byNisn) {
                return $byNisn;
            }
        }

        if ($nik !== '') {
            return StudentIdentity::activeOccupant('nik', $nik);
        }

        return null;
    }

    public function identityConflictMessage(?string $nisn, ?string $nik): ?string
    {
        $nisn = trim((string) $nisn);
        $nik = trim((string) $nik);

        if ($nisn !== '' && StudentIdentity::isTakenByActive('nisn', $nisn)) {
            return 'NISN sudah terdaftar sebagai siswa aktif. Periksa data siswa atau kosongkan NISN calon.';
        }

        if ($nik !== '' && StudentIdentity::isTakenByActive('nik', $nik)) {
            return 'NIK sudah terdaftar sebagai siswa aktif. Periksa data siswa atau kosongkan NIK calon.';
        }

        return null;
    }

    public function markApplicant(PpdbApplicant $applicant, Student $student): void
    {
        if (in_array($applicant->status, PpdbApplicant::TERMINAL_STATUSES, true)) {
            return;
        }

        $student->loadMissing('institution:id,name');
        $school = trim((string) ($student->institution?->name ?? ''));
        $staffNote = self::STAFF_NOTE_PREFIX.($school !== '' ? ' di '.$school : '').'.';
        $notes = trim((string) ($applicant->notes ?? ''));
        if (! str_contains($notes, self::STAFF_NOTE_PREFIX)) {
            $notes = $notes === '' ? $staffNote : $notes."\n".$staffNote;
        }

        $applicant->update([
            'status' => PpdbApplicant::STATUS_ACCEPTED_ELSEWHERE,
            'result_notes' => self::PUBLIC_NOTE,
            'notes' => $notes,
            'announcement_at' => $applicant->announcement_at ?? now(),
        ]);
    }
}
