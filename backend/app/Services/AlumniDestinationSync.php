<?php

namespace App\Services;

use App\Models\AlumniDestination;
use App\Models\Student;
use App\Models\User;
use App\Notifications\AlumniDestinationNotification;
use App\Support\SafeNotify;
use Illuminate\Support\Facades\Log;
use Throwable;

class AlumniDestinationSync
{
    /**
     * Jika siswa baru memakai NIK/NISN alumni di sekolah lain,
     * ajukan destinasi "Lanjut Sekolah" untuk disetujui admin sekolah asal.
     */
    public function proposeFromEnrollment(Student $enrolled): void
    {
        try {
            $this->proposeFromEnrollmentInternal($enrolled);
        } catch (Throwable $e) {
            Log::warning('Gagal mengajukan destinasi alumni otomatis', [
                'student_id' => $enrolled->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function proposeFromEnrollmentInternal(Student $enrolled): void
    {
        if ((string) $enrolled->status === 'Lulus') {
            return;
        }

        $nisn = trim((string) ($enrolled->nisn ?? ''));
        $nik = trim((string) ($enrolled->nik ?? ''));
        if ($nisn === '' && $nik === '') {
            return;
        }

        $enrolled->loadMissing('institution:id,name,level');
        $schoolName = trim((string) ($enrolled->institution?->name ?? ''));
        if ($schoolName === '') {
            return;
        }

        $alumni = Student::query()
            ->where('status', 'Lulus')
            ->where('id', '!=', $enrolled->id)
            ->where('institution_id', '!=', $enrolled->institution_id)
            ->where(function ($q) use ($nisn, $nik) {
                if ($nisn !== '') {
                    $q->orWhere('nisn', $nisn);
                }
                if ($nik !== '') {
                    $q->orWhere('nik', $nik);
                }
            })
            ->get();

        foreach ($alumni as $alum) {
            $this->createPendingIfMissing($alum, $enrolled, $schoolName);
        }
    }

    private function createPendingIfMissing(Student $alum, Student $enrolled, string $schoolName): void
    {
        $already = AlumniDestination::query()
            ->where('student_id', $alum->id)
            ->where(function ($q) use ($enrolled) {
                $q->where('related_student_id', $enrolled->id);
                if ($enrolled->institution_id) {
                    $q->orWhere('related_institution_id', $enrolled->institution_id);
                }
            })
            ->exists();

        if ($already) {
            return;
        }

        $program = $enrolled->class ?: ($enrolled->tingkat ? 'Tingkat '.$enrolled->tingkat : null);

        $destination = AlumniDestination::create([
            'institution_id' => (int) $alum->institution_id,
            'student_id' => $alum->id,
            'destination_type' => 'Sekolah',
            'destination_name' => $schoolName,
            'program_or_position' => $program,
            'year_entered' => (int) date('Y'),
            'notes' => 'Terdeteksi otomatis karena siswa terdaftar di '.$schoolName.'. Menunggu persetujuan admin.',
            'status' => AlumniDestination::STATUS_PENDING,
            'source' => AlumniDestination::SOURCE_AUTO_ENROLLMENT,
            'related_student_id' => $enrolled->id,
            'related_institution_id' => $enrolled->institution_id ? (int) $enrolled->institution_id : null,
        ]);

        $destination->setRelation('student', $alum);
        $this->notifyOriginAdmins($destination, $alum, $schoolName);
    }

    private function notifyOriginAdmins(AlumniDestination $destination, Student $alum, string $schoolName): void
    {
        $users = User::query()
            ->where('institution_id', $alum->institution_id)
            ->whereIn('role', ['admin', 'institution_admin'])
            ->get();

        foreach ($users as $user) {
            SafeNotify::send($user, new AlumniDestinationNotification($destination, 'proposed', $alum->name, $schoolName));
        }
    }

    public function notifyDecision(AlumniDestination $destination, string $action): void
    {
        $destination->loadMissing(['student:id,name,institution_id', 'relatedInstitution:id,name']);
        $studentName = $destination->student?->name ?: 'Alumni';
        $schoolName = $destination->relatedInstitution?->name ?: $destination->destination_name;

        $users = User::query()
            ->where('institution_id', $destination->institution_id)
            ->whereIn('role', ['admin', 'institution_admin'])
            ->get();

        foreach ($users as $user) {
            SafeNotify::send($user, new AlumniDestinationNotification($destination, $action, $studentName, $schoolName));
        }
    }
}
