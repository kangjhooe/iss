<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExamAnswer extends Model
{
    use HasFactory;

    protected $table = 'exam_answers';

    protected $fillable = [
        'exam_participant_id',
        'question_bank_id',
        'answer_text',
        'question_option_id',
        'selected_option_ids',
        'matching_answer',
        'score',
        'saved_at',
    ];

    protected function casts(): array
    {
        return [
            'score' => 'decimal:2',
            'saved_at' => 'datetime',
            'selected_option_ids' => 'array',
            'matching_answer' => 'array',
        ];
    }

    public function examParticipant()
    {
        return $this->belongsTo(ExamParticipant::class, 'exam_participant_id');
    }

    public function questionBank()
    {
        return $this->belongsTo(QuestionBank::class, 'question_bank_id');
    }

    public function questionOption()
    {
        return $this->belongsTo(QuestionOption::class, 'question_option_id');
    }
}
