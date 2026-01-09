<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // First, update any existing NULL values with unique temporary values
        $institutions = \DB::table('institution')->whereNull('npsn')->get();
        foreach ($institutions as $index => $institution) {
            \DB::table('institution')
                ->where('id', $institution->id)
                ->update(['npsn' => str_pad((string)($institution->id + 10000000), 8, '0', STR_PAD_LEFT)]);
        }
        
        Schema::table('institution', function (Blueprint $table) {
            // Remove unique constraint temporarily
            $table->dropUnique(['npsn']);
        });
        
        Schema::table('institution', function (Blueprint $table) {
            // Change column to NOT NULL
            $table->string('npsn')->nullable(false)->change();
        });
        
        Schema::table('institution', function (Blueprint $table) {
            // Add unique constraint back
            $table->unique('npsn');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('institution', function (Blueprint $table) {
            $table->string('npsn')->nullable()->unique()->change();
        });
    }
};
