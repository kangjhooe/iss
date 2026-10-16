<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventoryNeedLine extends Model
{
    use Auditable, HasFactory;

    protected $table = 'inventory_need_line';

    protected $fillable = [
        'analysis_id',
        'institution_id',
        'section',
        'name',
        'unit',
        'basis_type',
        'basis_room_type',
        'room_type',
        'basis_value',
        'ratio_numerator',
        'ratio_denominator',
        'usable_policy',
        'category_id',
        'source_item_ids',
        'priority',
        'unit_price',
        'unit_repair_price',
        'notes',
        'sort_order',
        'existing_baik',
        'existing_rusak_ringan',
        'existing_rusak_sedang',
        'existing_rusak_berat',
        'existing_habis',
        'required_qty',
        'usable_qty',
        'shortage_qty',
        'procure_qty',
        'repair_qty',
        'surplus_qty',
        'estimated_cost',
        'procure_cost',
        'repair_cost',
    ];

    protected function casts(): array
    {
        return [
            'source_item_ids' => 'array',
            'basis_value' => 'integer',
            'ratio_numerator' => 'integer',
            'ratio_denominator' => 'integer',
            'sort_order' => 'integer',
            'existing_baik' => 'integer',
            'existing_rusak_ringan' => 'integer',
            'existing_rusak_sedang' => 'integer',
            'existing_rusak_berat' => 'integer',
            'existing_habis' => 'integer',
            'required_qty' => 'integer',
            'usable_qty' => 'integer',
            'shortage_qty' => 'integer',
            'procure_qty' => 'integer',
            'repair_qty' => 'integer',
            'surplus_qty' => 'integer',
            'unit_price' => 'decimal:2',
            'unit_repair_price' => 'decimal:2',
            'estimated_cost' => 'decimal:2',
            'procure_cost' => 'decimal:2',
            'repair_cost' => 'decimal:2',
        ];
    }

    public function analysis()
    {
        return $this->belongsTo(InventoryNeedAnalysis::class, 'analysis_id');
    }

    public function category()
    {
        return $this->belongsTo(InventoryCategory::class, 'category_id');
    }

    public function basisLabel(): string
    {
        $n = (int) $this->basis_value;

        return match ($this->basis_type) {
            'siswa' => $n.' siswa',
            'rombel' => $n.' rombel',
            'ruang' => $n.' ruang'.($this->basis_room_type ? ' '.$this->basis_room_type : ''),
            default => 'Manual '.$n,
        };
    }

    public function ratioLabel(): string
    {
        return (int) $this->ratio_numerator.' / '.(int) $this->ratio_denominator;
    }

    public function usablePolicyLabel(): string
    {
        return $this->usable_policy === 'baik_only' ? 'Baik' : 'Baik + ringan';
    }

    public function priorityLabel(): string
    {
        return match ($this->priority) {
            'tinggi' => 'Tinggi',
            'rendah' => 'Rendah',
            default => 'Sedang',
        };
    }

    public function gapLabel(): string
    {
        if ((int) $this->shortage_qty > 0) {
            return 'Kurang';
        }
        if ((int) $this->surplus_qty > 0) {
            return 'Lebih';
        }

        return 'Cukup';
    }
};
