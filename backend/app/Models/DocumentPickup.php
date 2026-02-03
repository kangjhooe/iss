<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\Auditable;

class DocumentPickup extends Model
{
    use HasFactory, SoftDeletes, Auditable;

    protected $table = 'document_pickups';

    protected $fillable = [
        'institution_id',
        'student_id',
        'pickup_date',
        'taken_ijazah',
        'taken_raport',
        'taken_skhun',
        'nomor_ijazah',
        'kode_blangko',
        'dokumen_lainnya',
        'photo_path',
        'received_by',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'pickup_date' => 'datetime',
            'taken_ijazah' => 'boolean',
            'taken_raport' => 'boolean',
            'taken_skhun' => 'boolean',
        ];
    }

    public function institution()
    {
        return $this->belongsTo(Institution::class);
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by')->withDefault([
            'id' => null,
            'name' => '—',
        ]);
    }

    public function getFotoUrlAttribute(): ?string
    {
        if (!$this->photo_path) {
            return null;
        }
        return asset('storage/' . $this->photo_path);
    }

    public function scopeForInstitution($query, int $institutionId)
    {
        return $query->where('institution_id', $institutionId);
    }
}
