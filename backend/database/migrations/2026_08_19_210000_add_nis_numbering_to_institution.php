<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('institution', function (Blueprint $table) {
            if (! Schema::hasColumn('institution', 'nis_numbering')) {
                if (Schema::hasColumn('institution', 'admission_label')) {
                    $table->json('nis_numbering')->nullable()->after('admission_label');
                } else {
                    $table->json('nis_numbering')->nullable();
                }
            }
        });

        if (! Schema::hasTable('institution_nis_sequences')) {
            Schema::create('institution_nis_sequences', function (Blueprint $table) {
                $table->id();
                $table->foreignId('institution_id')->constrained('institution')->cascadeOnDelete();
                $table->string('period_key', 16);
                $table->unsignedInteger('last_seq')->default(0);
                $table->timestamps();

                $table->unique(['institution_id', 'period_key'], 'institution_nis_sequences_unique');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('institution_nis_sequences');

        if (Schema::hasColumn('institution', 'nis_numbering')) {
            Schema::table('institution', function (Blueprint $table) {
                $table->dropColumn('nis_numbering');
            });
        }
    }
};
