<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class AuditLog extends Model
{
    protected $fillable = [
        'user_id',
        'institution_id',
        'action',
        'auditable_type',
        'auditable_id',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent',
        'url',
        'method',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function institution()
    {
        return $this->belongsTo(Institution::class);
    }

    public function auditable()
    {
        return $this->morphTo();
    }

    /**
     * Write a manual audit log entry (e.g. for module access changes, approve/reject).
     */
    public static function logManual(
        Request $request,
        string $action,
        string $auditableType,
        $auditableId,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?int $institutionId = null
    ): void {
        $user = $request->user();
        self::create([
            'user_id' => $user?->id,
            'institution_id' => $institutionId ?? $user?->institution_id,
            'action' => $action,
            'auditable_type' => $auditableType,
            'auditable_id' => $auditableId,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'url' => $request->fullUrl(),
            'method' => $request->method(),
        ]);
    }
}
