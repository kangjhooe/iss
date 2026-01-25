<?php

namespace Database\Seeders;

use App\Models\CorrespondenceCategory;
use App\Models\Institution;
use Illuminate\Database\Seeder;

class CorrespondenceCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Kategori untuk Surat Masuk
        $categoriesMasuk = [
            ['name' => 'Surat Resmi', 'description' => 'Surat resmi dari instansi atau lembaga'],
            ['name' => 'Surat Undangan', 'description' => 'Surat undangan untuk acara atau kegiatan'],
            ['name' => 'Surat Pemberitahuan', 'description' => 'Surat pemberitahuan atau pengumuman'],
            ['name' => 'Surat Permohonan', 'description' => 'Surat permohonan atau permintaan'],
            ['name' => 'Surat Keputusan', 'description' => 'Surat keputusan dari instansi'],
            ['name' => 'Surat Edaran', 'description' => 'Surat edaran atau instruksi'],
        ];

        // Kategori untuk Surat Keluar
        $categoriesKeluar = [
            ['name' => 'Surat Resmi', 'description' => 'Surat resmi ke instansi atau lembaga'],
            ['name' => 'Surat Undangan', 'description' => 'Surat undangan untuk acara atau kegiatan'],
            ['name' => 'Surat Pemberitahuan', 'description' => 'Surat pemberitahuan atau pengumuman'],
            ['name' => 'Surat Permohonan', 'description' => 'Surat permohonan atau permintaan'],
            ['name' => 'Surat Keputusan', 'description' => 'Surat keputusan dari instansi'],
            ['name' => 'Surat Edaran', 'description' => 'Surat edaran atau instruksi'],
        ];

        // Kategori untuk Surat Internal
        $categoriesInternal = [
            ['name' => 'Surat Edaran', 'description' => 'Surat edaran internal'],
            ['name' => 'Surat Pemberitahuan', 'description' => 'Surat pemberitahuan internal'],
            ['name' => 'Surat Instruksi', 'description' => 'Surat instruksi atau perintah'],
            ['name' => 'Surat Undangan', 'description' => 'Surat undangan internal'],
            ['name' => 'Surat Memo', 'description' => 'Memo atau catatan internal'],
        ];

        // Kategori "Lainnya" untuk semua tipe
        $categoryLainnya = [
            'name' => 'Lainnya',
            'description' => 'Kategori lainnya',
        ];

        // Get all institutions
        $institutions = Institution::all();

        foreach ($institutions as $institution) {
            // Create categories for Surat Masuk
            foreach ($categoriesMasuk as $category) {
                $exists = CorrespondenceCategory::where('institution_id', $institution->id)
                    ->where('name', $category['name'])
                    ->where('type', 'masuk')
                    ->exists();

                if (!$exists) {
                    CorrespondenceCategory::create([
                        'institution_id' => $institution->id,
                        'name' => $category['name'],
                        'type' => 'masuk',
                        'description' => $category['description'],
                    ]);
                }
            }

            // Create categories for Surat Keluar
            foreach ($categoriesKeluar as $category) {
                $exists = CorrespondenceCategory::where('institution_id', $institution->id)
                    ->where('name', $category['name'])
                    ->where('type', 'keluar')
                    ->exists();

                if (!$exists) {
                    CorrespondenceCategory::create([
                        'institution_id' => $institution->id,
                        'name' => $category['name'],
                        'type' => 'keluar',
                        'description' => $category['description'],
                    ]);
                }
            }

            // Create categories for Surat Internal
            foreach ($categoriesInternal as $category) {
                $exists = CorrespondenceCategory::where('institution_id', $institution->id)
                    ->where('name', $category['name'])
                    ->where('type', 'internal')
                    ->exists();

                if (!$exists) {
                    CorrespondenceCategory::create([
                        'institution_id' => $institution->id,
                        'name' => $category['name'],
                        'type' => 'internal',
                        'description' => $category['description'],
                    ]);
                }
            }

            // Create kategori "Lainnya" untuk semua tipe
            foreach (['masuk', 'keluar', 'internal'] as $type) {
                $exists = CorrespondenceCategory::where('institution_id', $institution->id)
                    ->where('name', $categoryLainnya['name'])
                    ->where('type', $type)
                    ->exists();

                if (!$exists) {
                    CorrespondenceCategory::create([
                        'institution_id' => $institution->id,
                        'name' => $categoryLainnya['name'],
                        'type' => $type,
                        'description' => $categoryLainnya['description'],
                    ]);
                }
            }
        }

        $this->command->info('Correspondence categories seeded successfully!');
    }
}
