<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudentMutation extends Model
{
    use HasFactory, Auditable;

    protected $table = 'student_mutations';

    protected $fillable = [
        'origin_institution_id',
        'origin_npsn',
        'origin_school_name',
        'target_institution_id',
        'target_npsn',
        'target_school_name',
        'student_id',
        'student_grade',
        'student_gender',
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

    /** Mutasi ke sekolah yang belum terdaftar di aplikasi (input manual NPSN + nama). */
    public function isExternalTarget(): bool
    {
        return $this->target_institution_id === null;
    }

    /** Mutasi masuk dari sekolah yang belum terdaftar (input manual NPSN + nama asal). */
    public function isExternalOrigin(): bool
    {
        return $this->origin_institution_id === null;
    }

    public function canBeApprovedBy(User $user): bool
    {
        if ($this->status !== 'pending') {
            return false;
        }
        // Mutasi ke sekolah luar sistem: tidak ada approval dari tujuan
        if ($this->isExternalTarget()) {
            return false;
        }
        // Mutasi masuk dari sekolah luar: tidak ada approval dari asal (sudah dicatat langsung)
        if ($this->isExternalOrigin()) {
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
