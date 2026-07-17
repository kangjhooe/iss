<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student', function (Blueprint $table) {
            $table->unsignedTinyInteger('tingkat')->nullable()->after('residence_type');
            $table->index(['institution_id', 'tingkat']);
        });

        DB::table('student')
            ->whereNotNull('class_id')
            ->select(['id', 'class_id'])
            ->orderBy('id')
            ->chunkById(500, function ($students) {
                $grades = DB::table('class')
                    ->whereIn('id', $students->pluck('class_id')->unique())
                    ->whereNotNull('grade')
                    ->pluck('grade', 'id');

                foreach ($students as $student) {
                    if ($grades->has($student->class_id)) {
                        DB::table('student')
                            ->where('id', $student->id)
                            ->update(['tingkat' => $grades->get($student->class_id)]);
                    }
                }
            });
    }

    public function down(): void
    {
        Schema::table('student', function (Blueprint $table) {
            $table->dropIndex(['institution_id', 'tingkat']);
            $table->dropColumn('tingkat');
        });
    }
};
