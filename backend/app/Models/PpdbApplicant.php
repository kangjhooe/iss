<?php

namespace App\Models;

use App\Support\PpdbDocumentChecklist;
use App\Support\PpdbDocumentStorage;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\UploadedFile;
use InvalidArgumentException;
use OverflowException;

class PpdbApplicant extends Model
{
    public const STATUS_ACCEPTED_ELSEWHERE = 'accepted_elsewhere';

    /** @var list<string> Status yang tidak boleh dilanjutkan ke seleksi/convert */
    public const SELECTION_LOCKED_STATUSES = [
        'converted',
        'cancelled',
        self::STATUS_ACCEPTED_ELSEWHERE,
    ];

    /** @var list<string> Status final yang tidak ditandai ulang */
    public const TERMINAL_STATUSES = [
        'converted',
        'cancelled',
        'rejected',
        'failed',
        self::STATUS_ACCEPTED_ELSEWHERE,
    ];

    protected $table = 'ppdb_applicants';

    protected $fillable = [
        'ppdb_period_id',
        'ppdb_channel_id',
        'registration_number',
        'status',
        'rank',
        'announcement_at',
        're_registration_deadline',
        're_registration_confirmed_at',
        'student_id',
        'result_notes',
        'name',
        'nik',
        'nisn',
        'gender',
        'birth_date',
        'birth_place',
        'address',
        'village',
        'sub_district',
        'district',
        'province',
        'postal_code',
        'wilayah_province_code',
        'wilayah_regency_code',
        'wilayah_district_code',
        'wilayah_village_code',
        'phone',
        'email',
        'religion',
        'previous_school',
        'previous_school_npsn',
        'previous_school_address',
        'father_name',
        'father_phone',
        'father_nik',
        'mother_name',
        'mother_phone',
        'mother_nik',
        'guardian_name',
        'guardian_phone',
        'guardian_relation',
        'documents_verified',
        'verification_notes',
        'submitted_at',
        'notes',
        'payment_status',
        'payment_amount',
        'payment_type',
        'paid_at',
        'payment_notes',
    ];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'documents_verified' => 'boolean',
            'submitted_at' => 'datetime',
            'announcement_at' => 'datetime',
            're_registration_deadline' => 'date',
            're_registration_confirmed_at' => 'datetime',
            'payment_amount' => 'decimal:2',
            'paid_at' => 'datetime',
        ];
    }

    public function period(): BelongsTo
    {
        return $this->belongsTo(PpdbPeriod::class, 'ppdb_period_id');
    }

    public function channel(): BelongsTo
    {
        return $this->belongsTo(PpdbChannel::class, 'ppdb_channel_id');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(PpdbApplicantDocument::class, 'ppdb_applicant_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function isSelectionLocked(): bool
    {
        return in_array($this->status, self::SELECTION_LOCKED_STATUSES, true);
    }

    public function scopeForPeriod($query, int $periodId)
    {
        return $query->where('ppdb_period_id', $periodId);
    }

    public function scopeStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * @throws InvalidArgumentException
     * @throws OverflowException
     */
    public function storeUploadedDocument(
        UploadedFile $file,
        ?string $documentKey = null,
        ?string $name = null,
        ?string $description = null
    ): PpdbApplicantDocument {
        $this->loadMissing('channel');
        [$key, $resolvedName] = PpdbDocumentChecklist::resolveUpload($this->channel, $documentKey, $name);
        if ($resolvedName === '') {
            throw new InvalidArgumentException('Nama berkas wajib diisi.');
        }

        $existing = $key
            ? $this->documents()->where('document_key', $key)->first()
            : null;

        if (! $existing && $this->documents()->count() >= 20) {
            throw new OverflowException('Maksimal 20 file dokumen per calon peserta didik');
        }

        $originalName = $file->getClientOriginalName();
        $extension = $file->getClientOriginalExtension();
        $safeName = preg_replace('/[^a-zA-Z0-9._-]/', '_', pathinfo($originalName, PATHINFO_FILENAME));
        $fileName = time().'_'.$safeName.'.'.$extension;
        $filePath = PpdbDocumentStorage::store($file, (int) $this->id, $fileName);

        $payload = [
            'name' => $resolvedName,
            'document_key' => $key,
            'file_path' => $filePath,
            'file_name' => $originalName,
            'file_size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
            'description' => $description,
        ];

        if ($existing) {
            PpdbDocumentStorage::delete($existing->file_path);
            $existing->update($payload);

            return $existing->fresh();
        }

        return $this->documents()->create($payload);
    }
}
