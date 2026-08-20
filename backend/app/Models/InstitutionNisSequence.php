<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InstitutionNisSequence extends Model
{
    protected $table = 'institution_nis_sequences';

    protected $fillable = [
        'institution_id',
        'period_key',
        'last_seq',
    ];

    protected function casts(): array
    {
        return [
            'last_seq' => 'integer',
        ];
    }

    public function institution()
    {
        return $this->belongsTo(Institution::class);
    }
}
