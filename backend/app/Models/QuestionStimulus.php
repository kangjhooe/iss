<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuestionStimulus extends Model
{
    use HasFactory;

    protected $table = 'question_stimuli';

    protected $fillable = [
        'institution_id',
        'bank_soal_id',
        'subject_id',
        'title',
        'content',
        'type',
        'attachment_path',
    ];

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

    public function questions()
    {
        return $this->hasMany(QuestionBank::class, 'stimulus_id');
    }

    public function scopeForInstitution($query, int $institutionId)
    {
        return $query->where('institution_id', $institutionId);
    }

    public function scopeForBankSoal($query, int $bankSoalId)
    {
        return $query->where('bank_soal_id', $bankSoalId);
    }
}
