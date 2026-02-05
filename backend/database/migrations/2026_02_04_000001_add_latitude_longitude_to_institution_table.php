<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Menambahkan latitude dan longitude untuk validasi geolocation QR attendance.
     */
    public function up(): void
    {
        Schema::table('institution', function (Blueprint $table) {
            $table->decimal('latitude', 10, 8)->nullable()->after('address')->comment('Latitude untuk validasi lokasi QR attendance');
            $table->decimal('longitude', 11, 8)->nullable()->after('latitude')->comment('Longitude untuk validasi lokasi QR attendance');
            $table->integer('location_radius')->default(100)->after('longitude')->comment('Radius validasi lokasi dalam meter (default 100m)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('institution', function (Blueprint $table) {
            $table->dropColumn(['latitude', 'longitude', 'location_radius']);
        });
    }
};
