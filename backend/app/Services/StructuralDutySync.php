<?php

namespace App\Services;

use App\Models\AdditionalDuty;
use App\Models\Employee;
use App\Models\Permission;
use App\Models\StructuralPosition;
use App\Models\User;
use App\Support\KaprogAccess;
use App\Support\ReportAccess;
use App\Support\TeacherAccess;

/**
 * Satu sumber kebenaran jabatan struktural:
 * penetapan di Kepegawaian ikut menulis tugas tambahan + akses modul.
 */
class StructuralDutySync
{
    /** @var list<string>|null */
    protected ?array $cachedPositionKeys = null;

    /** @var list<int>|null */
    protected ?array $cachedStructuralDutyIds = null;

    /** @var list<string>|null */
    protected ?array $cachedAllDutyPermissionKeys = null;

    /**
     * @return list<string>
     */
    public function positionKeys(): array
    {
        return $this->cachedPositionKeys ??= StructuralPosition::query()
            ->pluck('key')
            ->filter()
            ->values()
            ->all();
    }

    public function isKey(?string $key): bool
    {
        return $key !== null && in_array($key, $this->positionKeys(), true);
    }

    /**
     * @return list<int>
     */
    public function structuralDutyIds(): array
    {
        return $this->cachedStructuralDutyIds ??= AdditionalDuty::query()
            ->whereIn('key', $this->positionKeys())
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    public function isDutyId(int $id): bool
    {
        return in_array($id, $this->structuralDutyIds(), true);
    }

    /**
     * Jangan biarkan form Data Guru mencabut jabatan struktural yang masih aktif.
     *
     * @param  list<int|string>  $requestedDutyIds
     * @return list<int>
     */
    public function mergePreservedDutyIds(Employee $employee, array $requestedDutyIds): array
    {
        $requested = array_values(array_unique(array_map('intval', $requestedDutyIds)));
        $structuralIds = $this->structuralDutyIds();
        $activeStructural = $this->activeStructuralDutyIds($employee);

        $operational = array_values(array_diff($requested, $structuralIds));
        $requestedStructural = array_values(array_intersect($requested, $structuralIds));

        return array_values(array_unique(array_merge($operational, $activeStructural, $requestedStructural)));
    }

    /**
     * @return list<int>
     */
    public function activeStructuralDutyIds(Employee $employee): array
    {
        $structuralIds = $this->structuralDutyIds();
        if ($structuralIds === []) {
            return [];
        }

        return $employee->additionalDuties()
            ->whereIn('additional_duties.id', $structuralIds)
            ->where(function ($q) {
                $q->whereNull('employee_additional_duties.ended_at')
                    ->orWhere('employee_additional_duties.ended_at', '>', now());
            })
            ->pluck('additional_duties.id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    /**
     * Sinkron tugas tambahan dari form Data Guru, tanpa mencabut jabatan struktural aktif.
     * Duty struktural baru (klien lama yang masih mencentang) ikut membuat penugasan jabatan.
     *
     * @param  list<int|string>  $requestedDutyIds
     * @return list<int>
     */
    public function applyDutyIds(Employee $employee, array $requestedDutyIds, int $actorUserId): array
    {
        $beforeStructural = $this->activeStructuralDutyIds($employee);
        $final = $this->mergePreservedDutyIds($employee, $requestedDutyIds);
        $newStructural = array_values(array_diff(
            array_intersect($final, $this->structuralDutyIds()),
            $beforeStructural
        ));

        $employee->additionalDuties()->sync($final);

        foreach ($newStructural as $dutyId) {
            $key = AdditionalDuty::query()->where('id', $dutyId)->value('key');
            $position = $key ? StructuralPosition::query()->where('key', $key)->first() : null;
            if (! $position) {
                continue;
            }

            $alreadyActive = $employee->structuralPositions()
                ->where('structural_position_id', $position->id)
                ->active()
                ->exists();
            if ($alreadyActive) {
                $this->grant($employee, $key, now()->toDateString());

                continue;
            }

            app(KepegawaianService::class)->assignStructuralPosition((int) $employee->institution_id, [
                'employee_id' => $employee->id,
                'structural_position_id' => $position->id,
                'started_at' => now()->toDateString(),
            ], $actorUserId);
        }

        return $final;
    }

    public function grant(Employee $employee, string $positionKey, string $startedAt, ?int $institutionId = null): void
    {
        $duty = AdditionalDuty::query()->where('key', $positionKey)->first();
        if (! $duty) {
            return;
        }

        $scopeInstitutionId = $institutionId ?: (int) $employee->institution_id;

        $others = Employee::query()
            ->forInstitution($scopeInstitutionId)
            ->where('id', '!=', $employee->id)
            ->whereHas('additionalDuties', function ($query) use ($duty) {
                $query->where('additional_duties.id', $duty->id)
                    ->where(function ($active) {
                        $active->whereNull('employee_additional_duties.ended_at')
                            ->orWhere('employee_additional_duties.ended_at', '>', now());
                    });
            })
            ->get();

        foreach ($others as $other) {
            $this->detachDuty($other, $duty);
            $this->refreshAccess($other);
        }

        $existing = $employee->additionalDuties()->where('additional_duties.id', $duty->id)->first();
        if ($existing) {
            $employee->additionalDuties()->updateExistingPivot($duty->id, [
                'started_at' => $startedAt,
                'ended_at' => null,
            ]);
        } else {
            $employee->additionalDuties()->attach($duty->id, [
                'started_at' => $startedAt,
                'ended_at' => null,
            ]);
        }

        $this->refreshAccess($employee);
    }

    public function revoke(Employee $employee, string $positionKey): void
    {
        $duty = AdditionalDuty::query()->where('key', $positionKey)->first();
        if (! $duty) {
            return;
        }

        $this->detachDuty($employee, $duty);

        if ($positionKey === KaprogAccess::DUTY_KEY) {
            $employee->programKeahlians()->sync([]);
        }

        $this->refreshAccess($employee);
    }

    protected function detachDuty(Employee $employee, AdditionalDuty $duty): void
    {
        $employee->additionalDuties()->detach($duty->id);
    }

    /**
     * Recalculate module access from active tugas tambahan, keeping non-duty extras.
     */
    public function refreshAccess(Employee $employee): void
    {
        if (! $employee->email) {
            return;
        }

        $user = User::query()->where('email', $employee->email)->first();
        if (! $user || ! in_array($user->role, ['teacher', 'staff'], true)) {
            return;
        }

        $employee->unsetRelation('additionalDuties');
        $activeDutyPerms = $this->activeDutyPermissionKeys($employee);
        $allDutyPerms = $this->allDutyPermissionKeys();

        $current = $user->permissions()->pluck('key')->all();
        $manual = array_values(array_diff($current, $allDutyPerms));
        $effective = array_values(array_unique(array_merge($manual, $activeDutyPerms)));

        if ($user->role === 'teacher' || $employee->type === 'Guru') {
            $effective = TeacherAccess::mergeTeachingDefaults($effective);
        }

        $effective = ReportAccess::sanitizeKeysForEmployee($employee, $effective);

        $permissionIds = Permission::query()->whereIn('key', $effective)->pluck('id')->all();
        $user->permissions()->sync($permissionIds);
    }

    /**
     * @return list<string>
     */
    protected function allDutyPermissionKeys(): array
    {
        return $this->cachedAllDutyPermissionKeys ??= Permission::query()
            ->whereHas('additionalDuties')
            ->pluck('key')
            ->all();
    }

    /**
     * @return list<string>
     */
    protected function activeDutyPermissionKeys(Employee $employee): array
    {
        return $employee->additionalDuties()
            ->where(function ($q) {
                $q->whereNull('employee_additional_duties.ended_at')
                    ->orWhere('employee_additional_duties.ended_at', '>', now());
            })
            ->with('permissions')
            ->get()
            ->flatMap(fn ($duty) => $duty->permissions->pluck('key'))
            ->unique()
            ->values()
            ->all();
    }
}
