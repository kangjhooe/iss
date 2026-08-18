<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeLeaveRequest extends Model
{
    protected $table = 'employee_leave_requests';

    public const TYPES = [
        'tahunan' => 'Cuti Tahunan',
        'sakit' => 'Cuti Sakit',
        'melahirkan' => 'Cuti Melahirkan',
        'penting' => 'Cuti Alasan Penting',
        'tanpa_gaji' => 'Cuti di Luar Tanggungan',
        'lainnya' => 'Lainnya',
    ];

    public const STATUSES = [
        'pending' => 'Menunggu',
        'approved' => 'Disetujui',
        'rejected' => 'Ditolak',
        'cancelled' => 'Dibatalkan',
    ];

    protected $fillable = [
        'institution_id',
        'employee_id',
        'leave_type',
        'start_date',
        'end_date',
        'reason',
        'attachment_path',
        'attachment_name',
        'status',
        'requested_by',
        'approved_by',
        'approved_at',
        'rejection_reason',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'approved_at' => 'datetime',
        ];
    }

    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function getLeaveTypeLabelAttribute(): string
    {
        return self::TYPES[$this->leave_type] ?? $this->leave_type;
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function getDurationDaysAttribute(): int
    {
        if (!$this->start_date || !$this->end_date) {
            return 0;
        }

        return $this->start_date->diffInDays($this->end_date) + 1;
    }
}
