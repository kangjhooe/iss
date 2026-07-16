<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\Auditable;

class LabUsageJournal extends Model
{
    use HasFactory, SoftDeletes, Auditable;

    protected $table = 'lab_usage_journal';

    protected $fillable = [
        'institution_id',
        'room_id',
        'date',
        'start_time',
        'end_time',
        'recorded_by',
        'class_id',
        'subject_id',
        'lab_booking_id',
        'lesson_schedule_id',
        'activity',
        'participants_count',
        'notes',
        'incident_notes',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'participants_count' => 'integer',
        ];
    }

    public function institution()
    {
        return $this->belongsTo(Institution::class);
    }

    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    public function recorder()
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function schoolClass()
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class, 'subject_id');
    }

    public function labBooking()
    {
        return $this->belongsTo(LabBooking::class, 'lab_booking_id');
    }

    public function lessonSchedule()
    {
        return $this->belongsTo(LessonSchedule::class, 'lesson_schedule_id');
    }
}
