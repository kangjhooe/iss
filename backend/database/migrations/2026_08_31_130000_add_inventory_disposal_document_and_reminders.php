<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventory_disposal', function (Blueprint $table) {
            $table->string('document_path')->nullable()->after('disposal_document_number');
            $table->string('document_name')->nullable()->after('document_path');
            $table->unsignedInteger('document_size')->nullable()->after('document_name');
            $table->string('document_mime', 100)->nullable()->after('document_size');
        });

        Schema::table('inventory_loan', function (Blueprint $table) {
            $table->timestamp('overdue_notified_at')->nullable()->after('status');
            $table->timestamp('due_reminder_sent_at')->nullable()->after('overdue_notified_at');
        });

        Schema::table('inventory_item', function (Blueprint $table) {
            $table->timestamp('warranty_reminder_sent_at')->nullable()->after('warranty_expiry');
        });
    }

    public function down(): void
    {
        Schema::table('inventory_disposal', function (Blueprint $table) {
            $table->dropColumn(['document_path', 'document_name', 'document_size', 'document_mime']);
        });

        Schema::table('inventory_loan', function (Blueprint $table) {
            $table->dropColumn(['overdue_notified_at', 'due_reminder_sent_at']);
        });

        Schema::table('inventory_item', function (Blueprint $table) {
            $table->dropColumn('warranty_reminder_sent_at');
        });
    }
};
