<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\Auditable;

class GuestVisit extends Model
{
    use HasFactory, SoftDeletes, Auditable;

    protected $table = 'guest_visits';

    protected $fillable = [
        'institution_id',
        'nama_tamu',
        'no_identitas',
        'instansi_asal',
        'no_telepon',
        'tujuan_kunjungan',
        'orang_ditemui',
        'waktu_masuk',
        'waktu_keluar',
        'foto_path',
        'catatan',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'waktu_masuk' => 'datetime',
            'waktu_keluar' => 'datetime',
        ];
    }

    public function institution()
    {
        return $this->belongsTo(Institution::class);
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
        if (!$this->foto_path) {
            return null;
        }
        return asset('storage/' . $this->foto_path);
    }

    public function scopeForInstitution($query, int $institutionId)
    {
        return $query->where('institution_id', $institutionId);
    }
}
