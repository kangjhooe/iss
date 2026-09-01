<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\EmployeeAttendance;
use App\Models\EmployeeDecree;
use App\Models\EmployeeLeaveRequest;
use App\Models\EmployeeStructuralPosition;
use App\Models\Institution;
use App\Models\StructuralPosition;
use App\Models\TeacherMutation;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class KepegawaianService
{
    public function __construct(
        protected StructuralDutySync $structuralDutySync
    ) {}

    public function listLeaves(int $institutionId, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = EmployeeLeaveRequest::with([
            'employee:id,name,nip,nuptk,type,email',
            'requester:id,name',
            'approver:id,name',
        ])
            ->where('institution_id', $institutionId)
            ->orderByDesc('created_at');

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (!empty($filters['leave_type'])) {
            $query->where('leave_type', $filters['leave_type']);
        }
        if (!empty($filters['employee_id'])) {
            $query->where('employee_id', $filters['employee_id']);
        }
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->whereHas('employee', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('nip', 'like', "%{$search}%")
                    ->orWhere('nuptk', 'like', "%{$search}%");
            });
        }

        return $query->paginate($perPage);
    }

    public function createLeave(int $institutionId, array $data, int $userId, ?UploadedFile $attachment = null): EmployeeLeaveRequest
    {
        $employee = Employee::where('institution_id', $institutionId)->findOrFail($data['employee_id']);

        if (($data['end_date'] ?? null) < ($data['start_date'] ?? null)) {
            throw ValidationException::withMessages([
                'end_date' => ['Tanggal selesai harus sama atau setelah tanggal mulai.'],
            ]);
        }

        $overlap = EmployeeLeaveRequest::where('employee_id', $employee->id)
            ->whereIn('status', ['pending', 'approved'])
            ->where(function ($q) use ($data) {
                $q->whereBetween('start_date', [$data['start_date'], $data['end_date']])
                    ->orWhereBetween('end_date', [$data['start_date'], $data['end_date']])
                    ->orWhere(function ($q2) use ($data) {
                        $q2->where('start_date', '<=', $data['start_date'])
                            ->where('end_date', '>=', $data['end_date']);
                    });
            })
            ->exists();

        if ($overlap) {
            throw ValidationException::withMessages([
                'start_date' => ['Sudah ada pengajuan cuti yang overlap pada rentang tanggal ini.'],
            ]);
        }

        $payload = [
            'institution_id' => $institutionId,
            'employee_id' => $employee->id,
            'leave_type' => $data['leave_type'],
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
            'reason' => $data['reason'] ?? null,
            'notes' => $data['notes'] ?? null,
            'status' => $data['status'] ?? 'pending',
            'requested_by' => $userId,
        ];

        if ($attachment) {
            $path = $attachment->store("employee_leaves/{$employee->id}", 'public');
            $payload['attachment_path'] = $path;
            $payload['attachment_name'] = $attachment->getClientOriginalName();
        }

        if (($payload['status'] ?? 'pending') === 'approved') {
            $payload['approved_by'] = $userId;
            $payload['approved_at'] = now();
        }

        $leave = EmployeeLeaveRequest::create($payload)->load([
            'employee:id,name,nip,nuptk,type,email',
            'requester:id,name',
            'approver:id,name',
        ]);

        if ($leave->status === 'approved') {
            $this->applyLeaveAttendance($leave);
        }

        return $leave;
    }

    public function decideLeave(EmployeeLeaveRequest $leave, string $action, int $userId, ?string $rejectionReason = null): EmployeeLeaveRequest
    {
        if ($leave->status !== 'pending') {
            throw ValidationException::withMessages([
                'status' => ['Hanya pengajuan pending yang dapat diproses.'],
            ]);
        }

        if ($action === 'approve') {
            $leave->update([
                'status' => 'approved',
                'approved_by' => $userId,
                'approved_at' => now(),
                'rejection_reason' => null,
            ]);
            $this->applyLeaveAttendance($leave->fresh());
        } elseif ($action === 'reject') {
            if (!$rejectionReason) {
                throw ValidationException::withMessages([
                    'rejection_reason' => ['Alasan penolakan wajib diisi.'],
                ]);
            }
            $leave->update([
                'status' => 'rejected',
                'approved_by' => $userId,
                'approved_at' => now(),
                'rejection_reason' => $rejectionReason,
            ]);
        } else {
            throw ValidationException::withMessages([
                'action' => ['Aksi tidak valid.'],
            ]);
        }

        return $leave->fresh([
            'employee:id,name,nip,nuptk,type,email',
            'requester:id,name',
            'approver:id,name',
        ]);
    }

    public function cancelLeave(EmployeeLeaveRequest $leave, int $userId): EmployeeLeaveRequest
    {
        if (!in_array($leave->status, ['pending', 'approved'], true)) {
            throw ValidationException::withMessages([
                'status' => ['Pengajuan ini tidak dapat dibatalkan.'],
            ]);
        }

        $wasApproved = $leave->status === 'approved';

        $leave->update([
            'status' => 'cancelled',
            'approved_by' => $userId,
            'approved_at' => now(),
            'notes' => trim(($leave->notes ? $leave->notes . "\n" : '') . 'Dibatalkan.'),
        ]);

        if ($wasApproved) {
            $this->clearLeaveAttendance($leave);
        }

        return $leave->fresh([
            'employee:id,name,nip,nuptk,type,email',
            'requester:id,name',
            'approver:id,name',
        ]);
    }

    public function listDecrees(int $institutionId, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = EmployeeDecree::with([
            'employee:id,name,nip,nuptk,type,email',
            'creator:id,name',
        ])
            ->where('institution_id', $institutionId)
            ->orderByDesc('decree_date')
            ->orderByDesc('id');

        if (!empty($filters['decree_type'])) {
            $query->where('decree_type', $filters['decree_type']);
        }
        if (!empty($filters['employee_id'])) {
            $query->where('employee_id', $filters['employee_id']);
        }
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('number', 'like', "%{$search}%")
                    ->orWhere('title', 'like', "%{$search}%")
                    ->orWhereHas('employee', function ($eq) use ($search) {
                        $eq->where('name', 'like', "%{$search}%")
                            ->orWhere('nip', 'like', "%{$search}%");
                    });
            });
        }

        return $query->paginate($perPage);
    }

    public function createDecree(int $institutionId, array $data, int $userId, ?UploadedFile $file = null): EmployeeDecree
    {
        $employee = Employee::where('institution_id', $institutionId)->findOrFail($data['employee_id']);

        $payload = [
            'institution_id' => $institutionId,
            'employee_id' => $employee->id,
            'decree_type' => $data['decree_type'],
            'number' => $data['number'],
            'title' => $data['title'],
            'decree_date' => $data['decree_date'],
            'effective_date' => $data['effective_date'] ?? null,
            'end_date' => $data['end_date'] ?? null,
            'description' => $data['description'] ?? null,
            'notes' => $data['notes'] ?? null,
            'created_by' => $userId,
        ];

        if ($file) {
            $path = $file->store("employee_decrees/{$employee->id}", 'public');
            $payload['file_path'] = $path;
            $payload['file_name'] = $file->getClientOriginalName();
            $payload['file_size'] = $file->getSize();
            $payload['mime_type'] = $file->getMimeType();
        }

        return EmployeeDecree::create($payload)->load([
            'employee:id,name,nip,nuptk,type,email',
            'creator:id,name',
        ]);
    }

    public function updateDecree(EmployeeDecree $decree, array $data, ?UploadedFile $file = null): EmployeeDecree
    {
        if (isset($data['employee_id']) && (int) $data['employee_id'] !== (int) $decree->employee_id) {
            Employee::where('institution_id', $decree->institution_id)->findOrFail($data['employee_id']);
        }

        $payload = collect($data)->only([
            'employee_id', 'decree_type', 'number', 'title', 'decree_date',
            'effective_date', 'end_date', 'description', 'notes',
        ])->all();

        if ($file) {
            if ($decree->file_path && Storage::disk('public')->exists($decree->file_path)) {
                Storage::disk('public')->delete($decree->file_path);
            }
            $path = $file->store("employee_decrees/{$decree->employee_id}", 'public');
            $payload['file_path'] = $path;
            $payload['file_name'] = $file->getClientOriginalName();
            $payload['file_size'] = $file->getSize();
            $payload['mime_type'] = $file->getMimeType();
        }

        $decree->update($payload);

        return $decree->fresh([
            'employee:id,name,nip,nuptk,type,email',
            'creator:id,name',
        ]);
    }

    public function deleteDecree(EmployeeDecree $decree): void
    {
        if ($decree->file_path && Storage::disk('public')->exists($decree->file_path)) {
            Storage::disk('public')->delete($decree->file_path);
        }
        $decree->delete();
    }

    public function listStructuralAssignments(int $institutionId, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = EmployeeStructuralPosition::with([
            'employee:id,name,nip,nuptk,type,email',
            'position',
            'decree:id,number,title,decree_date',
            'creator:id,name',
        ])
            ->where('institution_id', $institutionId)
            ->orderByDesc('started_at')
            ->orderByDesc('id');

        if (!empty($filters['structural_position_id'])) {
            $query->where('structural_position_id', $filters['structural_position_id']);
        }
        if (!empty($filters['employee_id'])) {
            $query->where('employee_id', $filters['employee_id']);
        }
        if (($filters['active_only'] ?? null) === true || ($filters['active_only'] ?? null) === '1') {
            $query->active();
        }
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->whereHas('employee', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('nip', 'like', "%{$search}%");
            });
        }

        return $query->paginate($perPage);
    }

    public function assignStructuralPosition(int $institutionId, array $data, int $userId): EmployeeStructuralPosition
    {
        return DB::transaction(function () use ($institutionId, $data, $userId) {
            $employee = Employee::where('institution_id', $institutionId)->findOrFail($data['employee_id']);
            $position = StructuralPosition::active()->findOrFail($data['structural_position_id']);

            if (!empty($data['employee_decree_id'])) {
                EmployeeDecree::where('institution_id', $institutionId)
                    ->where('employee_id', $employee->id)
                    ->findOrFail($data['employee_decree_id']);
            }

            // End previous active holder of the same structural position at this institution.
            $previous = EmployeeStructuralPosition::with(['employee', 'position'])
                ->where('institution_id', $institutionId)
                ->where('structural_position_id', $position->id)
                ->where(function ($q) {
                    $q->whereNull('ended_at')->orWhere('ended_at', '>=', now()->toDateString());
                })
                ->get();

            EmployeeStructuralPosition::whereIn('id', $previous->pluck('id'))
                ->update([
                    'ended_at' => date('Y-m-d', strtotime($data['started_at'] . ' -1 day')),
                ]);

            foreach ($previous as $old) {
                if ((int) $old->employee_id === (int) $employee->id) {
                    continue;
                }
                $this->structuralDutySync->revoke($old->employee, $position->key);
            }

            $assignment = EmployeeStructuralPosition::create([
                'institution_id' => $institutionId,
                'employee_id' => $employee->id,
                'structural_position_id' => $position->id,
                'employee_decree_id' => $data['employee_decree_id'] ?? null,
                'started_at' => $data['started_at'],
                'ended_at' => $data['ended_at'] ?? null,
                'decree_number' => $data['decree_number'] ?? null,
                'notes' => $data['notes'] ?? null,
                'created_by' => $userId,
            ])->load([
                'employee:id,name,nip,nuptk,type,email',
                'position',
                'decree:id,number,title,decree_date',
                'creator:id,name',
            ]);

            $this->structuralDutySync->grant($employee, $position->key, $data['started_at']);

            if ($position->key === 'kepala_sekolah') {
                Institution::find($institutionId)?->syncPrincipalCache();
            }

            return $assignment;
        });
    }

    public function endStructuralAssignment(EmployeeStructuralPosition $assignment, ?string $endedAt = null, ?string $notes = null): EmployeeStructuralPosition
    {
        if ($assignment->ended_at && $assignment->ended_at->lt(now()->startOfDay())) {
            throw ValidationException::withMessages([
                'ended_at' => ['Penugasan jabatan ini sudah berakhir.'],
            ]);
        }

        $ended = $endedAt ?: now()->toDateString();
        if ($ended < $assignment->started_at->toDateString()) {
            throw ValidationException::withMessages([
                'ended_at' => ['Tanggal berakhir tidak boleh sebelum tanggal mulai.'],
            ]);
        }

        $assignment->update([
            'ended_at' => $ended,
            'notes' => $notes !== null
                ? trim(($assignment->notes ? $assignment->notes . "\n" : '') . $notes)
                : $assignment->notes,
        ]);

        $assignment->loadMissing(['employee', 'position']);
        if ($assignment->employee && $assignment->position?->key) {
            $this->structuralDutySync->revoke($assignment->employee, $assignment->position->key);
        }

        if ($assignment->position?->key === 'kepala_sekolah') {
            Institution::find($assignment->institution_id)?->syncPrincipalCache();
        }

        return $assignment->fresh([
            'employee:id,name,nip,nuptk,type,email',
            'position',
            'decree:id,number,title,decree_date',
            'creator:id,name',
        ]);
    }

    public function listStructuralPositions(): Collection
    {
        return StructuralPosition::active()->orderBy('sort_order')->orderBy('label')->get();
    }

    public function applyLeaveAttendance(EmployeeLeaveRequest $leave): void
    {
        if ($leave->status !== 'approved' || !$leave->start_date || !$leave->end_date) {
            return;
        }

        $marker = $this->leaveAttendanceMarker($leave->id);
        $label = $leave->leave_type_label;
        $cursor = $leave->start_date->copy()->startOfDay();
        $end = $leave->end_date->copy()->startOfDay();

        while ($cursor->lte($end)) {
            if (!$cursor->isWeekend()) {
                EmployeeAttendance::updateOrCreate(
                    [
                        'institution_id' => $leave->institution_id,
                        'employee_id' => $leave->employee_id,
                        'date' => $cursor->toDateString(),
                    ],
                    [
                        'status' => EmployeeAttendance::STATUS_CUTI,
                        'check_in_time' => null,
                        'check_out_time' => null,
                        'notes' => $label . ' ' . $marker,
                    ]
                );
            }
            $cursor->addDay();
        }
    }

    public function clearLeaveAttendance(EmployeeLeaveRequest $leave): void
    {
        if (!$leave->start_date || !$leave->end_date) {
            return;
        }

        $marker = $this->leaveAttendanceMarker($leave->id);

        EmployeeAttendance::query()
            ->where('institution_id', $leave->institution_id)
            ->where('employee_id', $leave->employee_id)
            ->where('status', EmployeeAttendance::STATUS_CUTI)
            ->whereDate('date', '>=', $leave->start_date->toDateString())
            ->whereDate('date', '<=', $leave->end_date->toDateString())
            ->where('notes', 'like', '%' . $marker . '%')
            ->forceDelete();
    }

    protected function leaveAttendanceMarker(int $leaveId): string
    {
        return '[leave:' . $leaveId . ']';
    }

    /**
     * Unified career timeline for an employee.
     *
     * @return array<int, array<string, mixed>>
     */
    public function careerHistory(Employee $employee): array
    {
        $items = [];

        $leaves = EmployeeLeaveRequest::where('employee_id', $employee->id)
            ->where('status', 'approved')
            ->orderByDesc('start_date')
            ->get();

        foreach ($leaves as $leave) {
            $items[] = [
                'type' => 'leave',
                'type_label' => 'Cuti',
                'date' => $leave->start_date?->toDateString(),
                'end_date' => $leave->end_date?->toDateString(),
                'title' => $leave->leave_type_label,
                'subtitle' => $leave->reason,
                'meta' => [
                    'status' => $leave->status,
                    'duration_days' => $leave->duration_days,
                ],
                'ref_id' => $leave->id,
            ];
        }

        $decrees = EmployeeDecree::where('employee_id', $employee->id)
            ->orderByDesc('decree_date')
            ->get();

        foreach ($decrees as $decree) {
            $items[] = [
                'type' => 'decree',
                'type_label' => 'SK',
                'date' => $decree->decree_date?->toDateString(),
                'end_date' => $decree->end_date?->toDateString(),
                'title' => $decree->title,
                'subtitle' => $decree->number . ' · ' . $decree->decree_type_label,
                'meta' => [
                    'decree_type' => $decree->decree_type,
                    'has_file' => (bool) $decree->file_path,
                ],
                'ref_id' => $decree->id,
            ];
        }

        $positions = EmployeeStructuralPosition::with('position')
            ->where('employee_id', $employee->id)
            ->orderByDesc('started_at')
            ->get();

        foreach ($positions as $pos) {
            $items[] = [
                'type' => 'structural_position',
                'type_label' => 'Jabatan Struktural',
                'date' => $pos->started_at?->toDateString(),
                'end_date' => $pos->ended_at?->toDateString(),
                'title' => $pos->position?->label ?? 'Jabatan',
                'subtitle' => $pos->decree_number ? 'SK: ' . $pos->decree_number : null,
                'meta' => [
                    'is_active' => $pos->is_active,
                    'position_key' => $pos->position?->key,
                ],
                'ref_id' => $pos->id,
            ];
        }

        $mutations = TeacherMutation::where('employee_id', $employee->id)
            ->orderByDesc('approved_at')
            ->orderByDesc('id')
            ->get();

        foreach ($mutations as $mutation) {
            $from = $mutation->origin_school_name ?: ('Institusi #' . $mutation->origin_institution_id);
            $to = $mutation->target_school_name ?: ('Institusi #' . $mutation->target_institution_id);
            $items[] = [
                'type' => 'mutation',
                'type_label' => 'Mutasi',
                'date' => optional($mutation->approved_at)->toDateString()
                    ?? optional($mutation->created_at)->toDateString(),
                'end_date' => null,
                'title' => 'Mutasi Guru',
                'subtitle' => $from . ' → ' . $to,
                'meta' => [
                    'status' => $mutation->status,
                    'initiated_by' => $mutation->initiated_by,
                ],
                'ref_id' => $mutation->id,
            ];
        }

        if ($employee->join_date) {
            $items[] = [
                'type' => 'join',
                'type_label' => 'Kepegawaian',
                'date' => $employee->join_date->toDateString(),
                'end_date' => null,
                'title' => 'Bergabung',
                'subtitle' => $employee->employment_status,
                'meta' => [
                    'status' => $employee->status,
                ],
                'ref_id' => $employee->id,
            ];
        }

        usort($items, function ($a, $b) {
            $da = $a['date'] ?? '0000-00-00';
            $db = $b['date'] ?? '0000-00-00';
            if ($da === $db) {
                return ($b['ref_id'] ?? 0) <=> ($a['ref_id'] ?? 0);
            }

            return strcmp($db, $da);
        });

        return $items;
    }
}
