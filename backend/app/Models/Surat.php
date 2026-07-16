<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Surat extends Model
{
    use HasFactory;

    protected $table = 'surat';

    protected $fillable = [
        'institution_id',
        'correspondence_id',
        'template_id',
        'student_id',
        'employee_id',
        'kop_id',
        'tampilkan_kop',
        'tanda_tangan_id',
        'tampilkan_tanda_tangan',
        'stempel_id',
        'tampilkan_stempel',
        'posisi_ttd',
        'nomor',
        'judul',
        'tanggal',
        'isi_html',
        'pdf',
        'letter_type_code',
        'status',
        'published_at',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'tampilkan_kop' => 'boolean',
            'tampilkan_tanda_tangan' => 'boolean',
            'tampilkan_stempel' => 'boolean',
            'tanggal' => 'date',
            'published_at' => 'datetime',
        ];
    }

    public function institution()
    {
        return $this->belongsTo(Institution::class);
    }

    public function correspondence()
    {
        return $this->belongsTo(Correspondence::class, 'correspondence_id');
    }

    public function template()
    {
        return $this->belongsTo(TemplateSurat::class, 'template_id');
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function kop()
    {
        return $this->belongsTo(KopSurat::class, 'kop_id');
    }

    public function tandaTangan()
    {
        return $this->belongsTo(AsetTandaTangan::class, 'tanda_tangan_id');
    }

    public function stempel()
    {
        return $this->belongsTo(AsetTandaTangan::class, 'stempel_id');
    }

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function isPublished(): bool
    {
        return $this->status === 'terbit';
    }

    public function scopeForInstitution($query, int $institutionId)
    {
        return $query->where('institution_id', $institutionId);
    }
}
