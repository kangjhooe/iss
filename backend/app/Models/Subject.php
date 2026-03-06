<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\Auditable;

class Subject extends Model
{
    use HasFactory, SoftDeletes, Auditable;

    protected $table = 'subjects';

    protected $fillable = [
        'institution_id',
        'code',
        'name',
        'description',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function institution()
    {
        return $this->belongsTo(Institution::class);
    }

    public function lessonSchedules()
    {
        return $this->hasMany(LessonSchedule::class);
    }

    /**
     * Get the grades (nilai) for this subject.
     */
    public function grades()
    {
        return $this->hasMany(Grade::class, 'subject_id');
    }

    /**
     * Get the teaching journals for this subject.
     */
    public function teachingJournals()
    {
        return $this->hasMany(TeachingJournal::class, 'subject_id');
    }

    /**
     * Get the question bank items (soal) for this subject.
     */
    public function questionBanks()
    {
        return $this->hasMany(QuestionBank::class, 'subject_id');
    }

    /**
     * Get the exams (ujian) for this subject.
     */
    public function exams()
    {
        return $this->hasMany(Exam::class, 'subject_id');
    }

    /**
     * Get the bank soal for this subject.
     */
    public function bankSoal()
    {
        return $this->hasMany(BankSoal::class, 'subject_id');
    }

    /**
     * Get the question stimuli for this subject.
     */
    public function questionStimuli()
    {
        return $this->hasMany(QuestionStimulus::class, 'subject_id');
    }

    public function scopeForInstitution($query, int $institutionId)
    {
        return $query->where('institution_id', $institutionId);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
