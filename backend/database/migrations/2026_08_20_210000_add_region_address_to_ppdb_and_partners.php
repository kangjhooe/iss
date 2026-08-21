<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ppdb_applicants', function (Blueprint $table) {
            $table->string('village')->nullable()->after('address');
            $table->string('sub_district')->nullable()->after('village');
            $table->string('district')->nullable()->after('sub_district');
            $table->string('province')->nullable()->after('district');
            $table->string('postal_code', 10)->nullable()->after('province');
            $table->string('wilayah_province_code', 8)->nullable()->after('postal_code');
            $table->string('wilayah_regency_code', 16)->nullable()->after('wilayah_province_code');
            $table->string('wilayah_district_code', 16)->nullable()->after('wilayah_regency_code');
            $table->string('wilayah_village_code', 20)->nullable()->after('wilayah_district_code');
        });

        Schema::table('industry_partners', function (Blueprint $table) {
            $table->string('village')->nullable()->after('address');
            $table->string('sub_district')->nullable()->after('village');
            $table->string('district')->nullable()->after('sub_district');
            $table->string('province')->nullable()->after('district');
            $table->string('postal_code', 10)->nullable()->after('province');
            $table->string('wilayah_province_code', 8)->nullable()->after('postal_code');
            $table->string('wilayah_regency_code', 16)->nullable()->after('wilayah_province_code');
            $table->string('wilayah_district_code', 16)->nullable()->after('wilayah_regency_code');
            $table->string('wilayah_village_code', 20)->nullable()->after('wilayah_district_code');
        });
    }

    public function down(): void
    {
        $columns = [
            'village',
            'sub_district',
            'district',
            'province',
            'postal_code',
            'wilayah_province_code',
            'wilayah_regency_code',
            'wilayah_district_code',
            'wilayah_village_code',
        ];

        Schema::table('ppdb_applicants', function (Blueprint $table) use ($columns) {
            $table->dropColumn($columns);
        });

        Schema::table('industry_partners', function (Blueprint $table) use ($columns) {
            $table->dropColumn($columns);
        });
    }
};
