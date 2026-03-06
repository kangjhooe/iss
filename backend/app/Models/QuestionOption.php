<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuestionOption extends Model
{
    use HasFactory;

    protected $table = 'question_options';

    protected $fillable = [
        'question_bank_id',
        'option_key',
        'body',
        'is_correct',
        'option_weight',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_correct' => 'boolean',
            'option_weight' => 'decimal:2',
        ];
    }

    public function questionBank()
    {
        return $this->belongsTo(QuestionBank::class, 'question_bank_id');
    }
}
