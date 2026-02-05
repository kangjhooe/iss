<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudentChangeRequest extends Model
{
    use HasFactory;

    protected $table = 'student_change_requests';

    /**
     * Fields that students are allowed to request changes for.
     * Admin-only fields (nis, nisn, name, class_id, status, institution_id, etc.) are excluded.
     */
    public const ALLOWED_FIELDS = [
        'address',
        'phone',
        'religion',
        'no_kk',
        'aspiration',
        'hobby',
        'disability',
        'height',
        'weight',
        'previous_school',
        'residence_type',
        'father_name',
        'father_status',
        'father_nik',
        'father_birth_place',
        'father_birth_date',
        'father_education',
        'father_occupation',
        'father_income',
        'mother_name',
        'mother_status',
        'mother_nik',
        'mother_birth_place',
        'mother_birth_date',
        'mother_education',
        'mother_occupation',
        'mother_income',
        'guardian_name',
        'guardian_phone',
        'guardian_type',
        'guardian_status',
        'guardian_nik',
        'guardian_birth_place',
        'guardian_birth_date',
        'guardian_education',
        'guardian_occupation',
        'guardian_income',
        'notes',
    ];

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
        return in_array($field, self::ALLOWED_FIELDS, true);
    }
}
