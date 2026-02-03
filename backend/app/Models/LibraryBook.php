<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\Auditable;

class LibraryBook extends Model
{
    use HasFactory, SoftDeletes, Auditable;

    protected $table = 'library_books';

    protected $fillable = [
        'institution_id',
        'category_id',
        'isbn',
        'title',
        'author',
        'publisher',
        'year',
        'language',
        'pages',
        'shelf_code',
        'description',
        'cover_path',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'pages' => 'integer',
        ];
    }

    public function institution()
    {
        return $this->belongsTo(Institution::class);
    }

    public function category()
    {
        return $this->belongsTo(LibraryBookCategory::class, 'category_id');
    }

    public function copies()
    {
        return $this->hasMany(LibraryBookCopy::class, 'book_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function scopeForInstitution($query, $institutionId)
    {
        return $query->where('institution_id', $institutionId);
    }

    public function getAvailableCopiesCount(): int
    {
        return $this->copies()->where('status', 'Tersedia')->count();
    }
}
