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
        'floor',
        'area',
        'capacity',
        'condition',
        'description',
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
}
