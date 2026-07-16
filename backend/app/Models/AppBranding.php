<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppBranding extends Model
{
    protected $table = 'app_branding';

    protected $fillable = [
        'app_logo',
        'favicon',
        'hero_headline',
        'hero_subheadline',
        'hero_image',
        'hero_primary_cta_text',
        'hero_primary_cta_to',
        'hero_secondary_cta_text',
        'hero_secondary_cta_to',
        'maintenance_mode',
        'maintenance_message',
    ];

    protected function casts(): array
    {
        return [
            'maintenance_mode' => 'boolean',
        ];
    }

    protected $appends = ['app_logo_url', 'favicon_url', 'hero_image_url'];

    /**
     * URL lengkap untuk logo aplikasi (untuk frontend).
     */
    public function getAppLogoUrlAttribute(): ?string
    {
        return $this->app_logo ? asset('storage/' . $this->app_logo) : null;
    }

    /**
     * URL lengkap untuk favicon (untuk frontend).
     */
    public function getFaviconUrlAttribute(): ?string
    {
        return $this->favicon ? asset('storage/' . $this->favicon) : null;
    }

    /**
     * URL lengkap untuk gambar hero (untuk frontend).
     */
    public function getHeroImageUrlAttribute(): ?string
    {
        return $this->hero_image ? asset('storage/' . $this->hero_image) : null;
    }
}
