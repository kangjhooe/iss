<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\Auditable;

class ExamPeriod extends Model
{
    use HasFactory, Auditable;

    protected $table = 'exam_periods';

    public const STATUS_DRAFT = 'draft';
    public const STATUS_OPEN = 'open';
    public const STATUS_CLOSED = 'closed';

    protected $fillable = [
        'institution_id',
        'code',
        'name',
        'description',
        'window_start_at',
        'window_end_at',
        'status',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'window_start_at' => 'datetime',
            'window_end_at' => 'datetime',
        ];
    }

    public function institution()
    {
        return $this->belongsTo(Institution::class);
    }

    public function createdByUser()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function exams()
    {
        return $this->hasMany(Exam::class, 'exam_period_id');
    }

    public function scopeForInstitution($query, int $institutionId)
    {
        return $query->where('institution_id', $institutionId);
    }
}
