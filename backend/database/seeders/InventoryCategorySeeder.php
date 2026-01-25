<?php

namespace Database\Seeders;

use App\Models\InventoryCategory;
use App\Models\Institution;
use Illuminate\Database\Seeder;

class InventoryCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            ['code' => 'ELEK', 'name' => 'Elektronik', 'description' => 'Peralatan elektronik'],
            ['code' => 'MEU', 'name' => 'Meubelair', 'description' => 'Perabotan dan furniture'],
            ['code' => 'LAB', 'name' => 'Peralatan Lab', 'description' => 'Peralatan laboratorium'],
            ['code' => 'BUK', 'name' => 'Buku', 'description' => 'Buku dan literatur'],
            ['code' => 'OLA', 'name' => 'Peralatan Olahraga', 'description' => 'Peralatan olahraga'],
            ['code' => 'MUS', 'name' => 'Peralatan Musik', 'description' => 'Peralatan musik'],
            ['code' => 'KEB', 'name' => 'Peralatan Kebersihan', 'description' => 'Peralatan kebersihan'],
            ['code' => 'DKP', 'name' => 'Peralatan Dapur/Kantin', 'description' => 'Peralatan dapur dan kantin'],
            ['code' => 'MED', 'name' => 'Peralatan Medis/Kesehatan', 'description' => 'Peralatan medis dan kesehatan'],
            ['code' => 'ATK', 'name' => 'Alat Tulis Kantor', 'description' => 'Alat tulis kantor'],
            ['code' => 'TUK', 'name' => 'Peralatan Pertukangan', 'description' => 'Peralatan pertukangan'],
            ['code' => 'TAN', 'name' => 'Peralatan Pertanian', 'description' => 'Peralatan pertanian'],
        ];

        // Get all institutions
        $institutions = Institution::all();

        foreach ($institutions as $institution) {
            foreach ($categories as $category) {
                // Check if category already exists for this institution
                $exists = InventoryCategory::where('institution_id', $institution->id)
                    ->where('code', $category['code'])
                    ->exists();

                if (!$exists) {
                    InventoryCategory::create([
                        'institution_id' => $institution->id,
                        'code' => $category['code'],
                        'name' => $category['name'],
                        'description' => $category['description'],
                        'is_active' => true,
                    ]);
                }
            }
        }

        $this->command->info('Inventory categories seeded successfully!');
    }
}
