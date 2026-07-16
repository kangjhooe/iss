<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('extracurriculars', function (Blueprint $table) {
            $table->json('days_of_week')->nullable()->after('status');
            $table->boolean('is_outdoor')->default(false)->after('room_id');
            $table->string('location_note', 255)->nullable()->after('is_outdoor');
        });

        // Migrate single day_of_week -> days_of_week JSON array
        $rows = DB::table('extracurriculars')->whereNotNull('day_of_week')->get(['id', 'day_of_week']);
        foreach ($rows as $row) {
            DB::table('extracurriculars')->where('id', $row->id)->update([
                'days_of_week' => json_encode([(int) $row->day_of_week]),
            ]);
        }

        Schema::table('extracurriculars', function (Blueprint $table) {
            $table->dropColumn('day_of_week');
        });
    }

    public function down(): void
    {
        Schema::table('extracurriculars', function (Blueprint $table) {
            $table->unsignedTinyInteger('day_of_week')->nullable()->after('status');
        });

        $rows = DB::table('extracurriculars')->whereNotNull('days_of_week')->get(['id', 'days_of_week']);
        foreach ($rows as $row) {
            $days = json_decode($row->days_of_week, true);
            $first = is_array($days) && count($days) ? (int) $days[0] : null;
            DB::table('extracurriculars')->where('id', $row->id)->update([
                'day_of_week' => $first,
            ]);
        }

        Schema::table('extracurriculars', function (Blueprint $table) {
            $table->dropColumn(['days_of_week', 'is_outdoor', 'location_note']);
        });
    }
};
