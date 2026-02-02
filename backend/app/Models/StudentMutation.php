<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudentMutation extends Model
{
    use HasFactory;

    protected $table = 'student_mutations';

    protected $fillable = [
        'origin_institution_id',
        'target_institution_id',
        'student_id',
        'initiated_by',
        'requested_by',
        'approved_by',
        'status',
        'rejection_reason',
        'approved_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'approved_at' => 'datetime',
        ];
    }

    public function originInstitution()
    {
        return $this->belongsTo(Institution::class, 'origin_institution_id');
    }

    public function targetInstitution()
    {
        return $this->belongsTo(Institution::class, 'target_institution_id');
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function requester()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    public function scopeForOriginInstitution($query, int $institutionId)
    {
        return $query->where('origin_institution_id', $institutionId);
    }

    public function scopeForTargetInstitution($query, int $institutionId)
    {
        return $query->where('target_institution_id', $institutionId);
    }

    public function canBeApprovedBy(User $user): bool
    {
        if ($this->status !== 'pending') {
            return false;
        }
        // Initiated by origin → target institution admin approves
        if ($this->initiated_by === 'origin') {
            return $user->institution_id === $this->target_institution_id
                && in_array($user->role, ['admin', 'institution_admin'], true);
        }
        // Initiated by target → origin institution admin approves
        return $user->institution_id === $this->origin_institution_id
            && in_array($user->role, ['admin', 'institution_admin'], true);
    }

    public function canBeRejectedBy(User $user): bool
    {
        return $this->canBeApprovedBy($user);
    }
}
