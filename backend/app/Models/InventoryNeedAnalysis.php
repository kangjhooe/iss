<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class InventoryNeedAnalysis extends Model
{
    use Auditable, HasFactory, SoftDeletes;

    protected $table = 'inventory_need_analysis';

    protected $fillable = [
        'institution_id',
        'academic_year_id',
        'academic_year_label',
        'title',
        'status',
        'student_count',
        'rombel_count',
        'shortage_lines',
        'high_priority_lines',
        'estimated_budget',
        'procure_budget',
        'repair_budget',
        'notes',
        'finalized_at',
        'counts_synced_at',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'student_count' => 'integer',
            'rombel_count' => 'integer',
            'shortage_lines' => 'integer',
            'high_priority_lines' => 'integer',
            'estimated_budget' => 'decimal:2',
            'procure_budget' => 'decimal:2',
            'repair_budget' => 'decimal:2',
            'finalized_at' => 'datetime',
            'counts_synced_at' => 'datetime',
        ];
    }

    public function institution()
    {
        return $this->belongsTo(Institution::class);
    }

    public function academicYear()
    {
        return $this->belongsTo(AcademicYear::class, 'academic_year_id');
    }

    public function lines()
    {
        return $this->hasMany(InventoryNeedLine::class, 'analysis_id')
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isDraft(): bool
    {
        return $this->status !== 'final';
    }

    public function statusLabel(): string
    {
        return $this->status === 'final' ? 'Final' : 'Draf';
    }
};
