<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FeedbackTicket extends Model
{
    use HasFactory;

    public const TYPE_BUG = 'bug';

    public const TYPE_FEATURE = 'feature';

    public const STATUS_OPEN = 'open';

    public const STATUS_IN_PROGRESS = 'in_progress';

    public const STATUS_RESOLVED = 'resolved';

    public const STATUS_CLOSED = 'closed';

    public const STATUS_REJECTED = 'rejected';

    public const TYPES = [
        self::TYPE_BUG,
        self::TYPE_FEATURE,
    ];

    public const STATUSES = [
        self::STATUS_OPEN,
        self::STATUS_IN_PROGRESS,
        self::STATUS_RESOLVED,
        self::STATUS_CLOSED,
        self::STATUS_REJECTED,
    ];

    public const PRIORITIES = ['low', 'medium', 'high'];

    public const OPEN_STATUSES = [
        self::STATUS_OPEN,
        self::STATUS_IN_PROGRESS,
    ];

    protected $table = 'feedback_tickets';

    protected $fillable = [
        'institution_id',
        'submitted_by',
        'handled_by',
        'type',
        'title',
        'description',
        'module',
        'priority',
        'status',
        'admin_note',
        'handled_at',
    ];

    protected function casts(): array
    {
        return [
            'handled_at' => 'datetime',
        ];
    }

    public function institution()
    {
        return $this->belongsTo(Institution::class);
    }

    public function submitter()
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function handler()
    {
        return $this->belongsTo(User::class, 'handled_by');
    }

    public function scopeOpen($query)
    {
        return $query->whereIn('status', self::OPEN_STATUSES);
    }

    public function scopeForInstitution($query, int $institutionId)
    {
        return $query->where('institution_id', $institutionId);
    }

    public function isOpen(): bool
    {
        return in_array($this->status, self::OPEN_STATUSES, true);
    }
}
