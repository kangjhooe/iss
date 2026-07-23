<?php

namespace App\Services;

use App\Models\Employee;
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

    /**
     * Aggregate employee attendance rekap for reports.
     *
     * @param  array{employee_id?:int|string,date_from?:string,date_to?:string,status?:string}  $filters
     * @return array{rows: array<int, array>, totals: array<string, int|float>, meta: array}
     */
    public function buildRekap(int $institutionId, array $filters = []): array
    {
        $statusKeys = array_keys(EmployeeAttendance::STATUSES);

        $query = EmployeeAttendance::query()
            ->with(['employee:id,institution_id,nip,name,type,gender'])
            ->forInstitution($institutionId);

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

        $records = $query->orderBy('employee_id')->orderBy('date')->get();
        $byEmployee = $records->groupBy('employee_id');

        if (!empty($filters['employee_id'])) {
            $employees = Employee::query()
                ->where('institution_id', $institutionId)
                ->where('id', (int) $filters['employee_id'])
                ->orderBy('name')
                ->get(['id', 'nip', 'name', 'type']);
        } elseif ($byEmployee->isNotEmpty()) {
            $employees = Employee::query()
                ->where('institution_id', $institutionId)
                ->whereIn('id', $byEmployee->keys())
                ->orderBy('name')
                ->get(['id', 'nip', 'name', 'type']);
        } else {
            $employees = collect();
        }

        $totals = array_fill_keys($statusKeys, 0);
        $totals['tercatat'] = 0;

        $rows = $employees->map(function (Employee $employee) use ($byEmployee, $statusKeys, &$totals) {
            $atts = $byEmployee->get($employee->id, collect());
            $counts = array_fill_keys($statusKeys, 0);
            foreach ($atts as $att) {
                $status = $att->status;
                if (isset($counts[$status])) {
                    $counts[$status]++;
                }
            }
            $tercatat = (int) $atts->count();
            $hadir = $counts['hadir'] ?? 0;
            $persen = $tercatat > 0 ? round(($hadir / $tercatat) * 100, 1) : 0.0;

            foreach ($statusKeys as $key) {
                $totals[$key] += $counts[$key];
            }
            $totals['tercatat'] += $tercatat;

            return [
                'employee_id' => $employee->id,
                'nip' => $employee->nip,
                'name' => $employee->name,
                'type' => $employee->type,
                'counts' => $counts,
                'tercatat' => $tercatat,
                'persentase_hadir' => $persen,
            ];
        })->values()->all();

        $totals['persentase_hadir'] = $totals['tercatat'] > 0
            ? round(($totals['hadir'] / $totals['tercatat']) * 100, 1)
            : 0.0;

        $employeeName = null;
        if (!empty($filters['employee_id'])) {
            $employeeName = Employee::where('id', (int) $filters['employee_id'])->value('name');
        }

        return [
            'rows' => $rows,
            'totals' => $totals,
            'meta' => [
                'employee_count' => count($rows),
                'employee_name' => $employeeName,
                'date_from' => $filters['date_from'] ?? null,
                'date_to' => $filters['date_to'] ?? null,
                'status_filter' => $filters['status'] ?? null,
                'status_labels' => EmployeeAttendance::STATUSES,
            ],
        ];
    }
}
