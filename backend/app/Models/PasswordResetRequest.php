<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class PasswordResetRequest extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';

    public const STATUS_PROCESSED = 'processed';

    public const STATUS_REJECTED = 'rejected';

    public const STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_PROCESSED,
        self::STATUS_REJECTED,
    ];

    protected $table = 'password_reset_requests';

    protected $fillable = [
        'user_id',
        'institution_id',
        'email',
        'npsn',
        'contact_phone',
        'note',
        'ip_address',
        'status',
        'processed_by',
        'processed_at',
        'rejection_reason',
    ];

    protected function casts(): array
    {
        return [
            'processed_at' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function institution()
    {
        return $this->belongsTo(Institution::class);
    }

    public function processor()
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function canBeProcessed(): bool
    {
        return $this->status === self::STATUS_PENDING && $this->user_id;
    }

    public function canBeRejected(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public static function markPendingProcessedForUser(int $userId, int $processedBy): void
    {
        if (! Schema::hasTable('password_reset_requests')) {
            return;
        }

        static::query()
            ->where('user_id', $userId)
            ->where('status', self::STATUS_PENDING)
            ->update([
                'status' => self::STATUS_PROCESSED,
                'processed_by' => $processedBy,
                'processed_at' => now(),
                'updated_at' => now(),
            ]);
    }
}
