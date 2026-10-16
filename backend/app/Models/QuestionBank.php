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

    public const STATUS_DRAFT = 'draft';
    public const STATUS_READY = 'ready';

    /** Taksonomi Bloom (level kognitif). */
    public const COGNITIVE_C1 = 'c1';
    public const COGNITIVE_C2 = 'c2';
    public const COGNITIVE_C3 = 'c3';
    public const COGNITIVE_C4 = 'c4';
    public const COGNITIVE_C5 = 'c5';
    public const COGNITIVE_C6 = 'c6';

    public const COGNITIVE_LEVELS = [
        self::COGNITIVE_C1 => 'C1 — Mengingat',
        self::COGNITIVE_C2 => 'C2 — Memahami',
        self::COGNITIVE_C3 => 'C3 — Menerapkan',
        self::COGNITIVE_C4 => 'C4 — Menganalisis',
        self::COGNITIVE_C5 => 'C5 — Mengevaluasi',
        self::COGNITIVE_C6 => 'C6 — Mencipta',
    ];

    public const DIFFICULTY_MUDAH = 'mudah';
    public const DIFFICULTY_SEDANG = 'sedang';
    public const DIFFICULTY_SULIT = 'sulit';

    public const DIFFICULTIES = [
        self::DIFFICULTY_MUDAH => 'Mudah',
        self::DIFFICULTY_SEDANG => 'Sedang',
        self::DIFFICULTY_SULIT => 'Sulit',
    ];

    protected $fillable = [
        'institution_id',
        'bank_soal_id',
        'subject_id',
        'stimulus_id',
        'type',
        'status',
        'topic',
        'body',
        'weight',
        'cognitive_level',
        'difficulty',
        'tags',
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
            'tags' => 'array',
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

    public function examQuestions()
    {
        return $this->hasMany(ExamQuestion::class, 'question_bank_id');
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
