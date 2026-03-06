<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ExamParticipant extends Model
{
    use HasFactory;

    protected $table = 'exam_participants';

    public const STATUS_REGISTERED = 'registered';
    public const STATUS_STARTED = 'started';
    public const STATUS_SUBMITTED = 'submitted';

    protected $fillable = [
        'exam_session_id',
        'student_id',
        'participant_order',
        'login_token',
        'question_order',
        'started_at',
        'submitted_at',
        'score',
        'score_max',
        'score_released',
        'score_released_at',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'question_order' => 'array',
            'started_at' => 'datetime',
            'submitted_at' => 'datetime',
            'score_released_at' => 'datetime',
            'score' => 'decimal:2',
            'score_max' => 'decimal:2',
            'score_released' => 'boolean',
        ];
    }

    public function examSession()
    {
        return $this->belongsTo(ExamSession::class, 'exam_session_id');
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function answers()
    {
        return $this->hasMany(ExamAnswer::class, 'exam_participant_id');
    }

    public static function generateLoginToken(): string
    {
        return Str::random(32);
    }

    public function scopeBySession($query, int $sessionId)
    {
        return $query->where('exam_session_id', $sessionId);
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }
}
