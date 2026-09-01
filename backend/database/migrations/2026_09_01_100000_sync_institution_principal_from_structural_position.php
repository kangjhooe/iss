<?php

use App\Models\Employee;
use App\Models\EmployeeStructuralPosition;
use App\Models\Institution;
use App\Models\StructuralPosition;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $positionId = StructuralPosition::query()->where('key', 'kepala_sekolah')->value('id');
        if (!$positionId) {
            return;
        }

        Institution::query()->orderBy('id')->chunkById(100, function ($institutions) use ($positionId) {
            foreach ($institutions as $institution) {
                $hasActive = EmployeeStructuralPosition::query()
                    ->where('institution_id', $institution->id)
                    ->where('structural_position_id', $positionId)
                    ->where(function ($q) {
                        $q->whereNull('ended_at')->orWhere('ended_at', '>=', now()->toDateString());
                    })
                    ->exists();

                if (!$hasActive && $institution->principal_nip) {
                    $employee = Employee::query()
                        ->where('institution_id', $institution->id)
                        ->where('nip', $institution->principal_nip)
                        ->first(['id']);

                    if ($employee) {
                        DB::table('employee_structural_positions')->insert([
                            'institution_id' => $institution->id,
                            'employee_id' => $employee->id,
                            'structural_position_id' => $positionId,
                            'started_at' => $institution->created_at?->toDateString() ?? now()->toDateString(),
                            'ended_at' => null,
                            'notes' => 'Backfill otomatis dari profil instansi (migrasi principal).',
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }

                $institution->syncPrincipalCache();
            }
        });
    }

    public function down(): void
    {
        // Tidak mengembalikan data manual lama.
    }
};
