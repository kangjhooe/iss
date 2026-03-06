<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Hero halaman awal: judul, subjudul, gambar, dan CTA (dapat diatur via menu Branding Aplikasi).
     */
    public function up(): void
    {
        Schema::table('app_branding', function (Blueprint $table) {
            $table->string('hero_headline', 255)->nullable()->after('favicon')->comment('Judul hero halaman awal');
            $table->text('hero_subheadline')->nullable()->after('hero_headline')->comment('Subjudul hero');
            $table->string('hero_image')->nullable()->after('hero_subheadline')->comment('Path gambar hero di storage');
            $table->string('hero_primary_cta_text', 100)->nullable()->after('hero_image');
            $table->string('hero_primary_cta_to', 255)->nullable()->after('hero_primary_cta_text');
            $table->string('hero_secondary_cta_text', 100)->nullable()->after('hero_primary_cta_to');
            $table->string('hero_secondary_cta_to', 255)->nullable()->after('hero_secondary_cta_text');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('app_branding', function (Blueprint $table) {
            $table->dropColumn([
                'hero_headline',
                'hero_subheadline',
                'hero_image',
                'hero_primary_cta_text',
                'hero_primary_cta_to',
                'hero_secondary_cta_text',
                'hero_secondary_cta_to',
            ]);
        });
    }
};
