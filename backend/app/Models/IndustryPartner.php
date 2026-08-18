<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class IndustryPartner extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'institution_id',
        'name',
        'business_field',
        'address',
        'city',
        'phone',
        'email',
        'pic_name',
        'pic_phone',
        'status',
        'notes',
    ];

    public function institution()
    {
        return $this->belongsTo(Institution::class);
    }

    public function pklPlacements()
    {
        return $this->hasMany(PklPlacement::class);
    }

    public function bkkVacancies()
    {
        return $this->hasMany(BkkVacancy::class);
    }

    public function scopeForInstitution($query, int $institutionId)
    {
        return $query->where('institution_id', $institutionId);
    }
}
