<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TeacherMutation extends Model
{
    use HasFactory, Auditable;

    protected $table = 'teacher_mutations';

    protected $fillable = [
        'origin_institution_id',
        'origin_npsn',
        'origin_school_name',
        'target_institution_id',
        'target_npsn',
        'target_school_name',
        'employee_id',
        'employee_nuptk',
        'employee_nip',
        'employee_gender',
        'initiated_by',
        'requested_by',
        'approved_by',
        'status',
        'rejection_reason',
        'approved_at',
        'notes',
        'cancel_reason',
        'cancel_requested_by',
        'cancel_requested_at',
        'cancel_rejection_reason',
    ];

    protected function casts(): array
    {
        return [
            'origin_institution_id' => 'integer',
            'target_institution_id' => 'integer',
            'employee_id' => 'integer',
            'requested_by' => 'integer',
            'approved_by' => 'integer',
            'cancel_requested_by' => 'integer',
            'approved_at' => 'datetime',
            'cancel_requested_at' => 'datetime',
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

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id')->withTrashed();
    }

    public function requester()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function cancelRequester()
    {
        return $this->belongsTo(User::class, 'cancel_requested_by');
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

    public function scopeCancelled($query)
    {
        return $query->where('status', 'cancelled');
    }

    public function scopeCancelPending($query)
    {
        return $query->where('status', 'cancel_pending');
    }

    /** Status yang masih dihitung sebagai mutasi aktif di buku mutasi. */
    public function scopeActiveApproved($query)
    {
        return $query->whereIn('status', ['approved', 'cancel_pending']);
    }

    protected function activeInstitutionId(User $user): ?int
    {
        return $user->currentInstitutionId(request())
            ?? ($user->institution_id ? (int) $user->institution_id : null);
    }

    protected function isInstitutionAdmin(User $user): bool
    {
        return in_array($user->role, ['admin', 'institution_admin'], true);
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
        if (!$this->isInstitutionAdmin($user)) {
            return false;
        }
        $instId = $this->activeInstitutionId($user);

        // Mutasi keluar ke sekolah luar sistem: auto-approved saat dibuat, seharusnya tidak pernah pending.
        if ($this->isExternalTarget()) {
            return false;
        }

        // Mutasi masuk dari sekolah luar sistem: auto-approved saat dibuat, seharusnya tidak pernah pending.
        if ($this->isExternalOrigin()) {
            return false;
        }

        // Initiated by origin → target institution admin approves
        if ($this->initiated_by === 'origin') {
            return $instId !== null && (int) $instId === (int) $this->target_institution_id;
        }
        // Initiated by target → origin institution admin approves
        return $instId !== null && (int) $instId === (int) $this->origin_institution_id;
    }

    public function canBeRejectedBy(User $user): bool
    {
        return $this->canBeApprovedBy($user);
    }

    /**
     * Batal langsung (pending) atau ajukan batal (approved → cancel_pending / langsung bila eksternal).
     */
    public function canRequestCancelBy(User $user): bool
    {
        if (!$this->isInstitutionAdmin($user) && (int) $user->id !== (int) $this->requested_by) {
            return false;
        }

        $instId = $this->activeInstitutionId($user);

        if ($this->status === 'pending') {
            // Pengaju atau admin sekolah yang mengajukan
            if ((int) $user->id === (int) $this->requested_by) {
                return true;
            }
            if (!$this->isInstitutionAdmin($user)) {
                return false;
            }
            if ($this->initiated_by === 'origin') {
                return $instId !== null && (int) $instId === (int) $this->origin_institution_id;
            }
            return $instId !== null && (int) $instId === (int) $this->target_institution_id;
        }

        if ($this->status === 'approved') {
            if (!$this->isInstitutionAdmin($user)) {
                return false;
            }
            // Mutasi keluar ke sekolah luar: asal bisa batalkan langsung
            if ($this->isExternalTarget()) {
                return $instId !== null && (int) $instId === (int) $this->origin_institution_id;
            }
            // Mutasi masuk dari luar: tujuan bisa batalkan langsung (tidak ada admin asal di sistem)
            if ($this->isExternalOrigin()) {
                return $instId !== null && (int) $instId === (int) $this->target_institution_id;
            }
            // Sudah diterima tujuan: asal mengajukan batal (butuh persetujuan tujuan)
            return $instId !== null && (int) $instId === (int) $this->origin_institution_id;
        }

        return false;
    }

    /** Admin sekolah tujuan menyetujui/menolak permohonan batal mutasi. */
    public function canDecideCancelBy(User $user): bool
    {
        if ($this->status !== 'cancel_pending' || $this->isExternalTarget()) {
            return false;
        }
        if (!$this->isInstitutionAdmin($user)) {
            return false;
        }

        $instId = $this->activeInstitutionId($user);

        return $instId !== null && (int) $instId === (int) $this->target_institution_id;
    }
}
