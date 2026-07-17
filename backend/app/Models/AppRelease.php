<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppRelease extends Model
{
    protected $table = 'app_releases';

    protected $fillable = [
        'title',
        'version',
        'released_at',
        'items',
        'published_at',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'items' => 'array',
            'released_at' => 'date',
            'published_at' => 'datetime',
        ];
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopePublished($query)
    {
        return $query->whereNotNull('published_at');
    }

    public function isPublished(): bool
    {
        return $this->published_at !== null;
    }
}
