<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LibraryEbookView extends Model
{
    protected $table = 'library_ebook_views';

    protected $fillable = [
        'institution_id',
        'book_id',
        'user_id',
        'source',
        'visitor_key',
        'ip_address',
        'user_agent',
        'viewed_at',
    ];

    protected function casts(): array
    {
        return [
            'viewed_at' => 'datetime',
        ];
    }

    public function book()
    {
        return $this->belongsTo(LibraryBook::class, 'book_id');
    }

    public function institution()
    {
        return $this->belongsTo(Institution::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
