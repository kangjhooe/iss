<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Free period slots for cancelled invoices so unique can be re-used
        DB::table('finance_invoices')
            ->where('status', 'cancelled')
            ->whereNotNull('period_label')
            ->orderBy('id')
            ->chunkById(200, function ($rows) {
                foreach ($rows as $row) {
                    DB::table('finance_invoices')->where('id', $row->id)->update([
                        'period_label' => $row->period_label . '-c' . $row->id,
                    ]);
                }
            });

        Schema::table('finance_invoices', function (Blueprint $table) {
            $table->unique(
                ['institution_id', 'fee_type_id', 'student_id', 'period_label'],
                'finance_invoices_period_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('finance_invoices', function (Blueprint $table) {
            $table->dropUnique('finance_invoices_period_unique');
        });
    }
};
