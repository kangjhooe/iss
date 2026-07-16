<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KopSurat extends Model
{
    use HasFactory;

    protected $table = 'kop_surat';

    protected $fillable = [
        'institution_id',
        'nama',
        'logo_kiri',
        'logo_kanan',
        'baris_1',
        'baris_2',
        'baris_3',
        'alamat',
        'telepon',
        'email',
        'website',
        'isi_html',
        'tampilkan_garis',
        'is_default',
        'status',
    ];

    protected $appends = ['logo_kiri_url', 'logo_kanan_url'];

    protected function casts(): array
    {
        return [
            'tampilkan_garis' => 'boolean',
            'is_default' => 'boolean',
        ];
    }

    public function institution()
    {
        return $this->belongsTo(Institution::class);
    }

    public function surat()
    {
        return $this->hasMany(Surat::class, 'kop_id');
    }

    public function getLogoKiriUrlAttribute(): ?string
    {
        return $this->logo_kiri ? asset('storage/' . $this->logo_kiri) : null;
    }

    public function getLogoKananUrlAttribute(): ?string
    {
        return $this->logo_kanan ? asset('storage/' . $this->logo_kanan) : null;
    }

    public function scopeAktif($query)
    {
        return $query->where('status', 'aktif');
    }

    public function scopeForInstitution($query, int $institutionId)
    {
        return $query->where('institution_id', $institutionId);
    }
}
