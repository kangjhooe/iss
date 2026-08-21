<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\Auditable;

class Exam extends Model
{
    use HasFactory, Auditable;

    protected $table = 'exams';

    public const START_TYPE_SCHEDULED = 'scheduled';
    public const START_TYPE_MANUAL = 'manual';

    protected $fillable = [
        'institution_id',
        'subject_id',
        'code',
        'name',
        'description',
        'duration_minutes',
        'shuffle_questions',
        'shuffle_options',
        'start_type',
        'scheduled_start_at',
        'scheduled_end_at',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'shuffle_questions' => 'boolean',
            'shuffle_options' => 'boolean',
            'scheduled_start_at' => 'datetime',
            'scheduled_end_at' => 'datetime',
        ];
    }

    public function institution()
    {
        return $this->belongsTo(Institution::class);
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function createdByUser()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function sessions()
    {
        return $this->hasMany(ExamSession::class, 'exam_id');
    }

    public function examQuestions()
    {
        return $this->hasMany(ExamQuestion::class, 'exam_id')->orderBy('sort_order');
    }

    /**
     * Paket soal tidak boleh diubah setelah ada sesi yang berjalan atau selesai.
     */
    public function questionsAreLocked(): bool
    {
        if ($this->relationLoaded('sessions')) {
            return $this->sessions->contains(
                fn ($s) => in_array($s->status, [ExamSession::STATUS_STARTED, ExamSession::STATUS_ENDED], true)
            );
        }

        return $this->sessions()
            ->whereIn('status', [ExamSession::STATUS_STARTED, ExamSession::STATUS_ENDED])
            ->exists();
    }

    public function scopeForInstitution($query, int $institutionId)
    {
        return $query->where('institution_id', $institutionId);
    }
}
