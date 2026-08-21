<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PpdbApplicantDocument extends Model
{
    protected $table = 'ppdb_applicant_documents';

    protected $fillable = [
        'ppdb_applicant_id',
        'name',
        'document_key',
        'file_path',
        'file_name',
        'file_size',
        'mime_type',
        'description',
    ];

    public function applicant(): BelongsTo
    {
        return $this->belongsTo(PpdbApplicant::class, 'ppdb_applicant_id');
    }
}
