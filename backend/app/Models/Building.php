<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\Auditable;

class Building extends Model
{
    use HasFactory, SoftDeletes, Auditable;

    protected $table = 'building';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'institution_id',
        'land_id',
        'name',
        'code',
        'floor_count',
        'building_area',
        'condition',
        'construction_year',
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
            'building_area' => 'decimal:2',
            'construction_year' => 'integer',
            'floor_count' => 'integer',
        ];
    }

    /**
     * Get the institution that owns the building.
     */
    public function institution()
    {
        return $this->belongsTo(Institution::class);
    }

    /**
     * Get the land where this building is located.
     */
    public function land()
    {
        return $this->belongsTo(Land::class);
    }

    /**
     * Get the rooms in this building.
     */
    public function rooms()
    {
        return $this->hasMany(Room::class);
    }
}
