<?php

namespace App\Services;

use App\Models\EmployeeAttendance;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class EmployeeAttendanceService
{
    /**
     * List employee attendances for institution with filters.
     */
    public function listForInstitution(int $institutionId, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = EmployeeAttendance::with(['employee:id,institution_id,nip,name,type,gender'])
            ->forInstitution($institutionId)
            ->orderBy('date', 'desc')
            ->orderBy('employee_id')
            ->orderBy('id', 'desc');

        if (!empty($filters['employee_id'])) {
            $query->forEmployee((int) $filters['employee_id']);
        }
        if (!empty($filters['date_from'])) {
            $query->dateFrom($filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $query->dateTo($filters['date_to']);
        }
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->paginate($perPage);
    }

    /**
     * Create or update a single employee attendance (unique by institution, employee, date).
     */
    public function upsert(int $institutionId, array $data): EmployeeAttendance
    {
        $employeeId = (int) $data['employee_id'];
        $date = $data['date'];

        $att = EmployeeAttendance::updateOrCreate(
            [
                'institution_id' => $institutionId,
                'employee_id' => $employeeId,
                'date' => $date,
            ],
            [
                'status' => $data['status'] ?? 'hadir',
                'check_in_time' => isset($data['check_in_time']) ? $data['check_in_time'] : null,
                'check_out_time' => isset($data['check_out_time']) ? $data['check_out_time'] : null,
                'notes' => $data['notes'] ?? null,
            ]
        );

        return $att->load('employee:id,institution_id,nip,name,type,gender');
    }

    /**
     * Bulk upsert attendances for one date (many employees).
     */
    public function bulkUpsert(int $institutionId, string $date, array $attendances): \Illuminate\Support\Collection
    {
        return DB::transaction(function () use ($institutionId, $date, $attendances) {
            $saved = collect();
            foreach ($attendances as $row) {
                $employeeId = (int) ($row['employee_id'] ?? 0);
                if (!$employeeId) {
                    continue;
                }
                $att = EmployeeAttendance::updateOrCreate(
                    [
                        'institution_id' => $institutionId,
                        'employee_id' => $employeeId,
                        'date' => $date,
                    ],
                    [
                        'status' => $row['status'] ?? 'hadir',
                        'check_in_time' => $row['check_in_time'] ?? null,
                        'check_out_time' => $row['check_out_time'] ?? null,
                        'notes' => $row['notes'] ?? null,
                    ]
                );
                $att->load('employee:id,institution_id,nip,name,type,gender');
                $saved->push($att);
            }
            return $saved;
        });
    }

    /**
     * Update a single employee attendance record.
     */
    public function update(EmployeeAttendance $attendance, array $data): EmployeeAttendance
    {
        $attendance->update($data);
        return $attendance->fresh(['employee:id,institution_id,nip,name,type,gender']);
    }
}
