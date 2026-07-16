<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PiketLog extends Model
{
    use SoftDeletes;

    protected $table = 'piket_logs';

    public const STATUS_DRAFT = 'draft';
    public const STATUS_SUBMITTED = 'submitted';
    public const STATUS_REVIEWED = 'reviewed';

    public const STATUSES = [
        self::STATUS_DRAFT => 'Draft',
        self::STATUS_SUBMITTED => 'Dikirim',
        self::STATUS_REVIEWED => 'Direview',
    ];

    protected $fillable = [
        'institution_id',
        'duty_date',
        'employee_id',
        'piket_schedule_id',
        'summary',
        'handoff_notes',
        'status',
        'created_by',
        'reviewed_by',
        'reviewed_at',
        'review_notes',
    ];

    protected function casts(): array
    {
        return [
            'duty_date' => 'date',
            'reviewed_at' => 'datetime',
        ];
    }

    public function institution()
    {
        return $this->belongsTo(Institution::class);
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function schedule()
    {
        return $this->belongsTo(PiketSchedule::class, 'piket_schedule_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function incidents()
    {
        return $this->hasMany(PiketIncident::class, 'piket_log_id');
    }

    public function scopeForInstitution($query, int $institutionId)
    {
        return $query->where('institution_id', $institutionId);
    }
}
