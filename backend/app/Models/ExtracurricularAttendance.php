<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExtracurricularAttendance extends Model
{
    protected $table = 'extracurricular_attendances';

    public const STATUSES = ['hadir', 'izin', 'sakit', 'alpha'];

    protected $fillable = [
        'session_id',
        'student_id',
        'status',
        'notes',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(ExtracurricularSession::class, 'session_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}
