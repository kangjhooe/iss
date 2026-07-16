<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Announcement extends Model
{
    protected $fillable = [
        'title',
        'body',
        'target',
        'institution_ids',
        'created_by',
        'recipients_count',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'institution_ids' => 'array',
            'published_at' => 'datetime',
        ];
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
