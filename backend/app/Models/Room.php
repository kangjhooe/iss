<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\Auditable;

class Room extends Model
{
    use HasFactory, SoftDeletes, Auditable;

    protected $table = 'room';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'institution_id',
        'building_id',
        'name',
        'code',
        'type',
        'lab_type',
        'floor',
        'area',
        'capacity',
        'condition',
        'description',
        'responsible_employee_id',
    ];

    /** Jenis lab (untuk ruang tipe Laboratorium). */
    public const LAB_TYPES = [
        'IPA' => 'Lab IPA',
        'Komputer' => 'Lab Komputer',
        'Bahasa' => 'Lab Bahasa',
        'Lainnya' => 'Lainnya',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'area' => 'decimal:2',
            'capacity' => 'integer',
            'floor' => 'integer',
        ];
    }

    /**
     * Get the institution that owns the room.
     */
    public function institution()
    {
        return $this->belongsTo(Institution::class);
    }

    /**
     * Get the building where this room is located.
     */
    public function building()
    {
        return $this->belongsTo(Building::class);
    }

    /**
     * Get the classes that use this room.
     */
    public function classes()
    {
        return $this->hasMany(SchoolClass::class);
    }

    /**
     * Get the lesson schedules that use this room.
     */
    public function lessonSchedules()
    {
        return $this->hasMany(LessonSchedule::class);
    }

    /**
     * Get the employee responsible for this room (e.g. Kepala Lab).
     */
    public function responsibleEmployee()
    {
        return $this->belongsTo(Employee::class, 'responsible_employee_id');
    }

    /**
     * Get the inventory items located in this room.
     */
    public function inventoryItems()
    {
        return $this->hasMany(InventoryItem::class, 'room_id');
    }

    /**
     * Get the extracurriculars that use this room.
     */
    public function extracurriculars()
    {
        return $this->hasMany(Extracurricular::class, 'room_id');
    }
}
