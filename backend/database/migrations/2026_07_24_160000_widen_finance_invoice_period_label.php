<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Cancelled invoices append "-c{id}" to free the active unique key; 20 chars is too short.
        $driver = Schema::getConnection()->getDriverName();
        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE finance_invoices MODIFY period_label VARCHAR(64) NULL');
        } elseif ($driver === 'pgsql') {
            DB::statement('ALTER TABLE finance_invoices ALTER COLUMN period_label TYPE VARCHAR(64)');
        }
        // sqlite: type affinity ignores length; recreate not needed for tests
    }

    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();
        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE finance_invoices MODIFY period_label VARCHAR(20) NULL');
        } elseif ($driver === 'pgsql') {
            DB::statement('ALTER TABLE finance_invoices ALTER COLUMN period_label TYPE VARCHAR(20)');
        }
    }
};
