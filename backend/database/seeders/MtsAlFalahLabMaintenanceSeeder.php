<?php

namespace Database\Seeders;

use App\Models\Institution;
use App\Models\InventoryCategory;
use App\Models\InventoryItem;
use App\Models\InventoryMaintenance;
use App\Models\Room;
use App\Models\User;
use App\Support\InventoryCatalog;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

/**
 * Perawatan laptop Lab Komputer — 2 catatan, salah satunya berakhir rusak.
 *
 *   php artisan db:seed --class=MtsAlFalahLabMaintenanceSeeder
 */
class MtsAlFalahLabMaintenanceSeeder extends Seeder
{
    public const NPSN = '10816663';

    public const SEED_MARKER = 'SEED:LAB-MAINT-ALFALAH';

    public function run(): void
    {
        $institution = Institution::where('npsn', self::NPSN)->first();
        if (! $institution) {
            $this->command?->error('Institution NPSN '.self::NPSN.' tidak ditemukan.');

            return;
        }

        $room = Room::where('institution_id', $institution->id)
            ->where('type', 'Laboratorium')
            ->where('lab_type', 'Komputer')
            ->first();
        if (! $room) {
            $this->command?->error('Ruang Lab Komputer belum ada.');

            return;
        }

        $laptop = InventoryItem::where('institution_id', $institution->id)
            ->where('room_id', $room->id)
            ->where('code', 'like', '%LAB-LAPSIS%')
            ->first()
            ?? InventoryItem::where('institution_id', $institution->id)
                ->where('room_id', $room->id)
                ->where('name', 'Laptop Siswa')
                ->first();

        if (! $laptop) {
            $this->command?->error('Laptop Siswa lab belum ada. Jalankan MtsAlFalahLabInventorySeeder dulu.');

            return;
        }

        $admin = User::where('institution_id', $institution->id)->orderBy('id')->first()
            ?? User::orderBy('id')->first();
        if (! $admin) {
            $this->command?->error('User admin tidak ditemukan.');

            return;
        }

        InventoryMaintenance::withTrashed()
            ->where('institution_id', $institution->id)
            ->where('notes', 'like', '%'.self::SEED_MARKER.'%')
            ->forceDelete();

        // 1) Perawatan rutin — sukses
        InventoryMaintenance::create([
            'institution_id' => $institution->id,
            'item_id' => $laptop->id,
            'maintenance_type' => 'Perawatan',
            'scheduled_date' => Carbon::now()->subMonths(3)->toDateString(),
            'completed_date' => Carbon::now()->subMonths(3)->addDays(2)->toDateString(),
            'cost' => 150000,
            'vendor' => 'Teknisi Internal',
            'description' => 'Perawatan berkala laptop siswa: pembersihan, cek baterai, update OS.',
            'status' => 'Selesai',
            'technician_name' => 'Rudi Wariza',
            'notes' => self::SEED_MARKER.' | hasil=baik',
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
        ]);

        // 2) Perbaikan — berakhir rusak (1 unit)
        InventoryMaintenance::create([
            'institution_id' => $institution->id,
            'item_id' => $laptop->id,
            'maintenance_type' => 'Perbaikan',
            'scheduled_date' => Carbon::now()->subDays(20)->toDateString(),
            'completed_date' => Carbon::now()->subDays(15)->toDateString(),
            'cost' => 0,
            'vendor' => 'Servis Acer Krui',
            'description' => 'Perbaikan laptop siswa (motherboard / tidak nyala). Setelah dicek dinyatakan rusak berat, tidak ekonomis diperbaiki.',
            'status' => 'Selesai',
            'technician_name' => 'Teknisi Acer',
            'notes' => self::SEED_MARKER.' | hasil=rusak | qty=1',
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
        ]);

        // Pindahkan 1 unit ke item rusak (stok baik berkurang)
        $elektronik = InventoryCategory::where('institution_id', $institution->id)
            ->where('code', 'ELK')
            ->first();

        $npsn = $institution->npsn;
        $rusakCode = "ELK/LAB-LAPSIS-RUSAK/{$npsn}/2023";

        $laptopRusak = InventoryItem::withTrashed()
            ->where('institution_id', $institution->id)
            ->where('code', $rusakCode)
            ->first();

        $rusakPayload = [
            'institution_id' => $institution->id,
            'category_id' => $elektronik?->id ?? $laptop->category_id,
            'tracking_type' => InventoryCatalog::TRACKING_STOCK,
            'identity_status' => InventoryCatalog::IDENTITY_COMPLETE,
            'code' => $rusakCode,
            'name' => 'Laptop Siswa (Rusak)',
            'brand' => $laptop->brand ?? 'Acer',
            'quantity' => 1,
            'unit' => 'Unit',
            'condition' => 'Rusak Berat',
            'status' => 'Rusak',
            'room_id' => $room->id,
            'building_id' => $room->building_id,
            'location_note' => 'Lab Komputer — hasil perbaikan gagal',
            'purchase_date' => $laptop->purchase_date,
            'acquisition_method' => 'Pembelian',
            'description' => '1 unit laptop siswa rusak berat setelah perbaikan (seed perawatan)',
            'updated_by' => $admin->id,
        ];

        if ($laptopRusak) {
            if ($laptopRusak->trashed()) {
                $laptopRusak->restore();
            }
            $laptopRusak->fill($rusakPayload)->save();
        } else {
            $rusakPayload['created_by'] = $admin->id;
            InventoryItem::create($rusakPayload);
        }

        // Stok baik: pastikan 9 (dari 10 awal), idempotent
        if ((int) $laptop->quantity >= 10) {
            $laptop->update([
                'quantity' => 9,
                'updated_by' => $admin->id,
                'description' => trim(($laptop->description ?? '').' | 1 unit dipindah ke stok rusak setelah perbaikan'),
            ]);
        } elseif ((int) $laptop->quantity > 9) {
            $laptop->decrement('quantity');
        }

        $this->command?->info('Lab maintenance seeded: 2 perawatan laptop (1 sukses, 1 berakhir rusak → stok baik 9 + 1 rusak).');
    }
}
