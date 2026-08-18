<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubscriptionAddon extends Model
{
    public const KEY_STORAGE_UPGRADE = 'storage_upgrade';
    public const KEY_ONLINE_EXAM = 'online_exam';

    protected $fillable = [
        'key',
        'name',
        'description',
        'storage_mb',
        'price_monthly',
        'price_yearly',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'storage_mb' => 'integer',
            'price_monthly' => 'integer',
            'price_yearly' => 'integer',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }
}
