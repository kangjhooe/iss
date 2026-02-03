<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\Auditable;

class EmployeeAttendance extends Model
{
    use HasFactory, SoftDeletes, Auditable;

    protected $table = 'employee_attendances';

    public const STATUS_HADIR = 'hadir';
    public const STATUS_ALPHA = 'alpha';
    public const STATUS_IZIN = 'izin';
    public const STATUS_SAKIT = 'sakit';
    public const STATUS_CUTI = 'cuti';
    public const STATUS_DINAS_LUAR = 'dinas_luar';
    public const STATUS_WFH = 'wfh';

    public const STATUSES = [
        self::STATUS_HADIR => 'Hadir',
        self::STATUS_ALPHA => 'Alpha',
        self::STATUS_IZIN => 'Izin',
        self::STATUS_SAKIT => 'Sakit',
        self::STATUS_CUTI => 'Cuti',
        self::STATUS_DINAS_LUAR => 'Dinas Luar',
        self::STATUS_WFH => 'WFH',
    ];

    protected $fillable = [
        'institution_id',
        'employee_id',
        'date',
        'status',
        'check_in_time',
        'check_out_time',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'check_in_time' => 'datetime:H:i',
            'check_out_time' => 'datetime:H:i',
        ];
    }

    public function institution()
    {
        return $this->belongsTo(Institution::class);
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function scopeForInstitution($query, int $institutionId)
    {
        return $query->where('institution_id', $institutionId);
    }

    public function scopeForEmployee($query, int $employeeId)
    {
        return $query->where('employee_id', $employeeId);
    }

    public function scopeDateFrom($query, string $date)
    {
        return $query->whereDate('date', '>=', $date);
    }

    public function scopeDateTo($query, string $date)
    {
        return $query->whereDate('date', '<=', $date);
    }
}
