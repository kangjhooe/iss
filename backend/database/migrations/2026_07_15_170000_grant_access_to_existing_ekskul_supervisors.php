<?php

use App\Support\ExtracurricularAccess;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $employeeIds = DB::table('extracurriculars')
            ->whereNotNull('supervisor_employee_id')
            ->distinct()
            ->pluck('supervisor_employee_id');

        foreach ($employeeIds as $employeeId) {
            ExtracurricularAccess::grantAccessForEmployee((int) $employeeId);
        }
    }

    public function down(): void
    {
        // Intentionally left empty — do not revoke access already granted to pembina.
    }
};
