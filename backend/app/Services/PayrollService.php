<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\EmployeeAttendance;
use App\Models\EmployeeLeaveRequest;
use App\Models\EmployeeStructuralPosition;
use App\Models\PayrollComponent;
use App\Models\PayrollEmployeeComponent;
use App\Models\PayrollEmployeeProfile;
use App\Models\PayrollPeriod;
use App\Models\PayrollPositionAllowance;
use App\Models\PayrollRun;
use App\Models\PayrollSlip;
use App\Models\PayrollSlipLine;
use App\Models\StructuralPosition;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PayrollService
{
    /**
     * @return array<int, array{code:string,name:string,type:string,calc_mode:string,default_amount:float,is_system:bool,sort_order:int}>
     */
    public function defaultComponentDefinitions(): array
    {
        return [
            [
                'code' => PayrollComponent::CODE_BASE_SALARY,
                'name' => 'Gaji Pokok',
                'type' => PayrollComponent::TYPE_EARNING,
                'calc_mode' => PayrollComponent::CALC_FIXED,
                'default_amount' => 0,
                'is_system' => true,
                'sort_order' => 10,
            ],
            [
                'code' => PayrollComponent::CODE_TRANSPORT,
                'name' => 'Tunjangan Transport',
                'type' => PayrollComponent::TYPE_EARNING,
                'calc_mode' => PayrollComponent::CALC_FIXED,
                'default_amount' => 300000,
                'is_system' => false,
                'sort_order' => 20,
            ],
            [
                'code' => PayrollComponent::CODE_STRUCTURAL,
                'name' => 'Tunjangan Jabatan',
                'type' => PayrollComponent::TYPE_EARNING,
                'calc_mode' => PayrollComponent::CALC_STRUCTURAL_POSITION,
                'default_amount' => 0,
                'is_system' => true,
                'sort_order' => 25,
            ],
            [
                'code' => PayrollComponent::CODE_THR,
                'name' => 'THR',
                'description' => 'Tunjangan Hari Raya — dihitung dari gaji pokok × pengali (default 1 bulan)',
                'type' => PayrollComponent::TYPE_EARNING,
                'calc_mode' => PayrollComponent::CALC_THR,
                'default_amount' => 1,
                'is_system' => true,
                'sort_order' => 30,
            ],
            [
                'code' => PayrollComponent::CODE_ALPHA,
                'name' => 'Potongan Alpha',
                'type' => PayrollComponent::TYPE_DEDUCTION,
                'calc_mode' => PayrollComponent::CALC_PER_ALPHA_DAY,
                'default_amount' => 0,
                'is_system' => true,
                'sort_order' => 90,
            ],
        ];
    }

    public function ensureDefaultComponents(int $institutionId): void
    {
        foreach ($this->defaultComponentDefinitions() as $def) {
            PayrollComponent::firstOrCreate(
                [
                    'institution_id' => $institutionId,
                    'code' => $def['code'],
                ],
                array_merge($def, ['institution_id' => $institutionId, 'is_active' => true])
            );
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createComponent(int $institutionId, array $data): PayrollComponent
    {
        $this->ensureDefaultComponents($institutionId);

        return PayrollComponent::create([
            'institution_id' => $institutionId,
            'code' => strtoupper(trim((string) $data['code'])),
            'name' => trim((string) $data['name']),
            'description' => $data['description'] ?? null,
            'type' => $data['type'],
            'calc_mode' => $data['calc_mode'] ?? PayrollComponent::CALC_FIXED,
            'default_amount' => (float) ($data['default_amount'] ?? 0),
            'is_system' => false,
            'is_active' => $data['is_active'] ?? true,
            'sort_order' => (int) ($data['sort_order'] ?? 50),
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateComponent(PayrollComponent $component, array $data): PayrollComponent
    {
        if ($component->is_system && isset($data['code']) && $data['code'] !== $component->code) {
            throw ValidationException::withMessages([
                'code' => 'Komponen sistem tidak boleh mengubah kode.',
            ]);
        }

        $component->fill([
            'name' => $data['name'] ?? $component->name,
            'description' => array_key_exists('description', $data) ? $data['description'] : $component->description,
            'type' => $data['type'] ?? $component->type,
            'calc_mode' => $data['calc_mode'] ?? $component->calc_mode,
            'default_amount' => array_key_exists('default_amount', $data)
                ? (float) $data['default_amount']
                : $component->default_amount,
            'is_active' => array_key_exists('is_active', $data) ? (bool) $data['is_active'] : $component->is_active,
            'sort_order' => array_key_exists('sort_order', $data) ? (int) $data['sort_order'] : $component->sort_order,
        ]);

        if (! $component->is_system && ! empty($data['code'])) {
            $component->code = strtoupper(trim((string) $data['code']));
        }

        $component->save();

        return $component->fresh();
    }

    public function deleteComponent(PayrollComponent $component): void
    {
        if ($component->is_system) {
            throw ValidationException::withMessages([
                'component' => 'Komponen sistem tidak dapat dihapus.',
            ]);
        }

        $component->delete();
    }

    /**
     * @return Collection<int, array{id:?int,structural_position_id:int,key:string,label:string,amount:float,is_active:bool}>
     */
    public function listPositionAllowances(int $institutionId): Collection
    {
        $positions = StructuralPosition::active()->orderBy('sort_order')->orderBy('label')->get();
        $configured = PayrollPositionAllowance::forInstitution($institutionId)
            ->get()
            ->keyBy('structural_position_id');

        return $positions->map(function (StructuralPosition $position) use ($configured) {
            $row = $configured->get($position->id);

            return [
                'id' => $row?->id,
                'structural_position_id' => $position->id,
                'key' => $position->key,
                'label' => $position->label,
                'amount' => (float) ($row?->amount ?? 0),
                'is_active' => $row ? (bool) $row->is_active : false,
            ];
        });
    }

    /**
     * @param  array<int, array{structural_position_id:int,amount?:float,is_active?:bool}>  $rows
     */
    public function syncPositionAllowances(int $institutionId, array $rows): Collection
    {
        foreach ($rows as $row) {
            $positionId = (int) ($row['structural_position_id'] ?? 0);
            if ($positionId <= 0) {
                continue;
            }

            $position = StructuralPosition::active()->find($positionId);
            if (! $position) {
                continue;
            }

            $amount = round((float) ($row['amount'] ?? 0), 2);
            $isActive = array_key_exists('is_active', $row) ? (bool) $row['is_active'] : $amount > 0;

            if ($amount <= 0 && ! $isActive) {
                PayrollPositionAllowance::query()
                    ->where('institution_id', $institutionId)
                    ->where('structural_position_id', $positionId)
                    ->delete();

                continue;
            }

            PayrollPositionAllowance::updateOrCreate(
                [
                    'institution_id' => $institutionId,
                    'structural_position_id' => $positionId,
                ],
                [
                    'amount' => max(0, $amount),
                    'is_active' => $isActive,
                ]
            );
        }

        return $this->listPositionAllowances($institutionId);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function upsertEmployeeProfile(int $institutionId, array $data): PayrollEmployeeProfile
    {
        $this->ensureDefaultComponents($institutionId);

        $employeeId = (int) $data['employee_id'];
        $employee = Employee::query()
            ->where('id', $employeeId)
            ->where(function ($q) use ($institutionId) {
                $q->where('institution_id', $institutionId)
                    ->orWhereHas('assignments', fn ($a) => $a->where('institution_id', $institutionId)->where('status', 'approved'));
            })
            ->first();

        if (! $employee) {
            throw ValidationException::withMessages([
                'employee_id' => 'Pegawai tidak ditemukan di institusi ini.',
            ]);
        }

        $profile = PayrollEmployeeProfile::updateOrCreate(
            [
                'institution_id' => $institutionId,
                'employee_id' => $employeeId,
            ],
            [
                'base_salary' => (float) ($data['base_salary'] ?? 0),
                'payment_method' => $data['payment_method'] ?? 'transfer',
                'bank_name' => $data['bank_name'] ?? null,
                'bank_account' => $data['bank_account'] ?? null,
                'effective_from' => $data['effective_from'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]
        );

        if (! empty($data['components']) && is_array($data['components'])) {
            $this->syncEmployeeComponents($institutionId, $employeeId, $data['components']);
        }

        return $profile->load(['employee:id,institution_id,nip,name,type,employment_status,status']);
    }

    /**
     * @param  array<int, array{component_id:int,amount?:float|null,is_active?:bool}>  $components
     */
    public function syncEmployeeComponents(int $institutionId, int $employeeId, array $components): void
    {
        foreach ($components as $row) {
            $componentId = (int) ($row['component_id'] ?? 0);
            if ($componentId <= 0) {
                continue;
            }

            $component = PayrollComponent::forInstitution($institutionId)->find($componentId);
            if (! $component) {
                continue;
            }

            PayrollEmployeeComponent::updateOrCreate(
                [
                    'employee_id' => $employeeId,
                    'component_id' => $componentId,
                ],
                [
                    'institution_id' => $institutionId,
                    'amount' => array_key_exists('amount', $row) ? $row['amount'] : null,
                    'is_active' => array_key_exists('is_active', $row) ? (bool) $row['is_active'] : true,
                ]
            );
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createPeriod(int $institutionId, array $data): PayrollPeriod
    {
        $year = (int) $data['year'];
        $month = (int) $data['month'];
        $start = Carbon::create($year, $month, 1)->startOfMonth();
        $end = $start->copy()->endOfMonth();

        $exists = PayrollPeriod::forInstitution($institutionId)
            ->where('year', $year)
            ->where('month', $month)
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'month' => 'Periode gaji untuk bulan ini sudah ada.',
            ]);
        }

        $workingDays = isset($data['working_days'])
            ? (int) $data['working_days']
            : $this->countWeekdays($start, $end);

        return PayrollPeriod::create([
            'institution_id' => $institutionId,
            'year' => $year,
            'month' => $month,
            'label' => $data['label'] ?? $start->locale('id')->isoFormat('MMMM YYYY'),
            'start_date' => $start->toDateString(),
            'end_date' => $end->toDateString(),
            'working_days' => $workingDays,
            'status' => PayrollPeriod::STATUS_OPEN,
        ]);
    }

    public function closePeriod(PayrollPeriod $period): PayrollPeriod
    {
        $hasOpenRuns = PayrollRun::query()
            ->where('period_id', $period->id)
            ->whereIn('status', [PayrollRun::STATUS_DRAFT, PayrollRun::STATUS_FINALIZED])
            ->exists();

        if ($hasOpenRuns) {
            throw ValidationException::withMessages([
                'period' => 'Tutup atau selesaikan semua proses gaji (draft/final) sebelum menutup periode.',
            ]);
        }

        $period->update(['status' => PayrollPeriod::STATUS_CLOSED]);

        return $period->fresh();
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{run: PayrollRun, created: int, skipped: int, skipped_employees: array<int, string>}
     */
    public function generateRun(int $institutionId, int $userId, array $payload): array
    {
        $this->ensureDefaultComponents($institutionId);

        $period = PayrollPeriod::forInstitution($institutionId)->findOrFail($payload['period_id']);
        if ($period->status === PayrollPeriod::STATUS_CLOSED) {
            throw ValidationException::withMessages([
                'period_id' => 'Periode sudah ditutup.',
            ]);
        }

        $employees = $this->resolveEmployeesForRun($institutionId, $payload);
        if ($employees->isEmpty()) {
            throw ValidationException::withMessages([
                'employee_ids' => 'Tidak ada pegawai aktif dengan profil gaji.',
            ]);
        }

        $components = PayrollComponent::forInstitution($institutionId)
            ->active()
            ->orderBy('sort_order')
            ->get();

        $batchKey = (string) Str::uuid();
        $created = 0;
        $skipped = 0;
        $skippedEmployees = [];

        $run = DB::transaction(function () use (
            $institutionId,
            $userId,
            $period,
            $payload,
            $employees,
            $components,
            $batchKey,
            &$created,
            &$skipped,
            &$skippedEmployees
        ) {
            $run = PayrollRun::create([
                'institution_id' => $institutionId,
                'period_id' => $period->id,
                'batch_key' => $batchKey,
                'label' => $payload['label'] ?? ('Gaji ' . $period->label),
                'status' => PayrollRun::STATUS_DRAFT,
                'employee_filter' => $this->extractEmployeeFilter($payload),
                'generated_at' => now(),
                'created_by' => $userId,
                'notes' => $payload['notes'] ?? null,
            ]);

            foreach ($employees as $employee) {
                $alreadyInPeriod = PayrollSlip::query()
                    ->where('period_id', $period->id)
                    ->where('employee_id', $employee->id)
                    ->exists();

                if ($alreadyInPeriod) {
                    $skipped++;
                    $skippedEmployees[] = $employee->name . ' (sudah ada di periode ini)';

                    continue;
                }

                $profile = PayrollEmployeeProfile::forInstitution($institutionId)
                    ->where('employee_id', $employee->id)
                    ->first();

                if (! $profile || (float) $profile->base_salary <= 0) {
                    $skipped++;
                    $skippedEmployees[] = $employee->name;

                    continue;
                }

                $attendance = $this->buildAttendanceSnapshot($institutionId, $employee->id, $period);
                $lines = $this->calculateLines(
                    $institutionId,
                    $employee,
                    $profile,
                    $components,
                    $period,
                    $attendance,
                    $this->extractGenerateOptions($payload)
                );

                if ($lines->isEmpty()) {
                    $skipped++;
                    $skippedEmployees[] = $employee->name;

                    continue;
                }

                $totals = $this->summarizeLines($lines);

                $slip = PayrollSlip::create([
                    'institution_id' => $institutionId,
                    'run_id' => $run->id,
                    'period_id' => $period->id,
                    'employee_id' => $employee->id,
                    'gross' => $totals['gross'],
                    'total_deductions' => $totals['total_deductions'],
                    'net' => $totals['net'],
                    'attendance_snapshot' => $attendance,
                    'status' => PayrollSlip::STATUS_DRAFT,
                ]);

                foreach ($lines as $index => $line) {
                    PayrollSlipLine::create([
                        'slip_id' => $slip->id,
                        'component_id' => $line['component_id'],
                        'label' => $line['label'],
                        'type' => $line['type'],
                        'amount' => $line['amount'],
                        'is_manual_override' => false,
                        'source' => $line['source'],
                        'sort_order' => $index + 1,
                    ]);
                }

                $created++;
            }

            if ($created === 0) {
                throw ValidationException::withMessages([
                    'employee_ids' => 'Tidak ada slip gaji yang dibuat. Periksa profil gaji pegawai atau duplikasi periode.',
                ]);
            }

            return $run;
        });

        return [
            'run' => $run->load(['period', 'creator:id,name']),
            'created' => $created,
            'skipped' => $skipped,
            'skipped_employees' => $skippedEmployees,
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{include_thr: bool}
     */
    protected function extractGenerateOptions(array $payload): array
    {
        return [
            'include_thr' => (bool) ($payload['include_thr'] ?? false),
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function extractEmployeeFilter(array $payload): ?array
    {
        $filter = [];
        if (! empty($payload['employee_ids'])) {
            $filter['employee_ids'] = array_values(array_map('intval', (array) $payload['employee_ids']));
        }
        if (! empty($payload['employee_types'])) {
            $filter['employee_types'] = array_values((array) $payload['employee_types']);
        }

        $options = $this->extractGenerateOptions($payload);
        if ($options['include_thr'] || ! empty($payload['include_thr'])) {
            $filter['options'] = $options;
        }

        return $filter === [] ? null : $filter;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function resolveEmployeesForRun(int $institutionId, array $payload): Collection
    {
        $query = Employee::query()
            ->where('status', 'Aktif')
            ->where(function ($q) use ($institutionId) {
                $q->where('institution_id', $institutionId)
                    ->orWhereHas('assignments', fn ($a) => $a->where('institution_id', $institutionId)->where('status', 'approved'));
            })
            ->orderBy('name');

        if (! empty($payload['employee_ids']) && is_array($payload['employee_ids'])) {
            $query->whereIn('id', array_map('intval', $payload['employee_ids']));
        }

        if (! empty($payload['employee_types']) && is_array($payload['employee_types'])) {
            $query->whereIn('type', $payload['employee_types']);
        }

        return $query->get(['id', 'institution_id', 'nip', 'name', 'type', 'employment_status', 'status']);
    }

    /**
     * @return array<string, int|float>
     */
    public function buildAttendanceSnapshot(int $institutionId, int $employeeId, PayrollPeriod $period): array
    {
        $records = EmployeeAttendance::query()
            ->forInstitution($institutionId)
            ->forEmployee($employeeId)
            ->dateFrom($period->start_date->toDateString())
            ->dateTo($period->end_date->toDateString())
            ->get(['status']);

        $counts = [];
        foreach (array_keys(EmployeeAttendance::STATUSES) as $status) {
            $counts[$status] = 0;
        }

        foreach ($records as $record) {
            $key = $record->status;
            if (isset($counts[$key])) {
                $counts[$key]++;
            }
        }

        $unpaidLeaveDays = EmployeeLeaveRequest::query()
            ->where('institution_id', $institutionId)
            ->where('employee_id', $employeeId)
            ->where('status', 'approved')
            ->where('leave_type', 'tanpa_gaji')
            ->where(function ($q) use ($period) {
                $q->whereBetween('start_date', [$period->start_date, $period->end_date])
                    ->orWhereBetween('end_date', [$period->start_date, $period->end_date])
                    ->orWhere(function ($inner) use ($period) {
                        $inner->where('start_date', '<=', $period->start_date)
                            ->where('end_date', '>=', $period->end_date);
                    });
            })
            ->get()
            ->sum(fn (EmployeeLeaveRequest $leave) => $this->overlapDays(
                $leave->start_date,
                $leave->end_date,
                $period->start_date,
                $period->end_date
            ));

        $counts['tanpa_gaji'] = (int) $unpaidLeaveDays;
        $counts['working_days'] = (int) ($period->working_days ?: $this->countWeekdays($period->start_date, $period->end_date));

        return $counts;
    }

    protected function overlapDays($startA, $endA, $startB, $endB): int
    {
        $start = $startA->greaterThan($startB) ? $startA->copy() : $startB->copy();
        $end = $endA->lessThan($endB) ? $endA->copy() : $endB->copy();
        if ($start->greaterThan($end)) {
            return 0;
        }

        return $start->diffInDays($end) + 1;
    }

    /**
     * @param  Collection<int, PayrollComponent>  $components
     * @param  array<string, int|float>  $attendance
     * @return Collection<int, array{component_id:?int,label:string,type:string,amount:float,source:string}>
     */
    protected function calculateLines(
        int $institutionId,
        Employee $employee,
        PayrollEmployeeProfile $profile,
        Collection $components,
        PayrollPeriod $period,
        array $attendance,
        array $generateOptions = []
    ): Collection {
        $employeeOverrides = PayrollEmployeeComponent::query()
            ->where('institution_id', $institutionId)
            ->where('employee_id', $employee->id)
            ->where('is_active', true)
            ->get()
            ->keyBy('component_id');

        $lines = collect();
        $baseSalary = (float) $profile->base_salary;
        $workingDays = max(1, (int) ($attendance['working_days'] ?? 22));
        $alphaDays = (int) ($attendance[EmployeeAttendance::STATUS_ALPHA] ?? 0);
        $unpaidLeaveDays = (int) ($attendance['tanpa_gaji'] ?? 0);
        $dailyRate = $baseSalary / $workingDays;

        foreach ($components as $component) {
            if ($component->calc_mode === PayrollComponent::CALC_MANUAL) {
                $override = $employeeOverrides->get($component->id);
                if (! $override || $override->amount === null) {
                    continue;
                }
            }

            $amount = 0.0;
            $source = PayrollSlipLine::SOURCE_RULE;
            $lineLabel = $component->name;

            if ($component->code === PayrollComponent::CODE_BASE_SALARY) {
                $amount = $baseSalary;
            } elseif ($component->calc_mode === PayrollComponent::CALC_THR) {
                if (empty($generateOptions['include_thr'])) {
                    continue;
                }
                $multiplier = (float) ($component->default_amount > 0 ? $component->default_amount : 1);
                $amount = round($baseSalary * $multiplier, 2);
                $source = PayrollSlipLine::SOURCE_THR;
            } elseif ($component->calc_mode === PayrollComponent::CALC_STRUCTURAL_POSITION) {
                $resolved = $this->resolveStructuralAllowance($institutionId, $employee->id, $period);
                $amount = $resolved['amount'];
                $source = PayrollSlipLine::SOURCE_STRUCTURAL;
                if (! empty($resolved['labels'])) {
                    $lineLabel = $component->name . ' (' . implode(', ', $resolved['labels']) . ')';
                }
            } elseif ($component->calc_mode === PayrollComponent::CALC_PER_ALPHA_DAY) {
                $deductDays = $alphaDays + $unpaidLeaveDays;
                $amount = round($dailyRate * $deductDays, 2);
                $source = PayrollSlipLine::SOURCE_ATTENDANCE;
            } elseif ($component->calc_mode === PayrollComponent::CALC_MANUAL) {
                $override = $employeeOverrides->get($component->id);
                $amount = (float) ($override?->amount ?? 0);
                $source = PayrollSlipLine::SOURCE_MANUAL;
            } else {
                $override = $employeeOverrides->get($component->id);
                if ($override && $override->amount !== null) {
                    $amount = (float) $override->amount;
                } else {
                    $amount = (float) $component->default_amount;
                }
            }

            if ($amount <= 0 && $component->type === PayrollComponent::TYPE_DEDUCTION) {
                continue;
            }

            if ($amount <= 0 && $component->code !== PayrollComponent::CODE_BASE_SALARY) {
                continue;
            }

            $lines->push([
                'component_id' => $component->id,
                'label' => $lineLabel,
                'type' => $component->type,
                'amount' => round($amount, 2),
                'source' => $source,
            ]);
        }

        return $lines;
    }

    /**
     * @return array{amount: float, labels: array<int, string>}
     */
    protected function resolveStructuralAllowance(int $institutionId, int $employeeId, PayrollPeriod $period): array
    {
        $periodStart = $period->start_date->toDateString();
        $periodEnd = $period->end_date->toDateString();

        $positionIds = EmployeeStructuralPosition::query()
            ->where('institution_id', $institutionId)
            ->where('employee_id', $employeeId)
            ->where('started_at', '<=', $periodEnd)
            ->where(function ($q) use ($periodStart) {
                $q->whereNull('ended_at')
                    ->orWhere('ended_at', '>=', $periodStart);
            })
            ->pluck('structural_position_id')
            ->unique();

        if ($positionIds->isEmpty()) {
            return ['amount' => 0, 'labels' => []];
        }

        $allowances = PayrollPositionAllowance::forInstitution($institutionId)
            ->active()
            ->whereIn('structural_position_id', $positionIds)
            ->where('amount', '>', 0)
            ->with('structuralPosition:id,label')
            ->get();

        $labels = [];
        $total = 0.0;
        foreach ($allowances as $row) {
            $total += (float) $row->amount;
            if ($row->structuralPosition?->label) {
                $labels[] = $row->structuralPosition->label;
            }
        }

        return [
            'amount' => round($total, 2),
            'labels' => $labels,
        ];
    }

    /**
     * @param  Collection<int, array{type:string,amount:float}>  $lines
     * @return array{gross: float, total_deductions: float, net: float}
     */
    public function summarizeLines(Collection $lines): array
    {
        $gross = round($lines->where('type', PayrollComponent::TYPE_EARNING)->sum('amount'), 2);
        $totalDeductions = round($lines->where('type', PayrollComponent::TYPE_DEDUCTION)->sum('amount'), 2);
        $net = round($gross - $totalDeductions, 2);

        return [
            'gross' => $gross,
            'total_deductions' => $totalDeductions,
            'net' => max(0, $net),
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function updateSlip(PayrollSlip $slip, array $payload): PayrollSlip
    {
        $run = $slip->run;
        if (! $run || ! $run->isEditable()) {
            throw ValidationException::withMessages([
                'slip' => 'Slip tidak dapat diubah karena proses gaji sudah difinalisasi.',
            ]);
        }

        return DB::transaction(function () use ($slip, $payload) {
            if (array_key_exists('notes', $payload)) {
                $slip->notes = $payload['notes'];
            }

            if (! empty($payload['lines']) && is_array($payload['lines'])) {
                $existing = $slip->lines()->get()->keyBy('id');
                $validLineIds = $existing->keys()->map(fn ($id) => (int) $id)->all();
                $sort = 1;

                foreach ($payload['lines'] as $lineData) {
                    $lineId = isset($lineData['id']) ? (int) $lineData['id'] : null;
                    $amount = round((float) ($lineData['amount'] ?? 0), 2);
                    $label = trim((string) ($lineData['label'] ?? ''));
                    $type = $lineData['type'] ?? PayrollComponent::TYPE_EARNING;

                    if ($lineId && ! in_array($lineId, $validLineIds, true)) {
                        throw ValidationException::withMessages([
                            'lines' => 'Baris slip tidak valid.',
                        ]);
                    }

                    if ($lineId && $existing->has($lineId)) {
                        /** @var PayrollSlipLine $line */
                        $line = $existing->get($lineId);
                        $line->update([
                            'label' => $label !== '' ? $label : $line->label,
                            'type' => $type,
                            'amount' => $amount,
                            'is_manual_override' => true,
                            'source' => PayrollSlipLine::SOURCE_MANUAL,
                            'sort_order' => $sort++,
                        ]);
                    } elseif ($label !== '' && $amount > 0) {
                        PayrollSlipLine::create([
                            'slip_id' => $slip->id,
                            'component_id' => $lineData['component_id'] ?? null,
                            'label' => $label,
                            'type' => $type,
                            'amount' => $amount,
                            'is_manual_override' => true,
                            'source' => PayrollSlipLine::SOURCE_AD_HOC,
                            'sort_order' => $sort++,
                        ]);
                    }
                }

                if (! empty($payload['remove_line_ids']) && is_array($payload['remove_line_ids'])) {
                    $removeIds = array_map('intval', $payload['remove_line_ids']);
                    foreach ($removeIds as $removeId) {
                        if (! in_array($removeId, $validLineIds, true)) {
                            throw ValidationException::withMessages([
                                'remove_line_ids' => 'Baris slip tidak valid.',
                            ]);
                        }
                    }

                    PayrollSlipLine::query()
                        ->where('slip_id', $slip->id)
                        ->whereIn('id', $removeIds)
                        ->delete();
                }
            }

            $slip->load('lines');
            $totals = $this->summarizeLines($slip->lines->map(fn (PayrollSlipLine $line) => [
                'type' => $line->type,
                'amount' => (float) $line->amount,
            ]));

            $slip->update([
                'gross' => $totals['gross'],
                'total_deductions' => $totals['total_deductions'],
                'net' => $totals['net'],
                'notes' => $slip->notes,
            ]);

            return $slip->fresh(['lines', 'employee:id,nip,name,type,employment_status', 'period', 'run']);
        });
    }

    public function finalizeRun(PayrollRun $run): PayrollRun
    {
        if ($run->status !== PayrollRun::STATUS_DRAFT) {
            throw ValidationException::withMessages([
                'run' => 'Hanya proses draft yang dapat difinalisasi.',
            ]);
        }

        $run->load('period');

        if ($run->period && $run->period->status === PayrollPeriod::STATUS_CLOSED) {
            throw ValidationException::withMessages([
                'run' => 'Periode gaji sudah ditutup.',
            ]);
        }

        if ($run->slips()->count() === 0) {
            throw ValidationException::withMessages([
                'run' => 'Tidak ada slip gaji untuk difinalisasi.',
            ]);
        }

        $totalNet = (float) PayrollSlip::query()->where('run_id', $run->id)->sum('net');
        if ($totalNet <= 0) {
            throw ValidationException::withMessages([
                'run' => 'Total gaji neto nol; periksa slip sebelum finalisasi.',
            ]);
        }

        DB::transaction(function () use ($run) {
            $run->update([
                'status' => PayrollRun::STATUS_FINALIZED,
                'finalized_at' => now(),
            ]);

            PayrollSlip::where('run_id', $run->id)->update([
                'status' => PayrollSlip::STATUS_FINAL,
            ]);
        });

        return $run->fresh(['period', 'creator:id,name']);
    }

    public function markRunPaid(PayrollRun $run, int $userId): PayrollRun
    {
        if ($run->status === PayrollRun::STATUS_PAID) {
            return $run->fresh(['period', 'creator:id,name', 'financeExpense']);
        }

        if ($run->status !== PayrollRun::STATUS_FINALIZED) {
            throw ValidationException::withMessages([
                'run' => 'Proses gaji harus difinalisasi terlebih dahulu.',
            ]);
        }

        DB::transaction(function () use ($run, $userId) {
            $locked = PayrollRun::query()->whereKey($run->id)->lockForUpdate()->first();
            if (! $locked || $locked->status === PayrollRun::STATUS_PAID) {
                return;
            }

            $locked->update([
                'status' => PayrollRun::STATUS_PAID,
                'paid_at' => now(),
            ]);

            PayrollSlip::where('run_id', $locked->id)->update([
                'status' => PayrollSlip::STATUS_PAID,
            ]);

            $locked->refresh();
            app(FinanceExpenseService::class)->recordPayrollRunExpense($locked, $userId);
        });

        return $run->fresh(['period', 'creator:id,name', 'financeExpense']);
    }

    public function unpayRun(PayrollRun $run): PayrollRun
    {
        if ($run->status !== PayrollRun::STATUS_PAID) {
            throw ValidationException::withMessages([
                'run' => 'Hanya proses yang sudah dibayar yang dapat dibatalkan pembayarannya.',
            ]);
        }

        DB::transaction(function () use ($run) {
            $locked = PayrollRun::query()->whereKey($run->id)->lockForUpdate()->first();
            if (! $locked || $locked->status !== PayrollRun::STATUS_PAID) {
                return;
            }

            $locked->update([
                'status' => PayrollRun::STATUS_FINALIZED,
                'paid_at' => null,
            ]);

            PayrollSlip::where('run_id', $locked->id)->update([
                'status' => PayrollSlip::STATUS_FINAL,
            ]);

            app(FinanceExpenseService::class)->voidPayrollRunExpense($locked);
        });

        return $run->fresh(['period', 'creator:id,name', 'financeExpense']);
    }

    public function reopenRun(PayrollRun $run): PayrollRun
    {
        if ($run->status !== PayrollRun::STATUS_FINALIZED) {
            throw ValidationException::withMessages([
                'run' => 'Hanya proses final yang dapat dibuka kembali untuk diedit.',
            ]);
        }

        $run->load('period');
        if ($run->period && $run->period->status === PayrollPeriod::STATUS_CLOSED) {
            throw ValidationException::withMessages([
                'run' => 'Periode gaji sudah ditutup.',
            ]);
        }

        DB::transaction(function () use ($run) {
            $locked = PayrollRun::query()->whereKey($run->id)->lockForUpdate()->first();
            if (! $locked || $locked->status !== PayrollRun::STATUS_FINALIZED) {
                return;
            }

            $locked->update([
                'status' => PayrollRun::STATUS_DRAFT,
                'finalized_at' => null,
            ]);

            PayrollSlip::where('run_id', $locked->id)->update([
                'status' => PayrollSlip::STATUS_DRAFT,
            ]);
        });

        return $run->fresh(['period', 'creator:id,name', 'financeExpense']);
    }

    public function deleteRun(PayrollRun $run): void
    {
        if (! $run->isEditable()) {
            throw ValidationException::withMessages([
                'run' => 'Hanya proses draft yang dapat dihapus.',
            ]);
        }

        $run->delete();
    }

    protected function countWeekdays(Carbon $start, Carbon $end): int
    {
        $count = 0;
        $cursor = $start->copy();
        while ($cursor->lte($end)) {
            if (! $cursor->isWeekend()) {
                $count++;
            }
            $cursor->addDay();
        }

        return max(1, $count);
    }
}
