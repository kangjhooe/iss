<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeInstitutionAssignment extends Model
{
    use HasFactory;

    protected $table = 'employee_institution_assignments';

    protected $fillable = [
        'employee_id',
        'institution_id',
        'assignment_type',
        'status',
        'subject',
        'assignment_title',
        'assignment_notes',
        'requested_by',
        'approved_by',
        'approved_at',
        'rejection_reason',
        'started_at',
        'ended_at',
        'ended_reason',
    ];

    protected function casts(): array
    {
        return [
            'approved_at' => 'datetime',
            'started_at' => 'date',
            'ended_at' => 'date',
        ];
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function institution()
    {
        return $this->belongsTo(Institution::class);
    }

    public function requester()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function scopeForInstitution($query, int $institutionId)
    {
        return $query->where('institution_id', $institutionId);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }
}
