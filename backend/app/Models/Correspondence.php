<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\Auditable;

class Correspondence extends Model
{
    use HasFactory, SoftDeletes, Auditable;

    protected $table = 'correspondence';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'institution_id',
        'type',
        'letter_type_code',
        'letter_number',
        'reference_number',
        'subject',
        'from',
        'to',
        'date',
        'received_date',
        'priority',
        'status',
        'category_id',
        'created_by',
        'approved_by',
        'approved_at',
        'description',
        'file_path',
        'file_name',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date' => 'date',
            'received_date' => 'date',
            'approved_at' => 'datetime',
        ];
    }

    /**
     * Get the institution that owns the correspondence.
     */
    public function institution()
    {
        return $this->belongsTo(Institution::class);
    }

    /**
     * Get the category of the correspondence.
     */
    public function category()
    {
        return $this->belongsTo(CorrespondenceCategory::class, 'category_id')->withDefault();
    }

    /**
     * Get the user who created the correspondence.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by')->withDefault([
            'id' => null,
            'name' => 'Unknown',
            'email' => null,
        ]);
    }

    /**
     * Get the user who approved the correspondence.
     */
    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by')->withDefault([
            'id' => null,
            'name' => null,
            'email' => null,
        ]);
    }

    /**
     * Get the dispositions for the correspondence.
     */
    public function dispositions()
    {
        return $this->hasMany(CorrespondenceDisposition::class);
    }

    /**
     * Get the attachments for the correspondence.
     */
    public function attachments()
    {
        return $this->hasMany(CorrespondenceAttachment::class);
    }

    /**
     * Get the histories for the correspondence.
     */
    public function histories()
    {
        return $this->hasMany(CorrespondenceHistory::class)->orderBy('created_at', 'desc');
    }

    /**
     * Scope a query to filter by type.
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope a query to filter by status.
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope a query to filter by institution.
     */
    public function scopeForInstitution($query, int $institutionId)
    {
        return $query->where('institution_id', $institutionId);
    }

    /**
     * Scope a query to filter by priority.
     */
    public function scopeByPriority($query, string $priority)
    {
        return $query->where('priority', $priority);
    }

    /**
     * Check if correspondence can be approved.
     */
    public function canBeApproved(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if correspondence can be sent.
     */
    public function canBeSent(): bool
    {
        return in_array($this->status, ['approved', 'draft']);
    }

    /**
     * Get file URL if exists.
     */
    public function getFileUrlAttribute(): ?string
    {
        if (!$this->file_path) {
            return null;
        }
        return asset('storage/' . $this->file_path);
    }

    /**
     * Mapping jenis surat standar (01-16)
     */
    public static function getLetterTypes(): array
    {
        return [
            '01' => ['code' => '01', 'abbr' => 'SK', 'name' => 'Surat Keputusan'],
            '02' => ['code' => '02', 'abbr' => 'SU', 'name' => 'Surat Undangan'],
            '03' => ['code' => '03', 'abbr' => 'SPm', 'name' => 'Surat Permohonan'],
            '04' => ['code' => '04', 'abbr' => 'SPb', 'name' => 'Surat Pemberitahuan'],
            '05' => ['code' => '05', 'abbr' => 'SPj', 'name' => 'Surat Peminjaman'],
            '06' => ['code' => '06', 'abbr' => 'SPn', 'name' => 'Surat Pernyataan'],
            '07' => ['code' => '07', 'abbr' => 'SM', 'name' => 'Surat Mandat'],
            '08' => ['code' => '08', 'abbr' => 'ST', 'name' => 'Surat Tugas'],
            '09' => ['code' => '09', 'abbr' => 'SKet', 'name' => 'Surat Keterangan'],
            '10' => ['code' => '10', 'abbr' => 'SR', 'name' => 'Surat Rekomendasi'],
            '11' => ['code' => '11', 'abbr' => 'SB', 'name' => 'Surat Balasan'],
            '12' => ['code' => '12', 'abbr' => 'SPPD', 'name' => 'Surat Perintah Perjalanan Dinas'],
            '13' => ['code' => '13', 'abbr' => 'SRT', 'name' => 'Sertifikat'],
            '14' => ['code' => '14', 'abbr' => 'SPK', 'name' => 'Perjanjian Kerja'],
            '15' => ['code' => '15', 'abbr' => 'SPg', 'name' => 'Surat Pengantar'],
            '16' => ['code' => '16', 'abbr' => 'SL', 'name' => 'Surat Lainnya'],
        ];
    }

    /**
     * Get jenis surat by code.
     */
    public static function getLetterType(string $code): ?array
    {
        $types = self::getLetterTypes();
        return $types[$code] ?? null;
    }

    /**
     * Get jenis surat abbreviation.
     */
    public function getLetterTypeAbbrAttribute(): ?string
    {
        if (!$this->letter_type_code) {
            return null;
        }
        $type = self::getLetterType($this->letter_type_code);
        return $type['abbr'] ?? null;
    }

    /**
     * Get jenis surat name.
     */
    public function getLetterTypeNameAttribute(): ?string
    {
        if (!$this->letter_type_code) {
            return null;
        }
        $type = self::getLetterType($this->letter_type_code);
        return $type['name'] ?? null;
    }
}
