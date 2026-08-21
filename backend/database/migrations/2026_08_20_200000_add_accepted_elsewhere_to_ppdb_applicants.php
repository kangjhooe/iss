<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->modifyStatusEnum([
            'draft', 'submitted', 'verification', 'verified', 'rejected',
            'passed', 'reserve', 'failed', 're_registration', 'converted',
            'cancelled', 'accepted_elsewhere',
        ]);
    }

    public function down(): void
    {
        if (Schema::hasTable('ppdb_applicants')) {
            DB::table('ppdb_applicants')
                ->where('status', 'accepted_elsewhere')
                ->update(['status' => 'cancelled']);
        }

        $this->modifyStatusEnum([
            'draft', 'submitted', 'verification', 'verified', 'rejected',
            'passed', 'reserve', 'failed', 're_registration', 'converted', 'cancelled',
        ]);
    }

    /**
     * @param  list<string>  $values
     */
    private function modifyStatusEnum(array $values): void
    {
        if (! Schema::hasTable('ppdb_applicants') || ! Schema::hasColumn('ppdb_applicants', 'status')) {
            return;
        }

        $driver = Schema::getConnection()->getDriverName();
        if (! in_array($driver, ['mysql', 'mariadb'], true)) {
            return;
        }

        $quoted = implode(', ', array_map(fn (string $value) => "'".$value."'", $values));
        DB::statement("ALTER TABLE ppdb_applicants MODIFY COLUMN status ENUM({$quoted}) DEFAULT 'draft'");
    }
};
