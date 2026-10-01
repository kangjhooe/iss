<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            SuperAdminSeeder::class,
            AcademicYearSeeder::class,
            InventoryCategorySeeder::class,
            CorrespondenceCategorySeeder::class,
            TemplateSuratSeeder::class,
            QuranSurahSeeder::class,
        ]);
    }
}
