<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudentChangeRequest extends Model
{
    use HasFactory;

    protected $table = 'student_change_requests';

    /**
     * Fields students may edit only via change request (admin approval).
     * Structural fields (class, status, institution, academic year, etc.) remain admin-only.
     */
    public const APPROVAL_FIELDS = [
        'name',
        'nik',
        'nis',
        'nisn',
        'gender',
        'birth_place',
        'birth_date',
        'email',
        'no_kk',
        'father_name',
        'father_nik',
        'mother_name',
        'mother_nik',
        'guardian_name',
        'guardian_nik',
    ];

    /**
     * Fields students may update on their own profile without approval.
     */
    public const SELF_EDITABLE_FIELDS = [
        'address',
        'phone',
        'religion',
        'aspiration',
        'hobby',
        'disability',
        'height',
        'weight',
        'previous_school',
        'previous_school_npsn',
        'previous_school_address',
        'residence_type',
        'father_status',
        'father_birth_place',
        'father_birth_date',
        'father_education',
        'father_occupation',
        'father_income',
        'mother_status',
        'mother_birth_place',
        'mother_birth_date',
        'mother_education',
        'mother_occupation',
        'mother_income',
        'guardian_phone',
        'guardian_type',
        'guardian_status',
        'guardian_birth_place',
        'guardian_birth_date',
        'guardian_education',
        'guardian_occupation',
        'guardian_income',
        'notes',
    ];

    /**
     * @deprecated Use APPROVAL_FIELDS. Kept for backward compatibility.
     */
    public const ALLOWED_FIELDS = self::APPROVAL_FIELDS;

    protected $fillable = [
        'student_id',
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

    public static function isAllowedField(string $field): bool
    {
        return in_array($field, self::APPROVAL_FIELDS, true);
    }

    public static function isSelfEditableField(string $field): bool
    {
        return in_array($field, self::SELF_EDITABLE_FIELDS, true);
    }
}
