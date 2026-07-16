<?php

use App\Support\PiketAccess;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        PiketAccess::backfillScheduledEmployees();
    }

    public function down(): void
    {
        // Tidak mencabut permission yang sudah diberikan.
    }
};
