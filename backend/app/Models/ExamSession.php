<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExamSession extends Model
{
    use HasFactory;

    protected $table = 'exam_sessions';

    public const STATUS_DRAFT = 'draft';
    public const STATUS_SCHEDULED = 'scheduled';
    public const STATUS_STARTED = 'started';
    public const STATUS_ENDED = 'ended';

    protected $fillable = [
        'exam_id',
        'name',
        'scheduled_start_at',
        'scheduled_end_at',
        'started_at',
        'ended_at',
        'status',
        'entry_pin',
        'entry_pin_updated_at',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_start_at' => 'datetime',
            'scheduled_end_at' => 'datetime',
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
            'entry_pin_updated_at' => 'datetime',
        ];
    }

    /** Generate 6 uppercase letters (A-Z) for student entry. */
    public static function generateEntryPin(): string
    {
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $pin = '';
        $max = strlen($chars) - 1;
        for ($i = 0; $i < 6; $i++) {
            $pin .= $chars[random_int(0, $max)];
        }
        return $pin;
    }

    public function exam()
    {
        return $this->belongsTo(Exam::class);
    }

    public function participants()
    {
        return $this->hasMany(ExamParticipant::class, 'exam_session_id');
    }

    public function scopeByExam($query, int $examId)
    {
        return $query->where('exam_id', $examId);
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }
}
