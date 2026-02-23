<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Logo aplikasi dan favicon (branding global). Terpisah dari logo institusi.
     */
    public function up(): void
    {
        Schema::create('app_branding', function (Blueprint $table) {
            $table->id();
            $table->string('app_logo')->nullable()->comment('Path di storage: app_branding/app_logo_xxx.png');
            $table->string('favicon')->nullable()->comment('Path di storage: app_branding/favicon_xxx.ico');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('app_branding');
    }
};
