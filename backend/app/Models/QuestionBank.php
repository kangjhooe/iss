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
        'sort_order',
        'key_answer',
        'key_answer_aliases',
        'matching_data',
    ];

    protected function casts(): array
    {
        return [
            'weight' => 'decimal:2',
            'sort_order' => 'integer',
            'matching_data' => 'array',
            'key_answer_aliases' => 'array',
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

    /**
     * Accepted answers for isian (primary key + aliases), normalized lowercase trim.
     *
     * @return list<string>
     */
    public function acceptedIsianAnswers(): array
    {
        $keys = [];
        if ($this->key_answer !== null && trim((string) $this->key_answer) !== '') {
            $keys[] = trim(mb_strtolower((string) $this->key_answer));
        }
        foreach ($this->key_answer_aliases ?? [] as $alias) {
            $t = trim(mb_strtolower((string) $alias));
            if ($t !== '') {
                $keys[] = $t;
            }
        }

        return array_values(array_unique($keys));
    }
}
