<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class EmployeeDecree extends Model
{
    use SoftDeletes;

    protected $table = 'employee_decrees';

    public const TYPES = [
        'pengangkatan' => 'SK Pengangkatan',
        'penempatan' => 'SK Penempatan',
        'kenaikan_pangkat' => 'SK Kenaikan Pangkat',
        'mutasi' => 'SK Mutasi',
        'tugas_tambahan' => 'SK Tugas Tambahan',
        'jabatan' => 'SK Jabatan',
        'pemberhentian' => 'SK Pemberhentian',
        'lainnya' => 'Lainnya',
    ];

    protected $fillable = [
        'institution_id',
        'employee_id',
        'decree_type',
        'number',
        'title',
        'decree_date',
        'effective_date',
        'end_date',
        'description',
        'notes',
        'file_path',
        'file_name',
        'file_size',
        'mime_type',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'decree_date' => 'date',
            'effective_date' => 'date',
            'end_date' => 'date',
            'file_size' => 'integer',
        ];
    }

    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function structuralAssignments(): HasMany
    {
        return $this->hasMany(EmployeeStructuralPosition::class);
    }

    public function getDecreeTypeLabelAttribute(): string
    {
        return self::TYPES[$this->decree_type] ?? $this->decree_type;
    }
}
