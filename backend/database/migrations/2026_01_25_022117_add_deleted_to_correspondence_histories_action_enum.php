<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Modify enum to add 'deleted' action
        // MySQL doesn't support direct enum modification, so we use raw SQL
        DB::statement("ALTER TABLE correspondence_histories MODIFY COLUMN action ENUM('created', 'updated', 'approved', 'rejected', 'sent', 'disposed', 'archived', 'disposition_completed', 'deleted') NOT NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove 'deleted' from enum
        // Note: This will fail if there are any records with 'deleted' action
        DB::statement("ALTER TABLE correspondence_histories MODIFY COLUMN action ENUM('created', 'updated', 'approved', 'rejected', 'sent', 'disposed', 'archived', 'disposition_completed') NOT NULL");
    }
};
