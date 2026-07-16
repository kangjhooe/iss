<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TemplateSurat extends Model
{
    use HasFactory;

    protected $table = 'template_surat';

    protected $fillable = [
        'institution_id',
        'source_template_id',
        'nama',
        'kode',
        'isi_html',
        'status',
        'letter_type_code',
        'subject_type',
    ];

    protected $appends = [
        'is_platform',
    ];

    public function institution()
    {
        return $this->belongsTo(Institution::class);
    }

    public function sourceTemplate()
    {
        return $this->belongsTo(self::class, 'source_template_id');
    }

    public function derivedTemplates()
    {
        return $this->hasMany(self::class, 'source_template_id');
    }

    public function surat()
    {
        return $this->hasMany(Surat::class, 'template_id');
    }

    public function getIsPlatformAttribute(): bool
    {
        return $this->institution_id === null;
    }

    public function isPlatform(): bool
    {
        return $this->institution_id === null;
    }

    public function scopeAktif($query)
    {
        return $query->where('status', 'aktif');
    }

    public function scopePlatform($query)
    {
        return $query->whereNull('institution_id');
    }

    public function scopeForInstitution($query, ?int $institutionId)
    {
        return $query->where(function ($q) use ($institutionId) {
            $q->whereNull('institution_id');
            if ($institutionId) {
                $q->orWhere('institution_id', $institutionId);
            }
        });
    }
}
