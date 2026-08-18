<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProgramKeahlian extends Model
{
    use SoftDeletes, Auditable;

    protected $table = 'program_keahlian';

    protected $fillable = [
        'institution_id',
        'code',
        'name',
        'status',
        'description',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }

    public function institution()
    {
        return $this->belongsTo(Institution::class);
    }

    public function classes()
    {
        return $this->hasMany(SchoolClass::class, 'program_keahlian_id');
    }

    public function kaprogEmployees()
    {
        return $this->belongsToMany(Employee::class, 'employee_program_keahlian', 'program_keahlian_id', 'employee_id')
            ->withTimestamps();
    }

    public function scopeForInstitution($query, int $institutionId)
    {
        return $query->where('institution_id', $institutionId);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'Aktif');
    }
}
