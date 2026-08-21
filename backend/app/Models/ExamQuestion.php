<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExamQuestion extends Model
{
    use HasFactory;

    protected $table = 'exam_questions';

    protected $fillable = [
        'exam_id',
        'question_bank_id',
        'sort_order',
        'snapshot',
    ];

    protected function casts(): array
    {
        return [
            'snapshot' => 'array',
        ];
    }

    public function exam()
    {
        return $this->belongsTo(Exam::class);
    }

    public function questionBank()
    {
        return $this->belongsTo(QuestionBank::class, 'question_bank_id');
    }

    /**
     * Frozen package payload, or a live capture if this exam was attached before snapshots existed.
     *
     * @return array<string, mixed>|null
     */
    public function scoringPayload(?QuestionBank $live = null): ?array
    {
        if (\App\Services\ExamQuestionSnapshot::hasPayload($this->snapshot)) {
            return $this->snapshot;
        }
        $live = $live ?? $this->questionBank;
        if (! $live) {
            return null;
        }

        $captured = \App\Services\ExamQuestionSnapshot::capture($live);
        if ($this->exists) {
            $this->forceFill(['snapshot' => $captured])->save();
        }

        return $captured;
    }
}
