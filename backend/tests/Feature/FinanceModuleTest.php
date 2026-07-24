<?php

namespace Tests\Feature;

use App\Models\FinanceFeeType;
use App\Models\FinanceInvoice;
use App\Models\Institution;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class FinanceModuleTest extends TestCase
{
    use RefreshDatabase;

    protected Institution $institution;

    protected User $admin;

    protected Student $student;

    protected function setUp(): void
    {
        parent::setUp();

        $this->institution = Institution::create([
            'name' => 'SMP Keuangan',
            'npsn' => '50505050',
            'level' => 'SMP',
            'is_active' => true,
        ]);

        $this->admin = User::create([
            'name' => 'Admin Keuangan',
            'email' => 'admin-finance@example.com',
            'password' => Hash::make('Password123!'),
            'role' => 'institution_admin',
            'institution_id' => $this->institution->id,
            'email_verified_at' => now(),
            'is_active' => true,
        ]);

        $this->student = Student::create([
            'institution_id' => $this->institution->id,
            'nis' => '1001',
            'name' => 'Siswa Satu',
            'gender' => 'L',
            'status' => 'Aktif',
        ]);
    }

    public function test_admin_can_create_fee_type_and_generate_invoice_and_pay(): void
    {
        Sanctum::actingAs($this->admin);

        $feeRes = $this->postJson('/api/v1/finance/fee-types', [
            'name' => 'Kas Kelas',
            'code' => 'KAS',
            'frequency' => 'as_needed',
            'scope' => 'class',
            'default_amount' => 25000,
            'is_active' => true,
        ]);
        $feeRes->assertCreated();
        $feeTypeId = $feeRes->json('data.id');

        $gen = $this->postJson('/api/v1/finance/invoices/generate', [
            'fee_type_id' => $feeTypeId,
            'title' => 'Kas kelas Maret',
            'amount' => 25000,
            'student_ids' => [$this->student->id],
        ]);
        $gen->assertCreated()
            ->assertJsonPath('created', 1);

        $invoiceId = $gen->json('data.0.id');
        $this->assertDatabaseHas('finance_invoices', [
            'id' => $invoiceId,
            'status' => 'unpaid',
            'amount' => 25000,
        ]);

        $pay = $this->postJson('/api/v1/finance/payments', [
            'invoice_id' => $invoiceId,
            'amount' => 25000,
            'method' => 'cash',
        ]);
        $pay->assertCreated();

        $this->assertDatabaseHas('finance_invoices', [
            'id' => $invoiceId,
            'status' => 'paid',
        ]);

        $summary = $this->getJson('/api/v1/finance/summary');
        $summary->assertOk()
            ->assertJsonPath('data.collected', 25000);
    }

    public function test_monthly_spp_requires_period_label_and_skips_duplicate(): void
    {
        Sanctum::actingAs($this->admin);

        $fee = FinanceFeeType::create([
            'institution_id' => $this->institution->id,
            'name' => 'SPP',
            'code' => 'SPP',
            'frequency' => 'monthly',
            'scope' => 'school',
            'default_amount' => 150000,
            'is_active' => true,
        ]);

        $first = $this->postJson('/api/v1/finance/invoices/generate', [
            'fee_type_id' => $fee->id,
            'period_label' => '2026-07',
            'title' => 'SPP 2026-07',
            'student_ids' => [$this->student->id],
        ]);
        $first->assertCreated()->assertJsonPath('created', 1);

        $second = $this->postJson('/api/v1/finance/invoices/generate', [
            'fee_type_id' => $fee->id,
            'period_label' => '2026-07',
            'title' => 'SPP 2026-07',
            'student_ids' => [$this->student->id],
        ]);
        $second->assertCreated()
            ->assertJsonPath('created', 0)
            ->assertJsonPath('skipped', 1);

        $this->assertEquals(1, FinanceInvoice::where('fee_type_id', $fee->id)->count());
    }

    public function test_generate_requires_explicit_target(): void
    {
        Sanctum::actingAs($this->admin);

        $fee = FinanceFeeType::create([
            'institution_id' => $this->institution->id,
            'name' => 'Iuran',
            'frequency' => 'one_time',
            'scope' => 'school',
            'default_amount' => 10000,
            'is_active' => true,
        ]);

        $res = $this->postJson('/api/v1/finance/invoices/generate', [
            'fee_type_id' => $fee->id,
            'title' => 'Iuran tanpa target',
            'amount' => 10000,
        ]);

        $res->assertStatus(422);
    }

    public function test_payment_cannot_exceed_remaining(): void
    {
        Sanctum::actingAs($this->admin);

        $fee = FinanceFeeType::create([
            'institution_id' => $this->institution->id,
            'name' => 'Kas',
            'frequency' => 'as_needed',
            'scope' => 'school',
            'default_amount' => 10000,
            'is_active' => true,
        ]);

        $gen = $this->postJson('/api/v1/finance/invoices/generate', [
            'fee_type_id' => $fee->id,
            'title' => 'Kas',
            'amount' => 10000,
            'student_ids' => [$this->student->id],
        ]);
        $invoiceId = $gen->json('data.0.id');

        $pay = $this->postJson('/api/v1/finance/payments', [
            'invoice_id' => $invoiceId,
            'amount' => 15000,
            'method' => 'cash',
        ]);
        $pay->assertStatus(422);
    }

    public function test_invoice_export_returns_csv(): void
    {
        Sanctum::actingAs($this->admin);

        $fee = FinanceFeeType::create([
            'institution_id' => $this->institution->id,
            'name' => 'Export Fee',
            'frequency' => 'one_time',
            'scope' => 'school',
            'default_amount' => 5000,
            'is_active' => true,
        ]);

        $this->postJson('/api/v1/finance/invoices/generate', [
            'fee_type_id' => $fee->id,
            'title' => 'Export Test',
            'amount' => 5000,
            'student_ids' => [$this->student->id],
        ])->assertCreated();

        $res = $this->get('/api/v1/finance/invoices/export?status=outstanding');
        $res->assertOk();
        $this->assertStringContainsString('text/csv', (string) $res->headers->get('content-type'));
        $this->assertStringContainsString('Export Test', $res->streamedContent());
    }

    public function test_yearly_fee_requires_period_label(): void
    {
        Sanctum::actingAs($this->admin);

        $fee = FinanceFeeType::create([
            'institution_id' => $this->institution->id,
            'name' => 'Uang Gedung',
            'frequency' => 'yearly',
            'scope' => 'school',
            'default_amount' => 500000,
            'is_active' => true,
        ]);

        $missing = $this->postJson('/api/v1/finance/invoices/generate', [
            'fee_type_id' => $fee->id,
            'title' => 'Uang Gedung 2026',
            'student_ids' => [$this->student->id],
        ]);
        $missing->assertStatus(422);

        $ok = $this->postJson('/api/v1/finance/invoices/generate', [
            'fee_type_id' => $fee->id,
            'title' => 'Uang Gedung 2026',
            'period_label' => '2026',
            'student_ids' => [$this->student->id],
        ]);
        $ok->assertCreated()->assertJsonPath('created', 1);
    }

    public function test_payment_includes_class_on_response_and_export_csv(): void
    {
        Sanctum::actingAs($this->admin);

        $fee = FinanceFeeType::create([
            'institution_id' => $this->institution->id,
            'name' => 'Kas',
            'frequency' => 'as_needed',
            'scope' => 'school',
            'default_amount' => 10000,
            'is_active' => true,
        ]);

        $gen = $this->postJson('/api/v1/finance/invoices/generate', [
            'fee_type_id' => $fee->id,
            'title' => 'Kas',
            'amount' => 10000,
            'student_ids' => [$this->student->id],
        ]);
        $invoiceId = $gen->json('data.0.id');

        $pay = $this->postJson('/api/v1/finance/payments', [
            'invoice_id' => $invoiceId,
            'amount' => 10000,
            'method' => 'cash',
        ]);
        $pay->assertCreated();
        $this->assertArrayHasKey('invoice', $pay->json('data'));

        $csv = $this->get('/api/v1/finance/payments/export');
        $csv->assertOk();
        $this->assertStringContainsString('text/csv', (string) $csv->headers->get('content-type'));
    }

    public function test_cancel_invoice_with_payments(): void
    {
        Sanctum::actingAs($this->admin);

        $fee = FinanceFeeType::create([
            'institution_id' => $this->institution->id,
            'name' => 'Iuran',
            'frequency' => 'one_time',
            'scope' => 'school',
            'default_amount' => 20000,
            'is_active' => true,
        ]);

        $gen = $this->postJson('/api/v1/finance/invoices/generate', [
            'fee_type_id' => $fee->id,
            'title' => 'Iuran',
            'amount' => 20000,
            'student_ids' => [$this->student->id],
        ]);
        $invoiceId = $gen->json('data.0.id');

        $this->postJson('/api/v1/finance/payments', [
            'invoice_id' => $invoiceId,
            'amount' => 5000,
            'method' => 'transfer',
        ])->assertCreated();

        $del = $this->deleteJson("/api/v1/finance/invoices/{$invoiceId}");
        $del->assertOk();
        $this->assertDatabaseHas('finance_invoices', [
            'id' => $invoiceId,
            'status' => 'cancelled',
        ]);

        // Payments on cancelled invoices are hidden from the default payments list
        $list = $this->getJson('/api/v1/finance/payments');
        $list->assertOk();
        $this->assertSame(0, count($list->json('data') ?? []));

        $withCancelled = $this->getJson('/api/v1/finance/payments?include_cancelled=1');
        $withCancelled->assertOk();
        $this->assertGreaterThanOrEqual(1, count($withCancelled->json('data') ?? []));
    }

    public function test_cancel_monthly_invoice_frees_period_for_regenerate(): void
    {
        Sanctum::actingAs($this->admin);

        $fee = FinanceFeeType::create([
            'institution_id' => $this->institution->id,
            'name' => 'SPP Free',
            'code' => 'SPP-FREE',
            'frequency' => 'monthly',
            'scope' => 'school',
            'default_amount' => 100000,
            'is_active' => true,
        ]);

        $gen = $this->postJson('/api/v1/finance/invoices/generate', [
            'fee_type_id' => $fee->id,
            'period_label' => '2026-08',
            'title' => 'SPP 2026-08',
            'student_ids' => [$this->student->id],
        ]);
        $invoiceId = $gen->json('data.0.id');
        $gen->assertCreated();

        $this->putJson("/api/v1/finance/invoices/{$invoiceId}", [
            'status' => 'cancelled',
        ])->assertOk();

        $this->assertDatabaseHas('finance_invoices', [
            'id' => $invoiceId,
            'status' => 'cancelled',
            'period_label' => '2026-08-c' . $invoiceId,
        ]);

        $again = $this->postJson('/api/v1/finance/invoices/generate', [
            'fee_type_id' => $fee->id,
            'period_label' => '2026-08',
            'title' => 'SPP 2026-08',
            'student_ids' => [$this->student->id],
        ]);
        $again->assertCreated()->assertJsonPath('created', 1);
    }

    public function test_update_unpaid_invoice_amount_and_title(): void
    {
        Sanctum::actingAs($this->admin);

        $fee = FinanceFeeType::create([
            'institution_id' => $this->institution->id,
            'name' => 'Seragam',
            'frequency' => 'one_time',
            'scope' => 'school',
            'default_amount' => 100000,
            'is_active' => true,
        ]);

        $gen = $this->postJson('/api/v1/finance/invoices/generate', [
            'fee_type_id' => $fee->id,
            'title' => 'Seragam A',
            'amount' => 100000,
            'student_ids' => [$this->student->id],
        ]);
        $invoiceId = $gen->json('data.0.id');

        $upd = $this->putJson("/api/v1/finance/invoices/{$invoiceId}", [
            'title' => 'Seragam B',
            'amount' => 120000,
        ]);
        $upd->assertOk()
            ->assertJsonPath('data.title', 'Seragam B')
            ->assertJsonPath('data.amount', 120000);
    }
}
