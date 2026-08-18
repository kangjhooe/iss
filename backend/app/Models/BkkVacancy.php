<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BkkVacancy extends Model
{
    use SoftDeletes;

    public const STATUSES = ['buka', 'tutup'];

    protected $fillable = [
        'institution_id',
        'industry_partner_id',
        'title',
        'company_name',
        'position',
        'quota',
        'deadline',
        'status',
        'description',
        'requirements',
    ];

    protected function casts(): array
    {
        return [
            'deadline' => 'date',
            'quota' => 'integer',
        ];
    }

    public function institution()
    {
        return $this->belongsTo(Institution::class);
    }

    public function industryPartner()
    {
        return $this->belongsTo(IndustryPartner::class);
    }

    public function applications()
    {
        return $this->hasMany(BkkApplication::class);
    }

    public function scopeForInstitution($query, int $institutionId)
    {
        return $query->where('institution_id', $institutionId);
    }

    public function getDisplayCompanyAttribute(): string
    {
        return $this->industryPartner?->name
            ?: ($this->company_name ?: '—');
    }
}
