<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_mutations', function (Blueprint $table) {
            $table->string('source', 32)->default('admin')->after('initiated_by');
            $table->index('source');
        });
    }

    public function down(): void
    {
        Schema::table('student_mutations', function (Blueprint $table) {
            $table->dropIndex(['source']);
            $table->dropColumn('source');
        });
    }
};
