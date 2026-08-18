<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PklMonitoringLog extends Model
{
    public const METHODS = ['kunjungan', 'telepon', 'online'];

    protected $fillable = [
        'institution_id',
        'pkl_placement_id',
        'logged_by_employee_id',
        'visit_date',
        'method',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'visit_date' => 'date',
        ];
    }

    public function placement()
    {
        return $this->belongsTo(PklPlacement::class, 'pkl_placement_id');
    }

    public function loggedBy()
    {
        return $this->belongsTo(Employee::class, 'logged_by_employee_id');
    }
}
