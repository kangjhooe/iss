<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Ubah pickup_date menjadi datetime agar mencatat jam pengambilan.
     */
    public function up(): void
    {
        Schema::table('document_pickups', function (Blueprint $table) {
            $table->dateTime('pickup_date')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('document_pickups', function (Blueprint $table) {
            $table->date('pickup_date')->change();
        });
    }
};
