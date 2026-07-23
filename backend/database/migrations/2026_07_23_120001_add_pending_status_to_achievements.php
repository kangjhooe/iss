<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('achievements', function (Blueprint $table) {
            $table->string('status', 32)->default('dicatat')->after('notes');
            $table->unsignedBigInteger('reviewed_by')->nullable()->after('status');
            $table->timestamp('reviewed_at')->nullable()->after('reviewed_by');
            $table->text('review_notes')->nullable()->after('reviewed_at');
            $table->index('status');
        });

        DB::table('achievements')->whereNull('status')->orWhere('status', '')->update([
            'status' => 'dicatat',
            'reviewed_at' => DB::raw('COALESCE(reviewed_at, created_at)'),
            'reviewed_by' => DB::raw('COALESCE(reviewed_by, given_by)'),
        ]);
    }

    public function down(): void
    {
        Schema::table('achievements', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropColumn(['status', 'reviewed_by', 'reviewed_at', 'review_notes']);
        });
    }
};
