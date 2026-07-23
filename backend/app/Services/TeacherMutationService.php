<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\EmployeeInstitutionAssignment;
use App\Models\Extracurricular;
use App\Models\Institution;
use App\Models\Room;
use App\Models\SchoolClass;
use App\Models\TeacherMutation;
use App\Models\User;
use App\Notifications\TeacherMutationNotification;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TeacherMutationService
{
    /**
     * Lookup active teacher (Guru) by NUPTK at the given institution (for mutation confirmation preview).
     *
     * @return array{id: int, nuptk: string|null, nip: string|null, name: string, gender: string|null, status: string|null, email: string|null}
     */
    public function lookupTeacherByNuptk(int $institutionId, string $nuptk): array
    {
        $employee = Employee::where('nuptk', $nuptk)
            ->where('institution_id', $institutionId)
            ->where('type', 'Guru')
            ->where('status', 'Aktif')
            ->first();

        if (!$employee) {
            throw new \InvalidArgumentException('Guru dengan NUPTK tersebut tidak ditemukan di sekolah Anda atau status tidak aktif.');
        }

        return $this->formatTeacherLookup($employee);
    }

    /**
     * Lookup active teacher at origin school by NPSN + NUPTK (for pull confirmation preview).
     * Tidak ada batasan jenjang untuk mutasi guru.
     *
     * @return array{id: int, nuptk: string|null, nip: string|null, name: string, gender: string|null, status: string|null, email: string|null, institution: array{id: int, name: string, npsn: string|null, level: string|null}}
     */
    public function lookupTeacherAtOriginByNpsn(string $originNpsn, string $nuptk, int $excludeInstitutionId): array
    {
        $origin = Institution::where('npsn', $originNpsn)->where('is_active', true)->first();
        if (!$origin) {
            throw new \InvalidArgumentException('Sekolah asal dengan NPSN tersebut tidak ditemukan atau tidak aktif.');
        }
        if ((int) $origin->id === (int) $excludeInstitutionId) {
            throw new \InvalidArgumentException('Sekolah asal harus berbeda dengan sekolah Anda.');
        }

        $employee = Employee::where('nuptk', $nuptk)
            ->where('institution_id', $origin->id)
            ->where('type', 'Guru')
            ->where('status', 'Aktif')
            ->first();

        if (!$employee) {
            throw new \InvalidArgumentException('Guru dengan NUPTK tersebut tidak ditemukan di sekolah asal atau status tidak aktif.');
        }

        $data = $this->formatTeacherLookup($employee);
        $data['institution'] = [
            'id' => $origin->id,
            'name' => $origin->name,
            'npsn' => $origin->npsn,
            'level' => $origin->level,
        ];

        return $data;
    }

    /**
     * @return array{id: int, nuptk: string|null, nip: string|null, name: string, gender: string|null, status: string|null, email: string|null}
     */
    protected function formatTeacherLookup(Employee $employee): array
    {
        return [
            'id' => $employee->id,
            'nuptk' => $employee->nuptk,
            'nip' => $employee->nip,
            'name' => $employee->name,
            'gender' => $employee->gender,
            'status' => $employee->status,
            'email' => $employee->email,
        ];
    }

    protected function normalizeGender(mixed $gender): string
    {
        if (is_string($gender)) {
            return preg_match('/^(L|l|Laki|Male)/i', $gender) ? 'L' : 'P';
        }

        return 'P';
    }

    /**
     * Pastikan guru belum memiliki permohonan mutasi yang masih pending.
     */
    protected function assertNoPendingMutation(int $employeeId): void
    {
        $pending = TeacherMutation::where('employee_id', $employeeId)
            ->where('status', 'pending')
            ->exists();
        if ($pending) {
            throw new \InvalidArgumentException('Guru ini masih memiliki permohonan mutasi yang menunggu persetujuan.');
        }
    }

    /**
     * Create mutation request from origin school (admin asal: pilih NPSN tujuan + NUPTK guru).
     */
    public function createFromOrigin(int $originInstitutionId, string $targetNpsn, string $nuptk, int $requestedBy, ?string $notes = null): TeacherMutation
    {
        $target = Institution::where('npsn', $targetNpsn)->where('is_active', true)->firstOrFail();
        if ((int) $target->id === (int) $originInstitutionId) {
            throw new \InvalidArgumentException('Sekolah tujuan harus berbeda dengan sekolah asal.');
        }

        $employee = Employee::where('nuptk', $nuptk)
            ->where('institution_id', $originInstitutionId)
            ->where('type', 'Guru')
            ->where('status', 'Aktif')
            ->firstOrFail();

        $this->assertNoPendingMutation($employee->id);

        $mutation = TeacherMutation::create([
            'origin_institution_id' => $originInstitutionId,
            'target_institution_id' => $target->id,
            'employee_id' => $employee->id,
            'initiated_by' => 'origin',
            'requested_by' => $requestedBy,
            'status' => 'pending',
            'notes' => $notes,
        ]);
        $mutation->load(['originInstitution', 'targetInstitution', 'employee', 'requester']);
        $this->notifyInstitutionAdmins($target->id, $mutation, 'requested');
        return $mutation;
    }

    /**
     * Mutasi keluar ke sekolah yang belum terdaftar di aplikasi.
     * NPSN dan nama sekolah dicatat manual. Status langsung approved, guru di-mark Pindah.
     */
    public function createFromOriginExternal(
        int $originInstitutionId,
        string $targetNpsn,
        string $targetSchoolName,
        string $nuptk,
        int $requestedBy,
        ?string $notes = null
    ): TeacherMutation {
        $employee = Employee::where('nuptk', $nuptk)
            ->where('institution_id', $originInstitutionId)
            ->where('type', 'Guru')
            ->where('status', 'Aktif')
            ->firstOrFail();

        $this->assertNoPendingMutation($employee->id);

        DB::beginTransaction();
        try {
            $gender = $this->normalizeGender($employee->gender);

            $mutation = TeacherMutation::create([
                'origin_institution_id' => $originInstitutionId,
                'target_institution_id' => null,
                'target_npsn' => $targetNpsn,
                'target_school_name' => $targetSchoolName,
                'employee_id' => $employee->id,
                'employee_nuptk' => $employee->nuptk,
                'employee_nip' => $employee->nip,
                'employee_gender' => $gender,
                'initiated_by' => 'origin',
                'requested_by' => $requestedBy,
                'approved_by' => $requestedBy,
                'status' => 'approved',
                'approved_at' => now(),
                'notes' => $notes,
            ]);

            $employee->status = 'Pindah';
            $employee->save();

            $this->cleanupActiveRolesAtInstitution($employee, $originInstitutionId);
            $this->endAllApprovedAssignments($employee->id, 'Mutasi guru');
            $this->deactivateUserIfNoRemainingAssignments($employee);

            DB::commit();

            Log::info('Teacher mutation external (out of system) created', [
                'mutation_id' => $mutation->id,
                'employee_id' => $employee->id,
                'target_npsn' => $targetNpsn,
            ]);

            return $mutation->load(['originInstitution', 'employee', 'requester', 'approver']);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Teacher mutation external create failed', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Create mutation request from target school (admin tujuan: tarik guru dari sekolah asal).
     * initiated_by = 'target'.
     */
    public function createFromTarget(int $targetInstitutionId, string $originNpsn, string $nuptk, int $requestedBy, ?string $notes = null): TeacherMutation
    {
        $origin = Institution::where('npsn', $originNpsn)->where('is_active', true)->firstOrFail();
        if ((int) $origin->id === (int) $targetInstitutionId) {
            throw new \InvalidArgumentException('Sekolah asal harus berbeda dengan sekolah tujuan.');
        }

        $employee = Employee::where('nuptk', $nuptk)
            ->where('institution_id', $origin->id)
            ->where('type', 'Guru')
            ->where('status', 'Aktif')
            ->firstOrFail();

        $this->assertNoPendingMutation($employee->id);

        $mutation = TeacherMutation::create([
            'origin_institution_id' => $origin->id,
            'target_institution_id' => $targetInstitutionId,
            'employee_id' => $employee->id,
            'initiated_by' => 'target',
            'requested_by' => $requestedBy,
            'status' => 'pending',
            'notes' => $notes,
        ]);
        $mutation->load(['originInstitution', 'targetInstitution', 'employee', 'requester']);
        $this->notifyInstitutionAdmins($origin->id, $mutation, 'requested');
        return $mutation;
    }

    /**
     * Mutasi masuk dari sekolah yang belum terdaftar di aplikasi.
     * Buat data guru baru di sekolah kita + catat mutasi (status approved).
     */
    public function createFromTargetExternal(
        int $targetInstitutionId,
        string $originNpsn,
        string $originSchoolName,
        string $teacherName,
        string $nuptk,
        string $teacherGender,
        ?string $teacherNip,
        ?string $teacherEmail,
        int $requestedBy,
        ?string $notes = null
    ): TeacherMutation {
        $target = Institution::findOrFail($targetInstitutionId);

        if ($nuptk !== '' && Employee::where('nuptk', $nuptk)->exists()) {
            throw new \InvalidArgumentException('NUPTK tersebut sudah digunakan oleh guru lain di sistem.');
        }

        $gender = preg_match('/^(L|l|Laki|Male)/i', $teacherGender) ? 'L' : 'P';

        DB::beginTransaction();
        try {
            $employee = Employee::create([
                'institution_id' => $target->id,
                'type' => 'Guru',
                'nuptk' => $nuptk !== '' ? $nuptk : null,
                'nip' => $teacherNip !== null && $teacherNip !== '' ? $teacherNip : null,
                'name' => $teacherName,
                'gender' => $gender,
                'email' => $teacherEmail !== null && $teacherEmail !== '' ? $teacherEmail : null,
                'status' => 'Aktif',
            ]);

            $mutation = TeacherMutation::create([
                'origin_institution_id' => null,
                'origin_npsn' => $originNpsn,
                'origin_school_name' => $originSchoolName,
                'target_institution_id' => $target->id,
                'employee_id' => $employee->id,
                'employee_nuptk' => $employee->nuptk,
                'employee_nip' => $employee->nip,
                'employee_gender' => $gender,
                'initiated_by' => 'target',
                'requested_by' => $requestedBy,
                'approved_by' => $requestedBy,
                'status' => 'approved',
                'approved_at' => now(),
                'notes' => $notes,
            ]);

            DB::commit();

            Log::info('Teacher mutation external origin (into system) created', [
                'mutation_id' => $mutation->id,
                'employee_id' => $employee->id,
                'origin_npsn' => $originNpsn,
            ]);

            return $mutation->load(['targetInstitution', 'employee', 'requester', 'approver']);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Teacher mutation external origin create failed', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * List mutations for current user's institution (as origin or target).
     */
    public function listForInstitution(int $institutionId, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = TeacherMutation::with([
            'originInstitution:id,name,npsn,level',
            'targetInstitution:id,name,npsn,level',
            'employee:id,nuptk,nip,name,gender,status,email',
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
     * Bersihkan peran aktif guru di sebuah institusi setelah mutasi (dan institusi tempat
     * guru berada sekarang, bila berbeda): wali kelas, pembina ekskul, PJ ruang, jabatan
     * tambahan (non-induk), dan tugas tambahan yang masih berjalan.
     */
    protected function cleanupActiveRolesAtInstitution(Employee $employee, ?int $originInstitutionId): void
    {
        if ($originInstitutionId) {
            SchoolClass::where('teacher_id', $employee->id)
                ->where('institution_id', $originInstitutionId)
                ->update(['teacher_id' => null]);

            Extracurricular::where('supervisor_employee_id', $employee->id)
                ->where('institution_id', $originInstitutionId)
                ->update(['supervisor_employee_id' => null]);

            Room::where('responsible_employee_id', $employee->id)
                ->where('institution_id', $originInstitutionId)
                ->update(['responsible_employee_id' => null]);
        }

        $assignmentInstitutionIds = array_values(array_unique(array_filter([
            $originInstitutionId,
            $employee->institution_id,
        ])));

        if (!empty($assignmentInstitutionIds)) {
            EmployeeInstitutionAssignment::where('employee_id', $employee->id)
                ->where('status', 'approved')
                ->whereIn('institution_id', $assignmentInstitutionIds)
                ->update([
                    'status' => 'ended',
                    'ended_at' => now(),
                    'ended_reason' => 'Mutasi guru',
                ]);
        }

        DB::table('employee_additional_duties')
            ->where('employee_id', $employee->id)
            ->whereNull('ended_at')
            ->update(['ended_at' => now(), 'updated_at' => now()]);
    }

    /**
     * Akhiri semua assignment non-induk approved milik guru (dipakai saat mutasi eksternal keluar).
     */
    protected function endAllApprovedAssignments(int $employeeId, string $reason): void
    {
        EmployeeInstitutionAssignment::where('employee_id', $employeeId)
            ->where('status', 'approved')
            ->update([
                'status' => 'ended',
                'ended_at' => now(),
                'ended_reason' => $reason,
            ]);
    }

    /**
     * Nonaktifkan akun login guru bila tidak ada lagi assignment approved yang tersisa
     * (dipakai saat guru mutasi keluar sistem / ke sekolah luar).
     */
    protected function deactivateUserIfNoRemainingAssignments(Employee $employee): void
    {
        if (!$employee->email) {
            return;
        }
        $user = User::where('email', $employee->email)->first();
        if (!$user) {
            return;
        }
        $hasApprovedAssignment = EmployeeInstitutionAssignment::where('employee_id', $employee->id)
            ->where('status', 'approved')
            ->exists();
        if (!$hasApprovedAssignment && $user->is_active) {
            $user->is_active = false;
            $user->save();
        }
    }

    /**
     * Approve mutation: move teacher to target institution.
     */
    public function approve(TeacherMutation $mutation, int $approvedBy, ?string $notes = null): TeacherMutation
    {
        if ($mutation->status !== 'pending') {
            throw new \InvalidArgumentException('Permohonan mutasi sudah diproses.');
        }
        if (!$mutation->canBeApprovedBy(User::find($approvedBy))) {
            throw new \InvalidArgumentException('Anda tidak berwenang menyetujui permohonan ini.');
        }

        DB::beginTransaction();
        try {
            $employee = $mutation->employee;

            // Jalur eksternal seharusnya sudah auto-approved saat dibuat; ini jaring pengaman
            // bila suatu saat ada mutasi eksternal yang tersimpan sebagai pending.
            if ($mutation->isExternalTarget() || $mutation->isExternalOrigin()) {
                $originInstitutionId = $mutation->origin_institution_id;

                $employee->status = 'Pindah';
                $employee->save();

                $this->cleanupActiveRolesAtInstitution($employee, $originInstitutionId);
                $this->endAllApprovedAssignments($employee->id, 'Mutasi guru');
                $this->deactivateUserIfNoRemainingAssignments($employee);

                $mutation->employee_nuptk = $employee->nuptk;
                $mutation->employee_nip = $employee->nip;
                $mutation->employee_gender = $this->normalizeGender($employee->gender);
                $mutation->status = 'approved';
                $mutation->approved_by = $approvedBy;
                $mutation->approved_at = now();
                $mutation->notes = $notes ?? $mutation->notes;
                $mutation->save();

                DB::commit();

                Log::info('Teacher mutation external approved (safety net)', [
                    'mutation_id' => $mutation->id,
                    'employee_id' => $employee->id,
                ]);

                $mutation->load(['originInstitution', 'employee', 'requester', 'approver']);
                $this->notifyInstitutionAdmins($mutation->origin_institution_id, $mutation, 'approved');

                return $mutation->fresh(['originInstitution', 'employee', 'requester', 'approver']);
            }

            $target = Institution::find($mutation->target_institution_id);
            if (!$target) {
                DB::rollBack();
                throw new \InvalidArgumentException('Sekolah tujuan tidak ditemukan.');
            }

            // NUPTK tidak boleh dipakai guru aktif lain di sekolah tujuan
            if ($employee->nuptk) {
                $duplicate = Employee::where('institution_id', $target->id)
                    ->where('nuptk', $employee->nuptk)
                    ->where('id', '!=', $employee->id)
                    ->exists();
                if ($duplicate) {
                    DB::rollBack();
                    throw new \InvalidArgumentException('NUPTK guru sudah digunakan oleh guru lain di sekolah tujuan. Mutasi tidak dapat disetujui.');
                }
            }

            $originInstitutionId = $mutation->origin_institution_id;

            // Simpan snapshot identitas guru sebelum pindah
            $mutation->employee_nuptk = $employee->nuptk;
            $mutation->employee_nip = $employee->nip;
            $mutation->employee_gender = $this->normalizeGender($employee->gender);

            $employee->institution_id = $target->id;
            $employee->status = 'Aktif';
            $employee->save();

            if ($employee->email) {
                User::where('email', $employee->email)->update(['institution_id' => $target->id]);
            }

            $this->cleanupActiveRolesAtInstitution($employee, $originInstitutionId);

            $mutation->status = 'approved';
            $mutation->approved_by = $approvedBy;
            $mutation->approved_at = now();
            $mutation->notes = $notes ?? $mutation->notes;
            $mutation->save();

            DB::commit();

            Log::info('Teacher mutation approved', [
                'mutation_id' => $mutation->id,
                'employee_id' => $employee->id,
                'target_institution_id' => $target->id,
            ]);

            $mutation->load(['originInstitution', 'targetInstitution', 'employee', 'requester', 'approver']);
            $this->notifyInstitutionAdmins($mutation->origin_institution_id, $mutation, 'approved');

            return $mutation->fresh(['originInstitution', 'targetInstitution', 'employee', 'requester', 'approver']);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Teacher mutation approve failed', ['mutation_id' => $mutation->id, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Reject mutation.
     */
    public function reject(TeacherMutation $mutation, int $approvedBy, string $rejectionReason, ?string $notes = null): TeacherMutation
    {
        if ($mutation->status !== 'pending') {
            throw new \InvalidArgumentException('Permohonan mutasi sudah diproses.');
        }
        if (!$mutation->canBeRejectedBy(User::find($approvedBy))) {
            throw new \InvalidArgumentException('Anda tidak berwenang menolak permohonan ini.');
        }

        $mutation->status = 'rejected';
        $mutation->approved_by = $approvedBy;
        $mutation->approved_at = now();
        $mutation->rejection_reason = $rejectionReason;
        $mutation->notes = $notes ?? $mutation->notes;
        $mutation->save();

        Log::info('Teacher mutation rejected', ['mutation_id' => $mutation->id]);

        $mutation->load(['originInstitution', 'targetInstitution', 'employee', 'requester', 'approver']);
        $this->notifyInstitutionAdmins($mutation->origin_institution_id, $mutation, 'rejected');

        return $mutation->fresh(['originInstitution', 'targetInstitution', 'employee', 'requester', 'approver']);
    }

    /**
     * Batalkan mutasi:
     * - pending → cancelled langsung
     * - approved eksternal → rollback guru + cancelled langsung
     * - approved internal → cancel_pending (menunggu admin sekolah tujuan)
     */
    public function requestCancel(TeacherMutation $mutation, User $user, ?string $reason = null): TeacherMutation
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
            $mutation->load(['originInstitution', 'targetInstitution', 'employee', 'requester', 'approver', 'cancelRequester']);
            $this->notifyInstitutionAdmins($notifyId, $mutation, 'cancelled');

            return $mutation->fresh(['originInstitution', 'targetInstitution', 'employee', 'requester', 'approver', 'cancelRequester']);
        }

        if ($mutation->status !== 'approved') {
            throw new \InvalidArgumentException('Permohonan mutasi tidak dapat dibatalkan pada status ini.');
        }

        // External target/origin: batalkan langsung + rollback guru
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

        $mutation->load(['originInstitution', 'targetInstitution', 'employee', 'requester', 'approver', 'cancelRequester']);
        $this->notifyInstitutionAdmins($mutation->target_institution_id, $mutation, 'cancel_requested');

        return $mutation->fresh(['originInstitution', 'targetInstitution', 'employee', 'requester', 'approver', 'cancelRequester']);
    }

    /**
     * Setujui pembatalan mutasi yang sudah approved (oleh admin sekolah tujuan).
     */
    public function approveCancel(TeacherMutation $mutation, User $user, ?string $notes = null): TeacherMutation
    {
        if (!$mutation->canDecideCancelBy($user)) {
            throw new \InvalidArgumentException('Anda tidak berwenang menyetujui pembatalan mutasi ini.');
        }

        return $this->finalizeCancel($mutation, $user, $mutation->cancel_reason, false, $notes);
    }

    /**
     * Tolak permohonan pembatalan → status kembali approved.
     */
    public function rejectCancel(TeacherMutation $mutation, User $user, string $rejectionReason): TeacherMutation
    {
        if (!$mutation->canDecideCancelBy($user)) {
            throw new \InvalidArgumentException('Anda tidak berwenang menolak pembatalan mutasi ini.');
        }

        $mutation->status = 'approved';
        $mutation->cancel_rejection_reason = $rejectionReason;
        $mutation->notes = $mutation->notes;
        $mutation->save();

        $mutation->load(['originInstitution', 'targetInstitution', 'employee', 'requester', 'approver', 'cancelRequester']);
        $this->notifyInstitutionAdmins($mutation->origin_institution_id, $mutation, 'cancel_rejected');

        return $mutation->fresh(['originInstitution', 'targetInstitution', 'employee', 'requester', 'approver', 'cancelRequester']);
    }

    /**
     * Rollback guru ke sekolah asal (atau aktifkan kembali) lalu set status cancelled.
     */
    protected function finalizeCancel(
        TeacherMutation $mutation,
        User $user,
        ?string $reason = null,
        bool $setCancelMeta = false,
        ?string $notes = null
    ): TeacherMutation {
        DB::beginTransaction();
        try {
            $employee = $mutation->employee()->lockForUpdate()->first();
            if (!$employee) {
                throw new \InvalidArgumentException('Data guru tidak ditemukan.');
            }

            if ($mutation->isExternalTarget()) {
                // Guru masih di sekolah asal, status Pindah → Aktif
                $employee->status = 'Aktif';
                $employee->save();

                if ($employee->email) {
                    $account = User::where('email', $employee->email)->first();
                    if ($account && !$account->is_active) {
                        $account->is_active = true;
                        $account->save();
                    }
                }
            } elseif ($mutation->isExternalOrigin()) {
                // Guru dibuat di sekolah tujuan dari luar sistem
                $employee->status = 'Pindah';
                $employee->save();
            } else {
                $origin = Institution::find($mutation->origin_institution_id);
                if (!$origin) {
                    throw new \InvalidArgumentException('Sekolah asal tidak ditemukan.');
                }

                // Pastikan NUPTK belum dipakai guru aktif lain di sekolah asal
                if ($employee->nuptk) {
                    $duplicate = Employee::where('institution_id', $origin->id)
                        ->where('nuptk', $employee->nuptk)
                        ->where('id', '!=', $employee->id)
                        ->where('status', 'Aktif')
                        ->exists();
                    if ($duplicate) {
                        throw new \InvalidArgumentException('NUPTK guru sudah digunakan di sekolah asal. Pembatalan tidak dapat disetujui.');
                    }
                }

                $targetInstitutionId = $employee->institution_id;

                $employee->institution_id = $origin->id;
                $employee->status = 'Aktif';
                $employee->save();

                if ($employee->email) {
                    User::where('email', $employee->email)->update(['institution_id' => $origin->id]);
                }

                $this->cleanupActiveRolesAtInstitution($employee, $targetInstitutionId);
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

            Log::info('Teacher mutation cancelled', [
                'mutation_id' => $mutation->id,
                'employee_id' => $employee->id,
                'by' => $user->id,
            ]);

            $mutation->load(['originInstitution', 'targetInstitution', 'employee', 'requester', 'approver', 'cancelRequester']);
            $notifyId = $mutation->isExternalTarget() || $mutation->isExternalOrigin()
                ? null
                : $mutation->origin_institution_id;
            if ($notifyId) {
                $this->notifyInstitutionAdmins($notifyId, $mutation, 'cancelled');
            }

            return $mutation->fresh(['originInstitution', 'targetInstitution', 'employee', 'requester', 'approver', 'cancelRequester']);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Teacher mutation cancel failed', ['mutation_id' => $mutation->id, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Notify all admin/institution_admin users of an institution.
     */
    protected function notifyInstitutionAdmins(?int $institutionId, TeacherMutation $mutation, string $action): void
    {
        if (!$institutionId) {
            return;
        }
        $users = User::where('institution_id', $institutionId)
            ->whereIn('role', ['admin', 'institution_admin'])
            ->get();
        foreach ($users as $user) {
            $user->notify(new TeacherMutationNotification($mutation, $action));
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
        $query = TeacherMutation::with([
            'originInstitution:id,name,npsn,level',
            'targetInstitution:id,name,npsn,level',
            'employee:id,nuptk,nip,name,gender,status',
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
     * List approved mutations for export (Buku Mutasi Guru). Same filters as report; no pagination.
     *
     * @return Collection<int, TeacherMutation>
     */
    public function listForExport(int $institutionId, ?string $from, ?string $to, string $type = 'all', int $limit = 2000): Collection
    {
        $query = TeacherMutation::with([
            'originInstitution:id,name,npsn,level',
            'targetInstitution:id,name,npsn,level',
            'employee:id,nuptk,nip,name,gender,status',
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
     * Mutation history for a teacher (only if user's institution is involved).
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, TeacherMutation>
     */
    public function historyForEmployee(int $employeeId, int $userInstitutionId): \Illuminate\Database\Eloquent\Collection
    {
        $employee = Employee::find($employeeId);
        if (!$employee) {
            throw new \InvalidArgumentException('Guru tidak ditemukan.');
        }
        $canAccess = $employee->institution_id === $userInstitutionId;
        if (!$canAccess) {
            $canAccess = TeacherMutation::where('employee_id', $employeeId)
                ->where(function ($q) use ($userInstitutionId) {
                    $q->where('origin_institution_id', $userInstitutionId)
                        ->orWhere('target_institution_id', $userInstitutionId);
                })->exists();
        }
        if (!$canAccess) {
            throw new \InvalidArgumentException('Anda tidak berwenang melihat riwayat mutasi guru ini.');
        }

        return TeacherMutation::with([
            'originInstitution:id,name,npsn,level',
            'targetInstitution:id,name,npsn,level',
            'employee:id,nuptk,nip,name',
            'requester:id,name',
            'approver:id,name',
        ])->where('employee_id', $employeeId)->orderBy('created_at', 'desc')->get();
    }

    /**
     * Mutation history by NUPTK (teacher must be in user's institution or in a mutation involving it).
     */
    public function historyByNuptk(string $nuptk, int $userInstitutionId): \Illuminate\Database\Eloquent\Collection
    {
        $employee = Employee::where('nuptk', $nuptk)->where('type', 'Guru')->first();
        if (!$employee) {
            throw new \InvalidArgumentException('Guru dengan NUPTK tersebut tidak ditemukan.');
        }
        return $this->historyForEmployee($employee->id, $userInstitutionId);
    }
}
