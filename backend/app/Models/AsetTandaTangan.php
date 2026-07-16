<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AsetTandaTangan extends Model
{
    use HasFactory;

    protected $table = 'aset_tanda_tangan';

    protected $fillable = [
        'institution_id',
        'jenis',
        'nama',
        'file_path',
        'pemilik_nama',
        'pemilik_jabatan',
        'pemilik_nip',
        'lebar_mm',
        'tinggi_mm',
        'is_default',
        'status',
    ];

    protected $appends = ['file_url'];

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
            'lebar_mm' => 'integer',
            'tinggi_mm' => 'integer',
        ];
    }

    public function institution()
    {
        return $this->belongsTo(Institution::class);
    }

    public function suratAsTandaTangan()
    {
        return $this->hasMany(Surat::class, 'tanda_tangan_id');
    }

    public function suratAsStempel()
    {
        return $this->hasMany(Surat::class, 'stempel_id');
    }

    public function getFileUrlAttribute(): ?string
    {
        return $this->file_path ? asset('storage/' . $this->file_path) : null;
    }

    public function scopeAktif($query)
    {
        return $query->where('status', 'aktif');
    }

    public function scopeJenis($query, string $jenis)
    {
        return $query->where('jenis', $jenis);
    }

    public function scopeForInstitution($query, int $institutionId)
    {
        return $query->where('institution_id', $institutionId);
    }
}
