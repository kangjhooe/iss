<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\Auditable;

class LabBooking extends Model
{
    use HasFactory, SoftDeletes, Auditable;

    protected $table = 'lab_booking';

    public const STATUSES = ['pending', 'approved', 'rejected', 'cancelled'];

    protected $fillable = [
        'institution_id',
        'room_id',
        'requester_employee_id',
        'requester_name',
        'purpose',
        'date',
        'start_time',
        'end_time',
        'status',
        'approved_by',
        'approved_at',
        'notes',
        'rejection_reason',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'approved_at' => 'datetime',
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

    public function requesterEmployee()
    {
        return $this->belongsTo(Employee::class, 'requester_employee_id');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
