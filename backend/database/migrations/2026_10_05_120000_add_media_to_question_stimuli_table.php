<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('question_stimuli', function (Blueprint $table) {
            if (! Schema::hasColumn('question_stimuli', 'media')) {
                $table->json('media')->nullable()->after('attachment_path');
            }
        });
    }

    public function down(): void
    {
        Schema::table('question_stimuli', function (Blueprint $table) {
            if (Schema::hasColumn('question_stimuli', 'media')) {
                $table->dropColumn('media');
            }
        });
    }
};
