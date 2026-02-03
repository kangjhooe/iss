<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LibraryBookCopy extends Model
{
    use HasFactory;

    protected $table = 'library_book_copies';

    protected $fillable = [
        'book_id',
        'copy_code',
        'status',
        'condition',
        'notes',
    ];

    public function book()
    {
        return $this->belongsTo(LibraryBook::class);
    }

    public function loans()
    {
        return $this->hasMany(LibraryLoan::class, 'copy_id');
    }

    public function scopeAvailable($query)
    {
        return $query->where('status', 'Tersedia');
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function isAvailable(): bool
    {
        if ($this->status !== 'Tersedia') {
            return false;
        }
        $activeLoan = $this->loans()->whereIn('status', ['Dipinjam', 'Terlambat'])->exists();
        return !$activeLoan;
    }
}
