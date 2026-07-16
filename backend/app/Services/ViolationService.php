<?php

namespace App\Services;

use App\Models\PiketIncident;
use App\Models\Student;
use App\Models\User;
use App\Models\Violation;
use App\Models\ViolationType;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;

class ViolationService
{
    /**
     * BK / admin / pemegang modul violation boleh setujui langsung.
     */
    public function canDirectApprove(User $user): bool
    {
        if ($user->isAdminOrSuperAdmin() || $user->isInstitutionAdmin()) {
            return true;
        }

        return $user->hasModuleAccess('violation');
    }

    /**
     * List violations for institution with filters.
     */
    public function listForInstitution(int $institutionId, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Violation::with([
            'student' => fn ($q) => $q->select('id', 'name', 'nis', 'nisn', 'class_id')->with('class:id,name'),
            'violationType:id,name,code,category,point_weight,default_sanction',
            'reporter:id,name,email',
            'reviewer:id,name',
            'piketIncident:id,incident_date,incident_type,minutes_late,description,status',
        ])
            ->forInstitution($institutionId)
            ->orderByRaw("CASE WHEN status = 'pending' THEN 0 ELSE 1 END")
            ->orderBy('violation_date', 'desc')
            ->orderBy('created_at', 'desc');

        if (!empty($filters['student_id'])) {
            $query->where('student_id', $filters['student_id']);
        }
        if (!empty($filters['violation_type_id'])) {
            $query->where('violation_type_id', $filters['violation_type_id']);
        }
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (!empty($filters['date_from'])) {
            $query->whereDate('violation_date', '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $query->whereDate('violation_date', '<=', $filters['date_to']);
        }
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->whereHas('student', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('nis', 'like', "%{$search}%")
                    ->orWhere('nisn', 'like', "%{$search}%");
            });
        }
        if (!empty($filters['academic_year_id'])) {
            $query->where('academic_year_id', $filters['academic_year_id']);
        }
        if (!empty($filters['semester_id'])) {
            $query->where('semester_id', $filters['semester_id']);
        }
        if (!empty($filters['from_piket'])) {
            $query->whereNotNull('piket_incident_id');
        }

        return $query->paginate($perPage);
    }

    /**
     * Get violations by student.
     */
    public function listByStudent(int $studentId, int $institutionId): LengthAwarePaginator
    {
        return Violation::with([
            'violationType:id,name,code,category,point_weight',
            'reporter:id,name',
            'reviewer:id,name',
        ])
            ->forInstitution($institutionId)
            ->forStudent($studentId)
            ->where('status', '!=', Violation::STATUS_PENDING)
            ->orderBy('violation_date', 'desc')
            ->paginate(20);
    }

    /**
     * Create violation (BK/admin: langsung dicatat; atau force_pending).
     */
    public function create(int $institutionId, array $data, int $reportedBy, bool $asPending = false): Violation
    {
        $student = Student::where('id', $data['student_id'])
            ->where('institution_id', $institutionId)
            ->firstOrFail();

        $violationType = ViolationType::where('id', $data['violation_type_id'])
            ->where('institution_id', $institutionId)
            ->where('is_active', true)
            ->firstOrFail();

        $institution = \App\Models\Institution::find($institutionId);
        $academicYearId = $student->academic_year_id ?: $institution?->active_academic_year_id;
        $semesterId = $student->semester_id ?: $institution?->active_semester_id;

        $status = $asPending ? Violation::STATUS_PENDING : Violation::STATUS_DICATAT;

        $violation = Violation::create([
            'institution_id' => $institutionId,
            'student_id' => $student->id,
            'violation_type_id' => $violationType->id,
            'reported_by' => $reportedBy,
            'reviewed_by' => $asPending ? null : $reportedBy,
            'reviewed_at' => $asPending ? null : now(),
            'violation_date' => $data['violation_date'],
            'sanction' => $data['sanction'] ?? $violationType->default_sanction,
            'status' => $status,
            'description' => $data['description'] ?? null,
            'academic_year_id' => $academicYearId,
            'semester_id' => $semesterId,
            'class_id' => $student->class_id,
            'piket_incident_id' => $data['piket_incident_id'] ?? null,
        ]);

        return $violation->load(['student', 'violationType', 'reporter', 'reviewer', 'piketIncident']);
    }

    /**
     * Guru piket mengusulkan pelanggaran dari insiden (status pending).
     */
    public function proposeFromPiketIncident(
        PiketIncident $incident,
        array $data,
        User $reporter
    ): Violation {
        if ((int) $incident->institution_id !== (int) $reporter->institution_id
            && !$reporter->isSuperAdmin()) {
            throw ValidationException::withMessages([
                'incident' => ['Insiden tidak berada di institusi Anda.'],
            ]);
        }

        $studentId = $data['student_id'] ?? $incident->student_id;
        if (!$studentId) {
            throw ValidationException::withMessages([
                'student_id' => ['Insiden harus terkait siswa untuk diajukan ke BK.'],
            ]);
        }

        $existing = Violation::where('piket_incident_id', $incident->id)
            ->whereIn('status', [Violation::STATUS_PENDING, ...Violation::STATUSES_COUNTING_POINTS])
            ->first();

        if ($existing) {
            throw ValidationException::withMessages([
                'piket_incident_id' => ['Insiden ini sudah diajukan ke BK.'],
            ]);
        }

        // Hapus usulan ditolak sebelumnya agar bisa diajukan ulang
        Violation::where('piket_incident_id', $incident->id)
            ->where('status', Violation::STATUS_DITOLAK)
            ->update(['piket_incident_id' => null]);

        $description = $data['description'] ?? $incident->description;
        if ($incident->minutes_late && (!$description || !str_contains((string) $description, 'menit'))) {
            $lateNote = sprintf('Terlambat %d menit.', $incident->minutes_late);
            $description = trim(($description ? $description.' ' : '').$lateNote);
        }

        $violation = $this->create(
            (int) $incident->institution_id,
            [
                'student_id' => $studentId,
                'violation_type_id' => $data['violation_type_id'],
                'violation_date' => $data['violation_date'] ?? $incident->incident_date?->format('Y-m-d'),
                'sanction' => $data['sanction'] ?? null,
                'description' => $description,
                'piket_incident_id' => $incident->id,
            ],
            $reporter->id,
            true
        );

        if (in_array($incident->status, [PiketIncident::STATUS_OPEN], true)) {
            $incident->update(['status' => PiketIncident::STATUS_CONFIRMED]);
        }

        return $violation;
    }

    /**
     * BK menyetujui usulan → status dicatat (poin masuk).
     */
    public function approve(Violation $violation, User $reviewer, array $data = []): Violation
    {
        if (!$violation->isPending()) {
            throw ValidationException::withMessages([
                'status' => ['Hanya usulan menunggu yang dapat disetujui.'],
            ]);
        }

        $payload = [
            'status' => Violation::STATUS_DICATAT,
            'reviewed_by' => $reviewer->id,
            'reviewed_at' => now(),
            'review_notes' => $data['review_notes'] ?? null,
        ];

        if (!empty($data['violation_type_id'])) {
            $type = ViolationType::where('id', $data['violation_type_id'])
                ->where('institution_id', $violation->institution_id)
                ->where('is_active', true)
                ->firstOrFail();
            $payload['violation_type_id'] = $type->id;
            if (empty($data['sanction']) && empty($violation->sanction)) {
                $payload['sanction'] = $type->default_sanction;
            }
        }

        if (array_key_exists('sanction', $data) && $data['sanction'] !== null) {
            $payload['sanction'] = $data['sanction'];
        }

        $violation->update($payload);

        if ($violation->piket_incident_id) {
            PiketIncident::where('id', $violation->piket_incident_id)
                ->whereIn('status', [PiketIncident::STATUS_OPEN, PiketIncident::STATUS_CONFIRMED])
                ->update([
                    'status' => PiketIncident::STATUS_RESOLVED,
                    'resolved_by' => $reviewer->id,
                    'resolved_at' => now(),
                ]);
        }

        return $violation->fresh([
            'student.class:id,name',
            'violationType',
            'reporter',
            'reviewer',
            'piketIncident',
        ]);
    }

    /**
     * BK menolak usulan → tidak masuk poin.
     */
    public function reject(Violation $violation, User $reviewer, string $reviewNotes): Violation
    {
        if (!$violation->isPending()) {
            throw ValidationException::withMessages([
                'status' => ['Hanya usulan menunggu yang dapat ditolak.'],
            ]);
        }

        $violation->update([
            'status' => Violation::STATUS_DITOLAK,
            'reviewed_by' => $reviewer->id,
            'reviewed_at' => now(),
            'review_notes' => $reviewNotes,
        ]);

        return $violation->fresh([
            'student.class:id,name',
            'violationType',
            'reporter',
            'reviewer',
            'piketIncident',
        ]);
    }

    /**
     * Update violation.
     */
    public function update(Violation $violation, array $data): Violation
    {
        if ($violation->isPending()) {
            throw ValidationException::withMessages([
                'status' => ['Usulan menunggu harus disetujui atau ditolak terlebih dahulu.'],
            ]);
        }

        if ($violation->status === Violation::STATUS_DITOLAK) {
            throw ValidationException::withMessages([
                'status' => ['Pelanggaran yang ditolak tidak dapat diubah.'],
            ]);
        }

        $violation->update($data);

        return $violation->fresh(['student', 'violationType', 'reporter', 'reviewer', 'piketIncident']);
    }

    /**
     * List violation types for institution.
     */
    public function listTypes(int $institutionId, bool $activeOnly = true): Collection
    {
        $query = ViolationType::forInstitution($institutionId)->orderBy('category')->orderBy('name');
        if ($activeOnly) {
            $query->active();
        }

        return $query->get();
    }

    public function countPending(int $institutionId): int
    {
        return Violation::forInstitution($institutionId)->pending()->count();
    }
}
