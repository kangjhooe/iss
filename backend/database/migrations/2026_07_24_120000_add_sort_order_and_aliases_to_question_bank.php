<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('question_bank', function (Blueprint $table) {
            if (! Schema::hasColumn('question_bank', 'sort_order')) {
                $table->unsignedInteger('sort_order')->default(0)->after('weight');
            }
            if (! Schema::hasColumn('question_bank', 'key_answer_aliases')) {
                $table->json('key_answer_aliases')->nullable()->after('key_answer');
            }
        });

        // Backfill sort_order per bank (or institution if bank null)
        $groups = DB::table('question_bank')
            ->select('bank_soal_id', 'institution_id')
            ->whereNull('deleted_at')
            ->groupBy('bank_soal_id', 'institution_id')
            ->get();

        foreach ($groups as $g) {
            $q = DB::table('question_bank')->whereNull('deleted_at')->orderBy('id');
            if ($g->bank_soal_id !== null) {
                $q->where('bank_soal_id', $g->bank_soal_id);
            } else {
                $q->whereNull('bank_soal_id')->where('institution_id', $g->institution_id);
            }
            $order = 1;
            foreach ($q->pluck('id') as $id) {
                DB::table('question_bank')->where('id', $id)->update(['sort_order' => $order++]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('question_bank', function (Blueprint $table) {
            if (Schema::hasColumn('question_bank', 'key_answer_aliases')) {
                $table->dropColumn('key_answer_aliases');
            }
            if (Schema::hasColumn('question_bank', 'sort_order')) {
                $table->dropColumn('sort_order');
            }
        });
    }
};
