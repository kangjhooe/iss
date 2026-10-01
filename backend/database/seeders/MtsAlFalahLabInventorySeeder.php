<?php

namespace Database\Seeders;

use App\Models\Institution;
use App\Models\InventoryCategory;
use App\Models\InventoryItem;
use App\Models\Room;
use App\Models\User;
use App\Support\InventoryCatalog;
use Illuminate\Database\Seeder;

/**
 * Inventaris Lab Komputer — MTs Al-Falah Krui (NPSN 10816663).
 *
 * Prasyarat: institution + ruang Lab Komputer sudah ada.
 *
 *   php artisan db:seed --class=MtsAlFalahLabInventorySeeder
 */
class MtsAlFalahLabInventorySeeder extends Seeder
{
    public const NPSN = '10816663';

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
            $this->command?->error('Ruang Lab Komputer belum ada. Seed sarana prasarana dulu.');

            return;
        }

        $elektronik = InventoryCategory::where('institution_id', $institution->id)
            ->where('code', 'ELK')
            ->first();
        $meubelair = InventoryCategory::where('institution_id', $institution->id)
            ->where('code', 'MEU')
            ->first();

        if (! $elektronik || ! $meubelair) {
            $this->command?->error('Kategori ELK/MEU belum ada untuk institusi ini.');

            return;
        }

        $admin = User::where('institution_id', $institution->id)->orderBy('id')->first()
            ?? User::orderBy('id')->first();

        if (! $admin) {
            $this->command?->error('User untuk created_by tidak ditemukan.');

            return;
        }

        $npsn = $institution->npsn;
        $defs = [
            // Elektronik
            [
                'code' => "ELK/LAB-LAPSIS/{$npsn}/2023",
                'legacy_names' => ['Laptop Acer'],
                'category_id' => $elektronik->id,
                'name' => 'Laptop Siswa',
                'brand' => 'Acer',
                'quantity' => 10,
                'condition' => 'Baik',
                'status' => 'Tersedia',
                'purchase_date' => '2023-01-01',
                'description' => 'Laptop siswa Lab Komputer MTs Al-Falah Krui',
            ],
            [
                'code' => "ELK/LAB-PCGURU/{$npsn}/2019",
                'category_id' => $elektronik->id,
                'name' => 'PC Guru',
                'brand' => null,
                'quantity' => 2,
                'condition' => 'Baik',
                'status' => 'Tersedia',
                'purchase_date' => '2019-01-01',
                'description' => 'PC guru Lab Komputer',
            ],
            [
                'code' => "ELK/LAB-PRJ/{$npsn}/2024",
                'category_id' => $elektronik->id,
                'name' => 'Proyektor',
                'brand' => null,
                'quantity' => 1,
                'condition' => 'Baik',
                'status' => 'Tersedia',
                'purchase_date' => null,
                'description' => 'Proyektor Lab Komputer',
            ],
            [
                'code' => "ELK/LAB-RTR/{$npsn}/2024",
                'category_id' => $elektronik->id,
                'name' => 'Router',
                'brand' => null,
                'quantity' => 2,
                'condition' => 'Baik',
                'status' => 'Tersedia',
                'purchase_date' => null,
                'description' => 'Router jaringan Lab Komputer',
            ],
            [
                'code' => "ELK/LAB-UPS/{$npsn}/2024",
                'category_id' => $elektronik->id,
                'name' => 'UPS',
                'brand' => null,
                'quantity' => 1,
                'condition' => 'Rusak Berat',
                'status' => 'Rusak',
                'purchase_date' => null,
                'description' => 'UPS Lab Komputer (rusak)',
            ],
            [
                'code' => "ELK/LAB-WCAM/{$npsn}/2024",
                'category_id' => $elektronik->id,
                'name' => 'Webcam',
                'brand' => null,
                'quantity' => 1,
                'condition' => 'Baik',
                'status' => 'Tersedia',
                'purchase_date' => null,
                'description' => 'Webcam Lab Komputer',
            ],
            // Meubelair
            [
                'code' => "MEU/LAB-KRSIS/{$npsn}/2024",
                'category_id' => $meubelair->id,
                'name' => 'Kursi Siswa',
                'brand' => null,
                'quantity' => 20,
                'condition' => 'Baik',
                'status' => 'Tersedia',
                'purchase_date' => null,
                'description' => 'Kursi siswa Lab Komputer',
            ],
            [
                'code' => "MEU/LAB-MJSIS-BAIK/{$npsn}/2024",
                'category_id' => $meubelair->id,
                'name' => 'Meja Siswa',
                'brand' => null,
                'quantity' => 16,
                'condition' => 'Baik',
                'status' => 'Tersedia',
                'purchase_date' => null,
                'description' => 'Meja siswa Lab Komputer (kondisi baik)',
            ],
            [
                'code' => "MEU/LAB-MJSIS-RUSAK/{$npsn}/2024",
                'category_id' => $meubelair->id,
                'name' => 'Meja Siswa (Rusak)',
                'brand' => null,
                'quantity' => 4,
                'condition' => 'Rusak Ringan',
                'status' => 'Rusak',
                'purchase_date' => null,
                'description' => 'Meja siswa Lab Komputer (rusak)',
            ],
            [
                'code' => "MEU/LAB-WBOARD/{$npsn}/2024",
                'category_id' => $meubelair->id,
                'name' => 'Whiteboard',
                'brand' => null,
                'quantity' => 1,
                'condition' => 'Baik',
                'status' => 'Tersedia',
                'purchase_date' => null,
                'description' => 'Whiteboard Lab Komputer',
            ],
            [
                'code' => "MEU/LAB-MJGURU/{$npsn}/2024",
                'category_id' => $meubelair->id,
                'name' => 'Meja Guru',
                'brand' => null,
                'quantity' => 2,
                'condition' => 'Baik',
                'status' => 'Tersedia',
                'purchase_date' => null,
                'description' => 'Meja guru Lab Komputer',
            ],
            [
                'code' => "MEU/LAB-KRGURU/{$npsn}/2024",
                'category_id' => $meubelair->id,
                'name' => 'Kursi Guru',
                'brand' => null,
                'quantity' => 2,
                'condition' => 'Baik',
                'status' => 'Tersedia',
                'purchase_date' => null,
                'description' => 'Kursi guru Lab Komputer',
            ],
        ];

        $created = 0;
        $updated = 0;

        foreach ($defs as $def) {
            $legacyNames = $def['legacy_names'] ?? [];
            unset($def['legacy_names']);

            $item = InventoryItem::withTrashed()
                ->where('institution_id', $institution->id)
                ->where('code', $def['code'])
                ->first();

            if (! $item && $legacyNames !== []) {
                $item = InventoryItem::withTrashed()
                    ->where('institution_id', $institution->id)
                    ->whereIn('name', $legacyNames)
                    ->orderBy('id')
                    ->first();
            }

            $payload = array_merge($def, [
                'institution_id' => $institution->id,
                'tracking_type' => InventoryCatalog::TRACKING_STOCK,
                'identity_status' => InventoryCatalog::IDENTITY_COMPLETE,
                'unit' => 'Unit',
                'room_id' => $room->id,
                'building_id' => $room->building_id,
                'location_note' => 'Lab Komputer',
                'acquisition_method' => 'Pembelian',
                'updated_by' => $admin->id,
            ]);

            if ($item) {
                if ($item->trashed()) {
                    $item->restore();
                }
                $item->fill($payload);
                $item->save();
                $updated++;
            } else {
                $payload['created_by'] = $admin->id;
                InventoryItem::create($payload);
                $created++;
            }
        }

        $this->command?->info(sprintf(
            'Lab inventory seeded for %s (room #%d): %d created, %d updated.',
            $institution->name,
            $room->id,
            $created,
            $updated
        ));
    }
}
