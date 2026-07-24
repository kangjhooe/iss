<?php

namespace Tests\Feature;

use App\Models\Building;
use App\Models\Institution;
use App\Models\InventoryCategory;
use App\Models\InventoryItem;
use App\Models\Land;
use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class FacilityInventoryPdfExportTest extends TestCase
{
    use RefreshDatabase;

    protected Institution $institution;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->institution = Institution::create([
            'name' => 'SMP Sarpras',
            'npsn' => '60606060',
            'level' => 'SMP',
            'is_active' => true,
            'principal_name' => 'Budi Kepala',
            'principal_nip' => '198001012000031001',
        ]);

        $this->admin = User::create([
            'name' => 'Admin Sarpras',
            'email' => 'admin-sarpras@example.com',
            'password' => Hash::make('Password123!'),
            'role' => 'institution_admin',
            'institution_id' => $this->institution->id,
            'email_verified_at' => now(),
            'is_active' => true,
        ]);
    }

    public function test_facility_export_pdf_returns_pdf(): void
    {
        Sanctum::actingAs($this->admin);

        $land = Land::create([
            'institution_id' => $this->institution->id,
            'name' => 'Tanah Utama',
            'area' => 1500,
            'status' => 'Milik Sendiri',
        ]);

        $building = Building::create([
            'institution_id' => $this->institution->id,
            'land_id' => $land->id,
            'name' => 'Gedung A',
            'code' => 'GA',
            'building_area' => 800,
            'condition' => 'Baik',
        ]);

        Room::create([
            'institution_id' => $this->institution->id,
            'building_id' => $building->id,
            'name' => 'Ruang Kelas 7A',
            'code' => 'R7A',
            'type' => 'Kelas',
            'condition' => 'Baik',
        ]);

        $response = $this->get('/api/v1/facility/export/pdf');

        $response->assertOk();
        $this->assertStringContainsString('pdf', strtolower($response->headers->get('content-type') ?? ''));
        $this->assertNotEmpty($response->getContent());
        $this->assertStringStartsWith('%PDF', $response->getContent());
    }

    public function test_inventory_export_pdf_returns_pdf(): void
    {
        Sanctum::actingAs($this->admin);

        $category = InventoryCategory::create([
            'institution_id' => $this->institution->id,
            'code' => 'ELEK',
            'name' => 'Elektronik',
        ]);

        InventoryItem::create([
            'institution_id' => $this->institution->id,
            'category_id' => $category->id,
            'code' => 'EL-001',
            'name' => 'Proyektor',
            'condition' => 'Baik',
            'status' => 'Tersedia',
            'quantity' => 2,
            'purchase_price' => 3500000,
            'created_by' => $this->admin->id,
        ]);

        $response = $this->get('/api/v1/inventory/reports/export/pdf');

        $response->assertOk();
        $this->assertStringContainsString('pdf', strtolower($response->headers->get('content-type') ?? ''));
        $this->assertNotEmpty($response->getContent());
        $this->assertStringStartsWith('%PDF', $response->getContent());
    }
}
