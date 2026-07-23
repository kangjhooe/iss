<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Batal mutasi: pending bisa dibatalkan langsung;
     * approved (sudah diterima tujuan) butuh persetujuan admin sekolah tujuan (cancel_pending).
     */
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE student_mutations MODIFY status ENUM('pending','approved','rejected','cancelled','cancel_pending') NOT NULL DEFAULT 'pending'");
        }

        Schema::table('student_mutations', function (Blueprint $table) {
            $table->text('cancel_reason')->nullable()->after('notes');
            $table->foreignId('cancel_requested_by')->nullable()->after('cancel_reason')->constrained('user')->nullOnDelete();
            $table->timestamp('cancel_requested_at')->nullable()->after('cancel_requested_by');
            $table->text('cancel_rejection_reason')->nullable()->after('cancel_requested_at');
        });
    }

    public function down(): void
    {
        Schema::table('student_mutations', function (Blueprint $table) {
            $table->dropForeign(['cancel_requested_by']);
            $table->dropColumn([
                'cancel_reason',
                'cancel_requested_by',
                'cancel_requested_at',
                'cancel_rejection_reason',
            ]);
        });

        $driver = Schema::getConnection()->getDriverName();
        if ($driver === 'mysql') {
            DB::statement("UPDATE student_mutations SET status = 'rejected' WHERE status IN ('cancelled','cancel_pending')");
            DB::statement("ALTER TABLE student_mutations MODIFY status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending'");
        }
    }
};
