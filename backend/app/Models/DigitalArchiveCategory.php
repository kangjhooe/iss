<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DigitalArchiveCategory extends Model
{
    use HasFactory;

    protected $table = 'digital_archive_categories';

    protected $fillable = [
        'institution_id',
        'name',
        'slug',
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

    public function archives()
    {
        return $this->hasMany(DigitalArchive::class, 'digital_archive_category_id');
    }
}
