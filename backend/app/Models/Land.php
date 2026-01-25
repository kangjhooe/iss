<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\Auditable;

class Land extends Model
{
    use HasFactory, SoftDeletes, Auditable;

    protected $table = 'land';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'institution_id',
        'name',
        'certificate_number',
        'certificate_type',
        'area',
        'location',
        'status',
        'acquisition_date',
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
            'acquisition_date' => 'date',
        ];
    }

    /**
     * Get the institution that owns the land.
     */
    public function institution()
    {
        return $this->belongsTo(Institution::class);
    }

    /**
     * Get the buildings on this land.
     */
    public function buildings()
    {
        return $this->hasMany(Building::class);
    }
}
