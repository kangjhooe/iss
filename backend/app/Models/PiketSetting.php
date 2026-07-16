<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PiketSetting extends Model
{
    protected $table = 'piket_settings';

    protected $fillable = [
        'institution_id',
        'teacher_late_threshold',
        'include_saturday',
        'empty_class_grace_minutes',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'include_saturday' => 'boolean',
            'empty_class_grace_minutes' => 'integer',
            'teacher_late_threshold' => 'datetime:H:i',
        ];
    }

    public function institution()
    {
        return $this->belongsTo(Institution::class);
    }

    public static function forInstitution(int $institutionId): self
    {
        return static::firstOrCreate(
            ['institution_id' => $institutionId],
            [
                'teacher_late_threshold' => '07:15:00',
                'include_saturday' => false,
                'empty_class_grace_minutes' => 15,
            ]
        );
    }
}
