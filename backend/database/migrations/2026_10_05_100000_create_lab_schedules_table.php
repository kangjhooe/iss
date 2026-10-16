<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lab_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institution_id')->constrained('institution')->onDelete('cascade');
            $table->foreignId('room_id')->constrained('room')->onDelete('cascade');
            $table->foreignId('semester_id')->constrained('semesters')->onDelete('cascade');
            $table->foreignId('class_id')->constrained('class')->onDelete('cascade');
            $table->foreignId('subject_id')->constrained('subjects')->onDelete('cascade');
            $table->foreignId('employee_id')->constrained('employee')->onDelete('cascade');
            $table->unsignedTinyInteger('day_of_week')->comment('1=Senin .. 7=Minggu');
            $table->unsignedSmallInteger('period')->comment('Jam ke mulai');
            $table->unsignedSmallInteger('duration')->default(1)->comment('Jumlah JP berurutan');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['room_id', 'semester_id', 'day_of_week']);
            $table->index(['institution_id', 'semester_id']);
        });

        $this->migrateFromLessonSchedules();
    }

    /**
     * Salin jadwal pelajaran yang memakai ruang Laboratorium ke jadwal lab,
     * digabung per blok JP berurutan (duration).
     */
    private function migrateFromLessonSchedules(): void
    {
        if (! Schema::hasTable('lesson_schedules') || ! Schema::hasTable('room')) {
            return;
        }

        $labRoomIds = DB::table('room')
            ->where('type', 'Laboratorium')
            ->pluck('id')
            ->all();

        if ($labRoomIds === []) {
            return;
        }

        $rows = DB::table('lesson_schedules')
            ->whereIn('room_id', $labRoomIds)
            ->whereNull('deleted_at')
            ->orderBy('institution_id')
            ->orderBy('room_id')
            ->orderBy('semester_id')
            ->orderBy('class_id')
            ->orderBy('subject_id')
            ->orderBy('employee_id')
            ->orderBy('day_of_week')
            ->orderBy('period')
            ->get();

        $now = now();
        $buffer = [];

        $flush = function () use (&$buffer, $now) {
            if ($buffer === []) {
                return;
            }
            usort($buffer, fn ($a, $b) => $a->period <=> $b->period);
            $start = null;
            $duration = 0;
            $meta = null;

            $push = function () use (&$start, &$duration, &$meta, $now) {
                if ($start === null || $meta === null) {
                    return;
                }
                DB::table('lab_schedules')->insert([
                    'institution_id' => $meta->institution_id,
                    'room_id' => $meta->room_id,
                    'semester_id' => $meta->semester_id,
                    'class_id' => $meta->class_id,
                    'subject_id' => $meta->subject_id,
                    'employee_id' => $meta->employee_id,
                    'day_of_week' => $meta->day_of_week,
                    'period' => $start,
                    'duration' => $duration,
                    'notes' => $meta->notes,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
                $start = null;
                $duration = 0;
                $meta = null;
            };

            foreach ($buffer as $row) {
                if ($start === null) {
                    $start = (int) $row->period;
                    $duration = 1;
                    $meta = $row;
                    continue;
                }
                if ((int) $row->period === $start + $duration) {
                    $duration++;
                    continue;
                }
                $push();
                $start = (int) $row->period;
                $duration = 1;
                $meta = $row;
            }
            $push();
            $buffer = [];
        };

        $prevKey = null;
        foreach ($rows as $row) {
            $key = implode(':', [
                $row->institution_id,
                $row->room_id,
                $row->semester_id,
                $row->class_id,
                $row->subject_id,
                $row->employee_id,
                $row->day_of_week,
            ]);
            if ($prevKey !== null && $key !== $prevKey) {
                $flush();
            }
            $buffer[] = $row;
            $prevKey = $key;
        }
        $flush();
    }

    public function down(): void
    {
        Schema::dropIfExists('lab_schedules');
    }
};
