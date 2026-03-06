<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\Auditable;

class QuestionBank extends Model
{
    use HasFactory, SoftDeletes, Auditable;

    protected $table = 'question_bank';

    public const TYPE_PG = 'pg';
    public const TYPE_ISIAN = 'isian';
    public const TYPE_URAIAN = 'uraian';
    public const TYPE_PG_KOMPLEKS = 'pg_kompleks';
    public const TYPE_MATCHING = 'matching';

    protected $fillable = [
        'institution_id',
        'bank_soal_id',
        'subject_id',
        'stimulus_id',
        'type',
        'body',
        'weight',
        'key_answer',
        'matching_data',
    ];

    protected function casts(): array
    {
        return [
            'weight' => 'decimal:2',
            'matching_data' => 'array',
        ];
    }

    public function institution()
    {
        return $this->belongsTo(Institution::class);
    }

    public function bankSoal()
    {
        return $this->belongsTo(BankSoal::class, 'bank_soal_id');
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function stimulus()
    {
        return $this->belongsTo(QuestionStimulus::class, 'stimulus_id');
    }

    public function options()
    {
        return $this->hasMany(QuestionOption::class, 'question_bank_id')->orderBy('sort_order');
    }

    public function scopeForInstitution($query, int $institutionId)
    {
        return $query->where('institution_id', $institutionId);
    }

    public function scopeForBank($query, int $bankSoalId)
    {
        return $query->where('bank_soal_id', $bankSoalId);
    }

    public function scopeForSubject($query, int $subjectId)
    {
        return $query->where('subject_id', $subjectId);
    }
}
