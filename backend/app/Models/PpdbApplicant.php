<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PpdbApplicant extends Model
{
    protected $table = 'ppdb_applicants';

    protected $fillable = [
        'ppdb_period_id',
        'ppdb_channel_id',
        'registration_number',
        'status',
        'rank',
        'announcement_at',
        're_registration_deadline',
        're_registration_confirmed_at',
        'student_id',
        'result_notes',
        'name',
        'nik',
        'nisn',
        'gender',
        'birth_date',
        'birth_place',
        'address',
        'phone',
        'email',
        'religion',
        'previous_school',
        'previous_school_npsn',
        'previous_school_address',
        'father_name',
        'father_phone',
        'father_nik',
        'mother_name',
        'mother_phone',
        'mother_nik',
        'guardian_name',
        'guardian_phone',
        'guardian_relation',
        'documents_verified',
        'verification_notes',
        'submitted_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'documents_verified' => 'boolean',
            'submitted_at' => 'datetime',
            'announcement_at' => 'datetime',
            're_registration_deadline' => 'date',
            're_registration_confirmed_at' => 'datetime',
        ];
    }

    public function period(): BelongsTo
    {
        return $this->belongsTo(PpdbPeriod::class, 'ppdb_period_id');
    }

    public function channel(): BelongsTo
    {
        return $this->belongsTo(PpdbChannel::class, 'ppdb_channel_id');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(PpdbApplicantDocument::class, 'ppdb_applicant_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function scopeForPeriod($query, int $periodId)
    {
        return $query->where('ppdb_period_id', $periodId);
    }

    public function scopeStatus($query, string $status)
    {
        return $query->where('status', $status);
    }
}
