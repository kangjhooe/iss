<?php

use App\Services\InventoryNeedAnalysisService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventory_need_line', function (Blueprint $table) {
            $table->unsignedInteger('procure_qty')->default(0)->after('shortage_qty');
            $table->unsignedInteger('repair_qty')->default(0)->after('procure_qty');
            $table->decimal('unit_repair_price', 15, 2)->nullable()->after('unit_price');
            $table->decimal('procure_cost', 15, 2)->nullable()->after('estimated_cost');
            $table->decimal('repair_cost', 15, 2)->nullable()->after('procure_cost');
        });

        Schema::table('inventory_need_analysis', function (Blueprint $table) {
            $table->decimal('procure_budget', 15, 2)->default(0)->after('estimated_budget');
            $table->decimal('repair_budget', 15, 2)->default(0)->after('procure_budget');
        });

        $lines = DB::table('inventory_need_line')->get();
        foreach ($lines as $line) {
            $split = InventoryNeedAnalysisService::actionSplit(
                (int) $line->shortage_qty,
                (string) $line->usable_policy,
                (int) $line->existing_rusak_ringan,
                (int) $line->existing_rusak_sedang,
                (int) $line->existing_rusak_berat
            );
            $procureCost = $line->unit_price === null
                ? null
                : number_format($split['procure_qty'] * (float) $line->unit_price, 2, '.', '');
            $known = array_filter([$procureCost], fn ($value) => $value !== null);

            DB::table('inventory_need_line')->where('id', $line->id)->update([
                'procure_qty' => $split['procure_qty'],
                'repair_qty' => $split['repair_qty'],
                'procure_cost' => $procureCost,
                'repair_cost' => null,
                'estimated_cost' => $known === []
                    ? null
                    : number_format(array_sum(array_map('floatval', $known)), 2, '.', ''),
            ]);
        }

        foreach (DB::table('inventory_need_analysis')->pluck('id') as $analysisId) {
            $rows = DB::table('inventory_need_line')->where('analysis_id', $analysisId)->get();
            $procure = (float) $rows->sum(fn ($row) => (float) ($row->procure_cost ?? 0));
            $repair = (float) $rows->sum(fn ($row) => (float) ($row->repair_cost ?? 0));
            DB::table('inventory_need_analysis')->where('id', $analysisId)->update([
                'procure_budget' => number_format($procure, 2, '.', ''),
                'repair_budget' => number_format($repair, 2, '.', ''),
                'estimated_budget' => number_format($procure + $repair, 2, '.', ''),
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('inventory_need_analysis', function (Blueprint $table) {
            $table->dropColumn(['procure_budget', 'repair_budget']);
        });
        Schema::table('inventory_need_line', function (Blueprint $table) {
            $table->dropColumn(['procure_qty', 'repair_qty', 'unit_repair_price', 'procure_cost', 'repair_cost']);
        });
    }
};
