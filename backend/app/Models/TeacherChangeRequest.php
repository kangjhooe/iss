<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TeacherChangeRequest extends Model
{
    use HasFactory;

    protected $table = 'teacher_change_requests';

    /**
     * Fields that teachers are allowed to request changes for.
     * Admin-only fields (nik, nip, nuptk, name, institution_id, status, type, etc.) are excluded.
     */
    public const ALLOWED_FIELDS = [
        'address',
        'phone',
        'email',
        'religion',
        'birth_place',
        'birth_date',
        'education_level',
        'major',
        'subject',
        'notes',
        'certification_status',
        'certification_date',
        'teacher_registration_number',
        'certification_number',
        'certification_issuing_authority',
    ];

    protected $fillable = [
        'employee_id',
        'requested_by',
        'approved_by',
        'field_name',
        'old_value',
        'new_value',
        'status',
        'rejection_reason',
        'approved_at',
    ];

    protected function casts(): array
    {
        return [
            'approved_at' => 'datetime',
        ];
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
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

    public static function isAllowedField(string $field): bool
    {
        return in_array($field, self::ALLOWED_FIELDS, true);
    }
}
