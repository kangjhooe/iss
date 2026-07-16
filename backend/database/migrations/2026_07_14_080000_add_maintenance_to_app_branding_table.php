<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('app_branding', function (Blueprint $table) {
            if (!Schema::hasColumn('app_branding', 'maintenance_mode')) {
                $table->boolean('maintenance_mode')->default(false)->after('hero_secondary_cta_to');
            }
            if (!Schema::hasColumn('app_branding', 'maintenance_message')) {
                $table->text('maintenance_message')->nullable()->after('maintenance_mode');
            }
        });
    }

    public function down(): void
    {
        Schema::table('app_branding', function (Blueprint $table) {
            if (Schema::hasColumn('app_branding', 'maintenance_message')) {
                $table->dropColumn('maintenance_message');
            }
            if (Schema::hasColumn('app_branding', 'maintenance_mode')) {
                $table->dropColumn('maintenance_mode');
            }
        });
    }
};
