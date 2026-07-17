<?php

namespace App\Services;

use App\Models\Institution;
use App\Models\Student;
use App\Models\StudentMutation;
use App\Models\User;
use App\Notifications\StudentMutationNotification;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class StudentMutationService
{
    /**
     * Create mutation request from origin school (admin asal: pilih NPSN tujuan + NISN siswa).
     */
    public function createFromOrigin(int $originInstitutionId, string $targetNpsn, string $nisn, int $requestedBy, ?string $notes = null): StudentMutation
    {
        $target = Institution::where('npsn', $targetNpsn)->where('is_active', true)->firstOrFail();
        $student = Student::where('nisn', $nisn)
            ->where('institution_id', $originInstitutionId)
            ->where('status', 'Aktif')
            ->firstOrFail();

        $mutation = StudentMutation::create([
            'origin_institution_id' => $originInstitutionId,
            'target_institution_id' => $target->id,
            'student_id' => $student->id,
            'initiated_by' => 'origin',
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

            $mutation = StudentMutation::create([
                'origin_institution_id' => $originInstitutionId,
                'target_institution_id' => null,
                'target_npsn' => $targetNpsn,
                'target_school_name' => $targetSchoolName,
                'student_id' => $student->id,
                'student_grade' => $grade,
                'student_gender' => $gender,
                'initiated_by' => 'origin',
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
        $student = Student::where('nisn', $nisn)
            ->where('institution_id', $origin->id)
            ->where('status', 'Aktif')
            ->firstOrFail();

        $mutation = StudentMutation::create([
            'origin_institution_id' => $origin->id,
            'target_institution_id' => $targetInstitutionId,
            'student_id' => $student->id,
            'initiated_by' => 'target',
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
                'academic_year' => $target->activeAcademicYear?->name,
                'semester_id' => $target->active_semester_id,
            ]);

            $mutation = StudentMutation::create([
                'origin_institution_id' => null,
                'origin_npsn' => $originNpsn,
                'origin_school_name' => $originSchoolName,
                'target_institution_id' => $targetInstitutionId,
                'student_id' => $student->id,
                'student_grade' => $studentGrade,
                'student_gender' => $gender,
                'initiated_by' => 'target',
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
            $gender = $student->gender;
            if (is_string($gender)) {
                $gender = preg_match('/^(L|l|Laki|Male)/i', $gender) ? 'L' : 'P';
            } else {
                $gender = 'P';
            }

            $student->institution_id = $target->id;
            $student->class_id = null;
            $student->class = null;
            $student->nis = null;
            $student->academic_year_id = $target->active_academic_year_id;
            $student->academic_year = $target->activeAcademicYear?->name ?? null;
            $student->semester_id = $target->active_semester_id;
            $student->status = 'Aktif';
            $student->save();

            $mutation->student_grade = $grade;
            $mutation->student_gender = $gender;
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
     * Notify all admin/institution_admin users of an institution.
     */
    protected function notifyInstitutionAdmins(int $institutionId, StudentMutation $mutation, string $action): void
    {
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
        ])->where('status', 'approved')->orderBy('approved_at', 'desc');

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
        ])->where('status', 'approved')->orderBy('approved_at', 'asc');

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
