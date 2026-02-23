<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppBranding extends Model
{
    protected $table = 'app_branding';

    protected $fillable = ['app_logo', 'favicon'];

protected $appends = ['app_logo_url', 'favicon_url'];

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
}
