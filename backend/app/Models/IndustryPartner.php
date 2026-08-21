<?php

namespace App\Models;

use App\Support\RegionAddress;
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
        'village',
        'sub_district',
        'district',
        'province',
        'postal_code',
        'wilayah_province_code',
        'wilayah_regency_code',
        'wilayah_district_code',
        'wilayah_village_code',
        'city',
        'phone',
        'email',
        'pic_name',
        'pic_phone',
        'status',
        'notes',
    ];

    protected $appends = ['full_address'];

    public function getFullAddressAttribute(): ?string
    {
        return RegionAddress::format($this);
    }

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
