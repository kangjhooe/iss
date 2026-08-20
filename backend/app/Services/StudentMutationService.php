<?php

namespace App\Services;

use App\Models\Institution;
use App\Models\Student;
use App\Models\StudentMutation;
use App\Models\User;
use App\Notifications\StudentMutationNotification;
use App\Services\LocalNisService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class StudentMutationService
{
    /**
     * Lookup active student by NISN at the given institution (for mutation confirmation preview).
     *
     * @return array{id: int, nisn: string|null, nis: string|null, name: string, gender: string|null, status: string|null, tingkat: int|null, class_name: string|null}
     */
    public function lookupStudentByNisn(int $institutionId, string $nisn): array
    {
        $student = Student::with('class:id,name,grade')
            ->where('nisn', $nisn)
            ->where('institution_id', $institutionId)
            ->where('status', 'Aktif')
            ->first();

        if (!$student) {
            throw new \InvalidArgumentException('Siswa dengan NISN tersebut tidak ditemukan di sekolah Anda atau status tidak aktif.');
        }

        return $this->formatStudentLookup($student);
    }

    /**
     * Lookup active student at origin school by NPSN + NISN (for pull confirmation preview).
     *
     * @return array{id: int, nisn: string|null, nis: string|null, name: string, gender: string|null, status: string|null, tingkat: int|null, class_name: string|null, institution: array{id: int, name: string, npsn: string|null, level: string|null}}
     */
    public function lookupStudentAtOriginByNpsn(string $originNpsn, string $nisn, int $excludeInstitutionId): array
    {
        $origin = Institution::where('npsn', $originNpsn)->where('is_active', true)->first();
        if (!$origin) {
            throw new \InvalidArgumentException('Sekolah asal dengan NPSN tersebut tidak ditemukan atau tidak aktif.');
        }
        if ((int) $origin->id === (int) $excludeInstitutionId) {
            throw new \InvalidArgumentException('Sekolah asal harus berbeda dengan sekolah Anda.');
        }

        $target = Institution::find($excludeInstitutionId);
        if ($target && !$target->canMutateWith($origin)) {
            throw new \InvalidArgumentException('Mutasi hanya dapat dilakukan antar jenjang yang sama (SD-MI, SMP-MTs, SMA-MA-SMK-MAK, PAUD-TK).');
        }

        $student = Student::with('class:id,name,grade')
            ->where('nisn', $nisn)
            ->where('institution_id', $origin->id)
            ->where('status', 'Aktif')
            ->first();

        if (!$student) {
            throw new \InvalidArgumentException('Siswa dengan NISN tersebut tidak ditemukan di sekolah asal atau status tidak aktif.');
        }

        $data = $this->formatStudentLookup($student);
        $data['institution'] = [
            'id' => $origin->id,
            'name' => $origin->name,
            'npsn' => $origin->npsn,
            'level' => $origin->level,
        ];

        return $data;
    }

    /**
     * @return array{id: int, nisn: string|null, nis: string|null, name: string, gender: string|null, status: string|null, tingkat: int|null, class_name: string|null}
     */
    protected function formatStudentLookup(Student $student): array
    {
        $grade = $student->tingkat ?? $student->class?->grade;

        return [
            'id' => $student->id,
            'nisn' => $student->nisn,
            'nis' => $student->nis,
            'name' => $student->name,
            'gender' => $student->gender,
            'status' => $student->status,
            'tingkat' => $grade !== null ? (is_numeric($grade) ? (int) $grade : $grade) : null,
            'class_name' => $student->class?->name,
        ];
    }

    /**
     * Usulan mutasi keluar dari wali kelas (selalu pending, menunggu admin).
     *
     * @param  array{student_id:int, notes?:string, external?:bool, target_npsn?:string, target_school_name?:string}  $data
     */
    public function createProposalFromWali(int $originInstitutionId, array $data, int $requestedBy): StudentMutation
    {
        $student = Student::with('class')
            ->where('id', $data['student_id'])
            ->where('institution_id', $originInstitutionId)
            ->where('status', 'Aktif')
            ->firstOrFail();

        $external = !empty($data['external']);
        $targetNpsn = trim((string) ($data['target_npsn'] ?? ''));
        $targetSchoolName = trim((string) ($data['target_school_name'] ?? ''));
        $notes = $data['notes'] ?? null;

        if ($targetNpsn === '') {
            throw new \InvalidArgumentException('NPSN sekolah tujuan wajib diisi.');
        }

        $pendingExists = StudentMutation::query()
            ->where('student_id', $student->id)
            ->where('status', 'pending')
            ->exists();
        if ($pendingExists) {
            throw new \InvalidArgumentException('Siswa ini masih memiliki usulan mutasi yang menunggu persetujuan.');
        }

        if ($external) {
            if ($targetSchoolName === '') {
                throw new \InvalidArgumentException('Nama sekolah tujuan wajib diisi untuk mutasi eksternal.');
            }

            $mutation = StudentMutation::create([
                'origin_institution_id' => $originInstitutionId,
                'target_institution_id' => null,
                'target_npsn' => $targetNpsn,
                'target_school_name' => $targetSchoolName,
                'student_id' => $student->id,
                'student_grade' => $student->tingkat ?? $student->class?->grade,
                'student_gender' => $this->normalizeGender($student->gender),
                'initiated_by' => 'origin',
                'source' => 'wali',
                'requested_by' => $requestedBy,
                'status' => 'pending',
                'notes' => $notes,
            ]);

            $mutation->load(['originInstitution', 'student', 'requester']);
            $this->notifyInstitutionAdmins($originInstitutionId, $mutation, 'requested');

            return $mutation;
        }

        $target = Institution::where('npsn', $targetNpsn)->where('is_active', true)->first();
        if (!$target) {
            throw new \InvalidArgumentException('Sekolah tujuan dengan NPSN tersebut tidak ditemukan. Centang mutasi eksternal jika sekolah belum terdaftar.');
        }
        if ((int) $target->id === (int) $originInstitutionId) {
            throw new \InvalidArgumentException('Sekolah tujuan harus berbeda dengan sekolah asal.');
        }

        $origin = Institution::find($originInstitutionId);
        if ($origin && !$origin->canMutateWith($target)) {
            throw new \InvalidArgumentException('Mutasi hanya dapat dilakukan antar jenjang yang sama.');
        }

        $mutation = StudentMutation::create([
            'origin_institution_id' => $originInstitutionId,
            'target_institution_id' => $target->id,
            'student_id' => $student->id,
            'student_grade' => $student->tingkat ?? $student->class?->grade,
            'student_gender' => $this->normalizeGender($student->gender),
            'initiated_by' => 'origin',
            'source' => 'wali',
            'requested_by' => $requestedBy,
            'status' => 'pending',
            'notes' => $notes,
        ]);

        $mutation->load(['originInstitution', 'targetInstitution', 'student', 'requester']);
        $this->notifyInstitutionAdmins($target->id, $mutation, 'requested');
        $this->notifyInstitutionAdmins($originInstitutionId, $mutation, 'requested');

        return $mutation;
    }

    protected function normalizeGender(mixed $gender): string
    {
        if (is_string($gender)) {
            return preg_match('/^(L|l|Laki|Male)/i', $gender) ? 'L' : 'P';
        }

        return 'P';
    }

    /**
     * Pastikan siswa belum punya permohonan mutasi pending.
     */
    protected function assertNoPendingMutation(int $studentId): void
    {
        $pending = StudentMutation::where('student_id', $studentId)
            ->where('status', 'pending')
            ->exists();
        if ($pending) {
            throw new \InvalidArgumentException('Siswa ini masih memiliki permohonan mutasi yang menunggu persetujuan.');
        }
    }

    /**
     * Snapshot kelas/NIS sebelum dikosongkan saat mutasi disetujui.
     *
     * @return array{previous_class_id: int|null, previous_nis: string|null, previous_class_name: string|null}
     */
    protected function snapshotStudentPlacement(Student $student): array
    {
        $student->loadMissing('class');

        return [
            'previous_class_id' => $student->class_id ? (int) $student->class_id : null,
            'previous_nis' => $student->nis,
            'previous_class_name' => $student->class?->name ?? (is_string($student->class) ? $student->class : null),
        ];
    }

    protected function restoreStudentPlacement(Student $student, StudentMutation $mutation): void
    {
        if ($mutation->previous_class_id) {
            $student->class_id = $mutation->previous_class_id;
            $student->class = $mutation->previous_class_name;
        }
        if ($mutation->previous_nis !== null && $mutation->previous_nis !== '') {
            $student->nis = $mutation->previous_nis;
        }
    }

    /**
     * Create mutation request from origin school (admin asal: pilih NPSN tujuan + NISN siswa).
     */
    public function createFromOrigin(int $originInstitutionId, string $targetNpsn, string $nisn, int $requestedBy, ?string $notes = null): StudentMutation
    {
        $target = Institution::where('npsn', $targetNpsn)->where('is_active', true)->firstOrFail();
        if ((int) $target->id === (int) $originInstitutionId) {
            throw new \InvalidArgumentException('Sekolah tujuan harus berbeda dengan sekolah asal.');
        }

        $origin = Institution::find($originInstitutionId);
        if ($origin && !$origin->canMutateWith($target)) {
            throw new \InvalidArgumentException('Mutasi hanya dapat dilakukan antar jenjang yang sama.');
        }

        $student = Student::where('nisn', $nisn)
            ->where('institution_id', $originInstitutionId)
            ->where('status', 'Aktif')
            ->firstOrFail();

        $this->assertNoPendingMutation($student->id);

        $mutation = StudentMutation::create([
            'origin_institution_id' => $originInstitutionId,
            'target_institution_id' => $target->id,
            'student_id' => $student->id,
            'initiated_by' => 'origin',
            'source' => 'admin',
            'requested_by' => $requestedBy,
            'status' => 'pending',
            'notes' => $notes,
        ]);
        $mutation->load(['originInstitution', 'targetInstitution', 'student', 'requester']);
        $this->notifyInstitutionAdmins($target->id, $mutation, 'requested');
        return $mutation;
    }

    /**
     * Mutasi keluar ke sekolah yang belum terdaftar di aplikasi.
     * NPSN dan nama sekolah dicatat manual. Status langsung approved, siswa di-mark Pindah.
     */
    public function createFromOriginExternal(
        int $originInstitutionId,
        string $targetNpsn,
        string $targetSchoolName,
        string $nisn,
        int $requestedBy,
        ?string $notes = null
    ): StudentMutation {
        $student = Student::where('nisn', $nisn)
            ->where('institution_id', $originInstitutionId)
            ->where('status', 'Aktif')
            ->firstOrFail();

        $this->assertNoPendingMutation($student->id);

        DB::beginTransaction();
        try {
            $student->load('class');
            $grade = $student->tingkat ?? $student->class?->grade;
            $gender = $student->gender;
            if (is_string($gender)) {
                $gender = preg_match('/^(L|l|Laki|Male)/i', $gender) ? 'L' : 'P';
            } else {
                $gender = 'P';
            }
            $placement = $this->snapshotStudentPlacement($student);

            $mutation = StudentMutation::create([
                'origin_institution_id' => $originInstitutionId,
                'target_institution_id' => null,
                'target_npsn' => $targetNpsn,
                'target_school_name' => $targetSchoolName,
                'student_id' => $student->id,
                'student_grade' => $grade,
                'student_gender' => $gender,
                'previous_class_id' => $placement['previous_class_id'],
                'previous_nis' => $placement['previous_nis'],
                'previous_class_name' => $placement['previous_class_name'],
                'initiated_by' => 'origin',
                'source' => 'admin',
                'requested_by' => $requestedBy,
                'approved_by' => $requestedBy,
                'status' => 'approved',
                'approved_at' => now(),
                'notes' => $notes,
            ]);

            $student->status = 'Pindah';
            $student->class_id = null;
            $student->nis = null;
            $student->save();

            DB::commit();

            Log::info('Student mutation external (out of system) created', [
                'mutation_id' => $mutation->id,
                'student_id' => $student->id,
                'target_npsn' => $targetNpsn,
            ]);

            return $mutation->load(['originInstitution', 'student', 'requester', 'approver']);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Student mutation external create failed', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Create mutation request from target school (admin tujuan: tarik siswa dari sekolah asal).
     * initiated_by = 'target'.
     */
    public function createFromTarget(int $targetInstitutionId, string $originNpsn, string $nisn, int $requestedBy, ?string $notes = null): StudentMutation
    {
        $origin = Institution::where('npsn', $originNpsn)->where('is_active', true)->firstOrFail();
        if ((int) $origin->id === (int) $targetInstitutionId) {
            throw new \InvalidArgumentException('Sekolah asal harus berbeda dengan sekolah tujuan.');
        }

        $target = Institution::find($targetInstitutionId);
        if ($target && !$target->canMutateWith($origin)) {
            throw new \InvalidArgumentException('Mutasi hanya dapat dilakukan antar jenjang yang sama.');
        }

        $student = Student::where('nisn', $nisn)
            ->where('institution_id', $origin->id)
            ->where('status', 'Aktif')
            ->firstOrFail();

        $this->assertNoPendingMutation($student->id);

        $mutation = StudentMutation::create([
            'origin_institution_id' => $origin->id,
            'target_institution_id' => $targetInstitutionId,
            'student_id' => $student->id,
            'initiated_by' => 'target',
            'source' => 'admin',
            'requested_by' => $requestedBy,
            'status' => 'pending',
            'notes' => $notes,
        ]);
        $mutation->load(['originInstitution', 'targetInstitution', 'student', 'requester']);
        $this->notifyInstitutionAdmins($origin->id, $mutation, 'requested');
        return $mutation;
    }

    /**
     * Mutasi masuk dari sekolah yang belum terdaftar di aplikasi.
     * Buat data siswa baru di sekolah kita + catat mutasi (status approved).
     */
    public function createFromTargetExternal(
        int $targetInstitutionId,
        string $originNpsn,
        string $originSchoolName,
        string $studentName,
        string $studentNisn,
        string $studentGender,
        ?string $studentGrade,
        int $requestedBy,
        ?string $notes = null
    ): StudentMutation {
        $target = Institution::with('activeAcademicYear')->findOrFail($targetInstitutionId);

        if (Student::where('institution_id', $targetInstitutionId)->where('nisn', $studentNisn)->exists()) {
            throw new \InvalidArgumentException('NISN tersebut sudah digunakan oleh siswa lain di sekolah Anda.');
        }

        $gender = preg_match('/^(L|l|Laki|Male)/i', $studentGender) ? 'L' : 'P';

        DB::beginTransaction();
        try {
            $student = Student::create([
                'institution_id' => $targetInstitutionId,
                'nisn' => $studentNisn,
                'name' => $studentName,
                'gender' => $gender,
                'tingkat' => is_numeric($studentGrade) ? (int) $studentGrade : null,
                'status' => 'Aktif',
                'academic_year_id' => $target->active_academic_year_id,
                'academic_year' => $target->activeAcademicYear?->code ?: $target->activeAcademicYear?->name,
                'semester_id' => $target->active_semester_id,
            ]);
            try {
                app(LocalNisService::class)->assignIfEmpty($student);
            } catch (\InvalidArgumentException $e) {
                Log::warning('Local NIS not generated on external mutation in', [
                    'student_id' => $student->id,
                    'error' => $e->getMessage(),
                ]);
            }

            $mutation = StudentMutation::create([
                'origin_institution_id' => null,
                'origin_npsn' => $originNpsn,
                'origin_school_name' => $originSchoolName,
                'target_institution_id' => $targetInstitutionId,
                'student_id' => $student->id,
                'student_grade' => $studentGrade,
                'student_gender' => $gender,
                'initiated_by' => 'target',
                'source' => 'admin',
                'requested_by' => $requestedBy,
                'approved_by' => $requestedBy,
                'status' => 'approved',
                'approved_at' => now(),
                'notes' => $notes,
            ]);

            DB::commit();

            Log::info('Student mutation external origin (into system) created', [
                'mutation_id' => $mutation->id,
                'student_id' => $student->id,
                'origin_npsn' => $originNpsn,
            ]);

            return $mutation->load(['targetInstitution', 'student', 'requester', 'approver']);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Student mutation external origin create failed', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * List mutations for current user's institution (as origin or target).
     */
    public function listForInstitution(int $institutionId, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = StudentMutation::with([
            'originInstitution:id,name,npsn,level',
            'targetInstitution:id,name,npsn,level',
            'student:id,nisn,nis,name,gender,status',
            'requester:id,name,email',
            'approver:id,name',
            'cancelRequester:id,name',
        ])->orderBy('created_at', 'desc');

        $role = $filters['role'] ?? null;
        if ($role === 'as_origin') {
            $query->forOriginInstitution($institutionId);
        } elseif ($role === 'as_target') {
            $query->forTargetInstitution($institutionId);
        } else {
            $query->where(function ($q) use ($institutionId) {
                $q->where('origin_institution_id', $institutionId)
                    ->orWhere('target_institution_id', $institutionId);
            });
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->paginate($perPage);
    }

    /**
     * Approve mutation: move student to target institution.
     */
    public function approve(StudentMutation $mutation, int $approvedBy, ?string $notes = null): StudentMutation
    {
        if ($mutation->status !== 'pending') {
            throw new \InvalidArgumentException('Permohonan mutasi sudah diproses.');
        }
        if (!$mutation->canBeApprovedBy(\App\Models\User::find($approvedBy))) {
            throw new \InvalidArgumentException('Anda tidak berwenang menyetujui permohonan ini.');
        }

        DB::beginTransaction();
        try {
            $student = $mutation->student;

            if ($mutation->isExternalTarget()) {
                $student->load('class');
                $grade = $student->tingkat ?? $student->class?->grade;
                $gender = $this->normalizeGender($student->gender);
                $placement = $this->snapshotStudentPlacement($student);

                $student->status = 'Pindah';
                $student->class_id = null;
                $student->nis = null;
                $student->save();

                $mutation->student_grade = $grade;
                $mutation->student_gender = $gender;
                $mutation->previous_class_id = $placement['previous_class_id'];
                $mutation->previous_nis = $placement['previous_nis'];
                $mutation->previous_class_name = $placement['previous_class_name'];
                $mutation->status = 'approved';
                $mutation->approved_by = $approvedBy;
                $mutation->approved_at = now();
                $mutation->notes = $notes ?? $mutation->notes;
                $mutation->save();

                DB::commit();

                Log::info('Student mutation external (wali) approved', [
                    'mutation_id' => $mutation->id,
                    'student_id' => $student->id,
                ]);

                $mutation->load(['originInstitution', 'student', 'requester', 'approver']);
                $this->notifyInstitutionAdmins($mutation->origin_institution_id, $mutation, 'approved');

                return $mutation->fresh(['originInstitution', 'student', 'requester', 'approver']);
            }

            $target = Institution::with('activeAcademicYear')->find($mutation->target_institution_id);

            // NISN must not already exist for another active student at target institution
            if ($student->nisn) {
                $duplicate = Student::where('institution_id', $target->id)
                    ->where('nisn', $student->nisn)
                    ->where('id', '!=', $student->id)
                    ->exists();
                if ($duplicate) {
                    DB::rollBack();
                    throw new \InvalidArgumentException('NISN siswa sudah digunakan oleh siswa lain di sekolah tujuan. Mutasi tidak dapat disetujui.');
                }
            }

            // Simpan grade dan gender siswa sebelum pindah (untuk laporan per kelas/L-P)
            $student->load('class');
            $grade = $student->tingkat ?? $student->class?->grade;
            $gender = $this->normalizeGender($student->gender);
            $placement = $this->snapshotStudentPlacement($student);

            $student->institution_id = $target->id;
            $student->class_id = null;
            $student->class = null;
            $student->nis = null;
            $student->academic_year_id = $target->active_academic_year_id;
            $student->academic_year = $target->activeAcademicYear?->code ?: $target->activeAcademicYear?->name;
            $student->semester_id = $target->active_semester_id;
            $student->status = 'Aktif';
            $student->save();
            try {
                app(LocalNisService::class)->assignIfEmpty($student);
            } catch (\InvalidArgumentException $e) {
                Log::warning('Local NIS not generated on mutation approve', [
                    'student_id' => $student->id,
                    'error' => $e->getMessage(),
                ]);
            }

            $mutation->student_grade = $grade;
            $mutation->student_gender = $gender;
            $mutation->previous_class_id = $placement['previous_class_id'];
            $mutation->previous_nis = $placement['previous_nis'];
            $mutation->previous_class_name = $placement['previous_class_name'];
            $mutation->status = 'approved';
            $mutation->approved_by = $approvedBy;
            $mutation->approved_at = now();
            $mutation->notes = $notes ?? $mutation->notes;
            $mutation->save();

            DB::commit();

            Log::info('Student mutation approved', [
                'mutation_id' => $mutation->id,
                'student_id' => $student->id,
                'target_institution_id' => $target->id,
            ]);

            $mutation->load(['originInstitution', 'targetInstitution', 'student', 'requester', 'approver']);
            $this->notifyInstitutionAdmins($mutation->origin_institution_id, $mutation, 'approved');

            return $mutation->fresh(['originInstitution', 'targetInstitution', 'student', 'requester', 'approver']);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Student mutation approve failed', ['mutation_id' => $mutation->id, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Reject mutation.
     */
    public function reject(StudentMutation $mutation, int $approvedBy, string $rejectionReason, ?string $notes = null): StudentMutation
    {
        if ($mutation->status !== 'pending') {
            throw new \InvalidArgumentException('Permohonan mutasi sudah diproses.');
        }
        if (!$mutation->canBeRejectedBy(\App\Models\User::find($approvedBy))) {
            throw new \InvalidArgumentException('Anda tidak berwenang menolak permohonan ini.');
        }

        $mutation->status = 'rejected';
        $mutation->approved_by = $approvedBy;
        $mutation->approved_at = now();
        $mutation->rejection_reason = $rejectionReason;
        $mutation->notes = $notes ?? $mutation->notes;
        $mutation->save();

        Log::info('Student mutation rejected', ['mutation_id' => $mutation->id]);

        $mutation->load(['originInstitution', 'targetInstitution', 'student', 'requester', 'approver']);
        $this->notifyInstitutionAdmins($mutation->origin_institution_id, $mutation, 'rejected');

        return $mutation->fresh(['originInstitution', 'targetInstitution', 'student', 'requester', 'approver']);
    }

    /**
     * Batalkan mutasi:
     * - pending → cancelled langsung
     * - approved eksternal → rollback siswa + cancelled langsung
     * - approved internal → cancel_pending (menunggu admin sekolah tujuan)
     */
    public function requestCancel(StudentMutation $mutation, User $user, ?string $reason = null): StudentMutation
    {
        if (!$mutation->canRequestCancelBy($user)) {
            throw new \InvalidArgumentException('Anda tidak berwenang membatalkan permohonan mutasi ini.');
        }

        if ($mutation->status === 'pending') {
            $mutation->status = 'cancelled';
            $mutation->cancel_reason = $reason;
            $mutation->cancel_requested_by = $user->id;
            $mutation->cancel_requested_at = now();
            $mutation->cancel_rejection_reason = null;
            $mutation->save();

            $notifyId = $mutation->initiated_by === 'origin'
                ? $mutation->target_institution_id
                : $mutation->origin_institution_id;
            $mutation->load(['originInstitution', 'targetInstitution', 'student', 'requester', 'approver', 'cancelRequester']);
            $this->notifyInstitutionAdmins($notifyId, $mutation, 'cancelled');

            return $mutation->fresh(['originInstitution', 'targetInstitution', 'student', 'requester', 'approver', 'cancelRequester']);
        }

        if ($mutation->status !== 'approved') {
            throw new \InvalidArgumentException('Permohonan mutasi tidak dapat dibatalkan pada status ini.');
        }

        // External target/origin: batalkan langsung + rollback siswa
        if ($mutation->isExternalTarget() || $mutation->isExternalOrigin()) {
            return $this->finalizeCancel($mutation, $user, $reason, true);
        }

        // Sudah diterima sekolah tujuan: butuh persetujuan admin tujuan
        $mutation->status = 'cancel_pending';
        $mutation->cancel_reason = $reason;
        $mutation->cancel_requested_by = $user->id;
        $mutation->cancel_requested_at = now();
        $mutation->cancel_rejection_reason = null;
        $mutation->save();

        $mutation->load(['originInstitution', 'targetInstitution', 'student', 'requester', 'approver', 'cancelRequester']);
        $this->notifyInstitutionAdmins($mutation->target_institution_id, $mutation, 'cancel_requested');

        return $mutation->fresh(['originInstitution', 'targetInstitution', 'student', 'requester', 'approver', 'cancelRequester']);
    }

    /**
     * Setujui pembatalan mutasi yang sudah approved (oleh admin sekolah tujuan).
     */
    public function approveCancel(StudentMutation $mutation, User $user, ?string $notes = null): StudentMutation
    {
        if (!$mutation->canDecideCancelBy($user)) {
            throw new \InvalidArgumentException('Anda tidak berwenang menyetujui pembatalan mutasi ini.');
        }

        return $this->finalizeCancel($mutation, $user, $mutation->cancel_reason, false, $notes);
    }

    /**
     * Tolak permohonan pembatalan → status kembali approved.
     */
    public function rejectCancel(StudentMutation $mutation, User $user, string $rejectionReason): StudentMutation
    {
        if (!$mutation->canDecideCancelBy($user)) {
            throw new \InvalidArgumentException('Anda tidak berwenang menolak pembatalan mutasi ini.');
        }

        $mutation->status = 'approved';
        $mutation->cancel_rejection_reason = $rejectionReason;
        $mutation->notes = $mutation->notes;
        $mutation->save();

        $mutation->load(['originInstitution', 'targetInstitution', 'student', 'requester', 'approver', 'cancelRequester']);
        $this->notifyInstitutionAdmins($mutation->origin_institution_id, $mutation, 'cancel_rejected');

        return $mutation->fresh(['originInstitution', 'targetInstitution', 'student', 'requester', 'approver', 'cancelRequester']);
    }

    /**
     * Rollback siswa ke sekolah asal (atau aktifkan kembali) lalu set status cancelled.
     */
    protected function finalizeCancel(
        StudentMutation $mutation,
        User $user,
        ?string $reason = null,
        bool $setCancelMeta = false,
        ?string $notes = null
    ): StudentMutation {
        DB::beginTransaction();
        try {
            $student = $mutation->student()->lockForUpdate()->first();
            if (!$student) {
                throw new \InvalidArgumentException('Data siswa tidak ditemukan.');
            }

            if ($mutation->isExternalTarget()) {
                // Siswa masih di sekolah asal, status Pindah → Aktif + restore kelas/NIS
                $student->status = 'Aktif';
                $this->restoreStudentPlacement($student, $mutation);
                $student->save();
            } elseif ($mutation->isExternalOrigin()) {
                // Siswa dibuat di sekolah tujuan dari luar sistem
                $student->status = 'Pindah';
                $student->class_id = null;
                $student->nis = null;
                $student->save();
            } else {
                $origin = Institution::with('activeAcademicYear')->find($mutation->origin_institution_id);
                if (!$origin) {
                    throw new \InvalidArgumentException('Sekolah asal tidak ditemukan.');
                }

                // Pastikan NISN belum dipakai siswa aktif lain di sekolah asal
                if ($student->nisn) {
                    $duplicate = Student::where('institution_id', $origin->id)
                        ->where('nisn', $student->nisn)
                        ->where('id', '!=', $student->id)
                        ->where('status', 'Aktif')
                        ->exists();
                    if ($duplicate) {
                        throw new \InvalidArgumentException('NISN siswa sudah digunakan di sekolah asal. Pembatalan tidak dapat disetujui.');
                    }
                }

                $student->institution_id = $origin->id;
                $student->class_id = null;
                $student->class = null;
                $student->nis = null;
                $this->restoreStudentPlacement($student, $mutation);
                $student->academic_year_id = $origin->active_academic_year_id;
                $student->academic_year = $origin->activeAcademicYear?->code ?: $origin->activeAcademicYear?->name;
                $student->semester_id = $origin->active_semester_id;
                $student->status = 'Aktif';
                $student->save();
            }

            $mutation->status = 'cancelled';
            if ($setCancelMeta) {
                $mutation->cancel_reason = $reason;
                $mutation->cancel_requested_by = $user->id;
                $mutation->cancel_requested_at = now();
            }
            if ($notes !== null) {
                $mutation->notes = $notes;
            }
            $mutation->cancel_rejection_reason = null;
            $mutation->save();

            DB::commit();

            Log::info('Student mutation cancelled', [
                'mutation_id' => $mutation->id,
                'student_id' => $student->id,
                'by' => $user->id,
            ]);

            $mutation->load(['originInstitution', 'targetInstitution', 'student', 'requester', 'approver', 'cancelRequester']);
            $notifyId = $mutation->isExternalTarget() || $mutation->isExternalOrigin()
                ? null
                : $mutation->origin_institution_id;
            if ($notifyId) {
                $this->notifyInstitutionAdmins($notifyId, $mutation, 'cancelled');
            }

            return $mutation->fresh(['originInstitution', 'targetInstitution', 'student', 'requester', 'approver', 'cancelRequester']);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Student mutation cancel failed', ['mutation_id' => $mutation->id, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Notify all admin/institution_admin users of an institution.
     */
    protected function notifyInstitutionAdmins(?int $institutionId, StudentMutation $mutation, string $action): void
    {
        if (!$institutionId) {
            return;
        }
        $users = User::where('institution_id', $institutionId)
            ->whereIn('role', ['admin', 'institution_admin'])
            ->get();
        foreach ($users as $user) {
            $user->notify(new StudentMutationNotification($mutation, $action));
        }
    }

    /**
     * Report mutations for institution in date range.
     * type: 'out' = mutasi keluar (we are origin), 'in' = mutasi masuk (we are target), 'all' = both.
     *
     * @return array{summary: array{mutasi_keluar: int, mutasi_masuk: int}, data: \Illuminate\Contracts\Pagination\LengthAwarePaginator}
     */
    public function reportForInstitution(int $institutionId, ?string $from, ?string $to, string $type = 'all', int $perPage = 15): array
    {
        $query = StudentMutation::with([
            'originInstitution:id,name,npsn,level',
            'targetInstitution:id,name,npsn,level',
            'student:id,nisn,nis,name,gender,status',
            'requester:id,name',
            'approver:id,name',
        ])->activeApproved()->orderBy('approved_at', 'desc');

        if ($from) {
            $query->whereDate('approved_at', '>=', $from);
        }
        if ($to) {
            $query->whereDate('approved_at', '<=', $to);
        }

        $queryOut = (clone $query)->where('origin_institution_id', $institutionId);
        $queryIn = (clone $query)->where('target_institution_id', $institutionId);

        $mutasiKeluar = $queryOut->count();
        $mutasiMasuk = $queryIn->count();

        if ($type === 'out') {
            $query->where('origin_institution_id', $institutionId);
        } elseif ($type === 'in') {
            $query->where('target_institution_id', $institutionId);
        } else {
            $query->where(function ($q) use ($institutionId) {
                $q->where('origin_institution_id', $institutionId)
                    ->orWhere('target_institution_id', $institutionId);
            });
        }

        $data = $query->paginate($perPage);

        return [
            'summary' => [
                'mutasi_keluar' => $mutasiKeluar,
                'mutasi_masuk' => $mutasiMasuk,
            ],
            'data' => $data,
        ];
    }

    /**
     * List approved mutations for export (Buku Mutasi). Same filters as report; no pagination.
     *
     * @return Collection<int, StudentMutation>
     */
    public function listForExport(int $institutionId, ?string $from, ?string $to, string $type = 'all', int $limit = 2000): Collection
    {
        $query = StudentMutation::with([
            'originInstitution:id,name,npsn,level',
            'targetInstitution:id,name,npsn,level',
            'student:id,nisn,nis,name,gender,status',
            'approver:id,name',
        ])->activeApproved()->orderBy('approved_at', 'asc');

        if ($from) {
            $query->whereDate('approved_at', '>=', $from);
        }
        if ($to) {
            $query->whereDate('approved_at', '<=', $to);
        }

        if ($type === 'out') {
            $query->where('origin_institution_id', $institutionId);
        } elseif ($type === 'in') {
            $query->where('target_institution_id', $institutionId);
        } else {
            $query->where(function ($q) use ($institutionId) {
                $q->where('origin_institution_id', $institutionId)
                    ->orWhere('target_institution_id', $institutionId);
            });
        }

        return $query->limit($limit)->get();
    }

    /**
     * Mutation history for a student (only if user's institution is involved).
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, StudentMutation>
     */
    public function historyForStudent(int $studentId, int $userInstitutionId): \Illuminate\Database\Eloquent\Collection
    {
        $student = Student::find($studentId);
        if (!$student) {
            throw new \InvalidArgumentException('Siswa tidak ditemukan.');
        }
        $canAccess = $student->institution_id === $userInstitutionId;
        if (!$canAccess) {
            $canAccess = StudentMutation::where('student_id', $studentId)
                ->where(function ($q) use ($userInstitutionId) {
                    $q->where('origin_institution_id', $userInstitutionId)
                        ->orWhere('target_institution_id', $userInstitutionId);
                })->exists();
        }
        if (!$canAccess) {
            throw new \InvalidArgumentException('Anda tidak berwenang melihat riwayat mutasi siswa ini.');
        }

        return StudentMutation::with([
            'originInstitution:id,name,npsn,level',
            'targetInstitution:id,name,npsn,level',
            'student:id,nisn,nis,name',
            'requester:id,name',
            'approver:id,name',
        ])->where('student_id', $studentId)->orderBy('created_at', 'desc')->get();
    }

    /**
     * Mutation history by NISN (student must be in user's institution or in a mutation involving it).
     */
    public function historyByNisn(string $nisn, int $userInstitutionId): \Illuminate\Database\Eloquent\Collection
    {
        $student = Student::where('nisn', $nisn)->first();
        if (!$student) {
            throw new \InvalidArgumentException('Siswa dengan NISN tersebut tidak ditemukan.');
        }
        return $this->historyForStudent($student->id, $userInstitutionId);
    }
}
