<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdditionalDuty extends Model
{
    use HasFactory;

    protected $table = 'additional_duties';

    protected $fillable = [
        'key',
        'label',
        'description',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }

    /**
     * Permissions (module access) granted by this duty.
     */
    public function permissions()
    {
        return $this->belongsToMany(Permission::class, 'additional_duty_permissions');
    }

    /**
     * Employees who have this additional duty.
     */
    public function employees()
    {
        return $this->belongsToMany(Employee::class, 'employee_additional_duties')
            ->withPivot(['started_at', 'ended_at'])
            ->withTimestamps();
    }

    /**
     * Get permission keys for this duty.
     */
    public function getPermissionKeys(): array
    {
        return $this->permissions()->pluck('key')->toArray();
    }

    /**
     * Pegawai aktif di institusi yang sedang memegang tugas tambahan (belum berakhir).
     */
    public static function resolveActiveHolder(string $dutyKey, int $institutionId): ?\App\Models\Employee
    {
        return \App\Models\Employee::forInstitution($institutionId)
            ->whereHas('additionalDuties', function ($query) use ($dutyKey) {
                $query->where('additional_duties.key', $dutyKey)
                    ->where(function ($active) {
                        $active->whereNull('employee_additional_duties.ended_at')
                            ->orWhere('employee_additional_duties.ended_at', '>', now());
                    });
            })
            ->orderBy('name')
            ->first(['id', 'name', 'nip']);
    }
}
