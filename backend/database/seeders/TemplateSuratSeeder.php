<?php

namespace Database\Seeders;

use App\Models\TemplateSurat;
use Illuminate\Database\Seeder;

class TemplateSuratSeeder extends Seeder
{
    public function run(): void
    {
        $templates = require __DIR__ . '/platform_templates_data.php';

        foreach ($templates as $tpl) {
            TemplateSurat::updateOrCreate(
                ['institution_id' => null, 'kode' => $tpl['kode']],
                [
                    'nama' => $tpl['nama'],
                    'isi_html' => $tpl['isi_html'],
                    'status' => 'aktif',
                    'letter_type_code' => $tpl['letter_type_code'],
                    'subject_type' => $tpl['subject_type'] ?? 'siswa',
                ]
            );
        }
    }
}
