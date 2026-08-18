<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class SchoolPostImage extends Model
{
    protected $table = 'school_post_images';

    protected $fillable = [
        'school_post_id',
        'path',
        'caption',
        'sort',
    ];

    protected function casts(): array
    {
        return [
            'sort' => 'integer',
        ];
    }

    public function post(): BelongsTo
    {
        return $this->belongsTo(SchoolPost::class, 'school_post_id');
    }

    public function getUrlAttribute(): ?string
    {
        if (!$this->path) {
            return null;
        }

        return Storage::disk('public')->url($this->path);
    }
}
