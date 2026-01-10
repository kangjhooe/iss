<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeEducation extends Model
{
    use HasFactory;

    protected $table = 'employee_educations';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'employee_id',
        'level',
        'school_name',
        'major',
        'graduation_year',
        'certificate_number',
        'city',
        'notes',
        'order',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'graduation_year' => 'integer',
            'order' => 'integer',
        ];
    }

    /**
     * Get the employee that owns this education.
     */
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
