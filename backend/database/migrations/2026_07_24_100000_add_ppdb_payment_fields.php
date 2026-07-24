<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ppdb_periods', function (Blueprint $table) {
            $table->decimal('registration_fee', 12, 2)->nullable()->after('description');
            $table->decimal('re_registration_fee', 12, 2)->nullable()->after('registration_fee');
        });

        Schema::table('ppdb_applicants', function (Blueprint $table) {
            $table->string('payment_status', 20)->default('unpaid')->after('notes');
            $table->decimal('payment_amount', 12, 2)->nullable()->after('payment_status');
            $table->string('payment_type', 30)->nullable()->after('payment_amount');
            $table->timestamp('paid_at')->nullable()->after('payment_type');
            $table->text('payment_notes')->nullable()->after('paid_at');
        });
    }

    public function down(): void
    {
        Schema::table('ppdb_periods', function (Blueprint $table) {
            $table->dropColumn(['registration_fee', 're_registration_fee']);
        });

        Schema::table('ppdb_applicants', function (Blueprint $table) {
            $table->dropColumn(['payment_status', 'payment_amount', 'payment_type', 'paid_at', 'payment_notes']);
        });
    }
};
