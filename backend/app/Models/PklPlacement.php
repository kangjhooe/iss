<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PklPlacement extends Model
{
    use SoftDeletes;

    public const STATUSES = ['draft', 'berlangsung', 'selesai', 'batal'];

    protected $fillable = [
        'institution_id',
        'pkl_period_id',
        'student_id',
        'industry_partner_id',
        'supervisor_employee_id',
        'industry_supervisor_name',
        'start_date',
        'end_date',
        'status',
        'score',
        'assessment_notes',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'score' => 'decimal:2',
        ];
    }

    public function institution()
    {
        return $this->belongsTo(Institution::class);
    }

    public function period()
    {
        return $this->belongsTo(PklPeriod::class, 'pkl_period_id');
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function industryPartner()
    {
        return $this->belongsTo(IndustryPartner::class);
    }

    public function supervisor()
    {
        return $this->belongsTo(Employee::class, 'supervisor_employee_id');
    }

    public function monitoringLogs()
    {
        return $this->hasMany(PklMonitoringLog::class)->orderByDesc('visit_date');
    }

    public function journals()
    {
        return $this->hasMany(PklJournal::class)->orderByDesc('journal_date');
    }

    public function scopeForInstitution($query, int $institutionId)
    {
        return $query->where('institution_id', $institutionId);
    }
}
