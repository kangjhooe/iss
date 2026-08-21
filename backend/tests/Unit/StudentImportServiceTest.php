<?php

namespace Tests\Unit;

use App\Models\Student;
use App\Services\StudentAccountService;
use App\Services\StudentImportService;
use Tests\TestCase;

class StudentImportServiceTest extends TestCase
{
    public function test_normalize_nisn_pads_leading_zeros(): void
    {
        $service = new StudentImportService(app(StudentAccountService::class));

        $this->assertSame('0096877778', $service->normalizeNisn(96877778));
        $this->assertSame('0096877778', $service->normalizeNisn('96877778'));
        $this->assertSame('0096877778', $service->normalizeNisn('0096877778'));
        $this->assertSame('0081007994', $service->normalizeNisn('81007994'));
        $this->assertNull($service->normalizeNisn(''));
        $this->assertNull($service->normalizeNisn(null));
    }

    public function test_normalize_nik_keeps_digits_only(): void
    {
        $service = new StudentImportService(app(StudentAccountService::class));

        $this->assertSame('3201010101010001', $service->normalizeNik('3201010101010001'));
        $this->assertSame('3201010101010001', $service->normalizeNik('3201-0101-0101-0001'));
        $this->assertNull($service->normalizeNik(''));
    }

    public function test_normalize_row_drops_excel_row_and_empty_unique_fields(): void
    {
        $service = new StudentImportService(app(StudentAccountService::class));

        $row = $service->normalizeRow([
            'excel_row' => 4,
            'nik' => '3201010101010001',
            'nisn' => 96877778,
            'nis' => '  ',
            'name' => ' RAYHAN AL\'AYUBI ',
            'tingkat' => '10',
            'address' => ' Jl. Melati 1 ',
            'village' => ' Way Halim ',
            'province' => '',
        ]);

        $this->assertArrayNotHasKey('excel_row', $row);
        $this->assertSame('3201010101010001', $row['nik']);
        $this->assertSame('0096877778', $row['nisn']);
        $this->assertNull($row['nis']);
        $this->assertSame("RAYHAN AL'AYUBI", $row['name']);
        $this->assertSame(10, $row['tingkat']);
        $this->assertSame('Aktif', $row['status']);
        $this->assertSame('Jl. Melati 1', $row['address']);
        $this->assertSame('Way Halim', $row['village']);
        $this->assertNull($row['province']);
    }

    public function test_import_block_reason_explains_inactive_and_deleted_students(): void
    {
        $service = new StudentImportService(app(StudentAccountService::class));

        $aktif = new Student([
            'name' => 'Siswa Aktif',
            'nisn' => '0011111111',
            'status' => 'Aktif',
        ]);
        $this->assertNull($service->importBlockReason($aktif));

        $pindah = new Student([
            'name' => "RAYHAN AL'AYUBI",
            'nisn' => '0096877778',
            'status' => 'Pindah',
        ]);
        $this->assertStringContainsString("RAYHAN AL'AYUBI", $service->importBlockReason($pindah));
        $this->assertStringContainsString('0096877778', $service->importBlockReason($pindah));
        $this->assertStringContainsString('Pindah (sudah dimutasi)', $service->importBlockReason($pindah));

        $tidakAktif = new Student(['name' => 'Jeni', 'nisn' => '0081007994', 'status' => 'Tidak Aktif']);
        $this->assertStringContainsString('Tidak Aktif', $service->importBlockReason($tidakAktif));

        $lulus = new Student(['name' => 'Alumni', 'nisn' => '0012345678', 'status' => 'Lulus']);
        $this->assertStringContainsString('Lulus (alumni)', $service->importBlockReason($lulus));

        $deleted = new Student(['name' => 'Jeni Lama', 'nisn' => '0081007994', 'status' => 'Aktif']);
        $deleted->deleted_at = now();
        $this->assertStringContainsString('kotak sampah', $service->importBlockReason($deleted));
    }
}
