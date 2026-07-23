<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WaliNote extends Model
{
    use HasFactory;

    protected $table = 'wali_notes';

    protected $fillable = [
        'institution_id',
        'class_id',
        'student_id',
        'author_user_id',
        'body',
    ];

    public function institution()
    {
        return $this->belongsTo(Institution::class);
    }

    public function schoolClass()
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function author()
    {
        return $this->belongsTo(User::class, 'author_user_id');
    }
}
