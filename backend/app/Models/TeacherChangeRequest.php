<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TeacherChangeRequest extends Model
{
    use HasFactory;

    protected $table = 'teacher_change_requests';

    /**
     * Fields teachers may edit only via change request (admin approval).
     * Structural fields (institution_id, type) remain admin-only and are excluded.
     */
    public const APPROVAL_FIELDS = [
        'name',
        'nik',
        'nip',
        'nuptk',
        'gender',
        'email',
        'birth_place',
        'birth_date',
        'subject',
        'employment_status',
        'status',
        'join_date',
        'certification_status',
        'certification_date',
        'teacher_registration_number',
        'certification_number',
        'certification_issuing_authority',
    ];

    /**
     * Fields teachers may update on their own profile without approval.
     */
    public const SELF_EDITABLE_FIELDS = [
        'address',
        'village',
        'sub_district',
        'district',
        'province',
        'postal_code',
        'wilayah_province_code',
        'wilayah_regency_code',
        'wilayah_district_code',
        'wilayah_village_code',
        'phone',
        'religion',
        'education_level',
        'major',
        'notes',
    ];

    /**
     * @deprecated Use APPROVAL_FIELDS. Kept for backward compatibility.
     */
    public const ALLOWED_FIELDS = self::APPROVAL_FIELDS;

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
        return in_array($field, self::APPROVAL_FIELDS, true);
    }

    public static function isSelfEditableField(string $field): bool
    {
        return in_array($field, self::SELF_EDITABLE_FIELDS, true);
    }
}
