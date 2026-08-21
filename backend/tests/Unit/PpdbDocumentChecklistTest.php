<?php

namespace Tests\Unit;

use App\Models\PpdbChannel;
use App\Support\PpdbDocumentChecklist;
use Tests\TestCase;

class PpdbDocumentChecklistTest extends TestCase
{
    public function test_normalize_presets_and_custom_labels(): void
    {
        $out = PpdbDocumentChecklist::normalize([
            ['key' => 'foto', 'label' => 'Foto 3x4', 'required' => true],
            ['label' => 'Surat pindah', 'required' => false],
            ['label' => 'Foto 3x4', 'key' => 'foto'],
            '',
        ]);

        $this->assertCount(2, $out);
        $this->assertSame('foto', $out[0]['key']);
        $this->assertTrue($out[0]['required']);
        $this->assertSame('surat_pindah', $out[1]['key']);
        $this->assertFalse($out[1]['required']);
    }

    public function test_summarize_matches_key_or_legacy_name(): void
    {
        $channel = new PpdbChannel([
            'required_documents' => [
                ['key' => 'kk', 'label' => 'Kartu Keluarga', 'required' => true],
                ['key' => 'akte', 'label' => 'Akte Kelahiran', 'required' => true],
            ],
        ]);

        $summary = PpdbDocumentChecklist::summarize($channel, [
            (object) ['document_key' => 'kk', 'name' => 'KK'],
            (object) ['document_key' => null, 'name' => 'Akte Kelahiran'],
        ]);

        $this->assertTrue($summary['complete']);
        $this->assertSame(2, $summary['required_total']);
        $this->assertSame(2, $summary['required_uploaded']);
    }

    public function test_resolve_upload_fills_label_from_checklist(): void
    {
        $channel = new PpdbChannel([
            'required_documents' => [
                ['key' => 'foto', 'label' => 'Foto 3x4', 'required' => true],
            ],
        ]);

        [$key, $name] = PpdbDocumentChecklist::resolveUpload($channel, 'foto', '');
        $this->assertSame('foto', $key);
        $this->assertSame('Foto 3x4', $name);
    }
}
